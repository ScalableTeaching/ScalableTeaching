<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * App\Models\Course
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Course newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Course newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Course query()
 * @method static \Illuminate\Database\Eloquent\Builder|Course whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Course whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Course whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Course whereUpdatedAt($value)
 * @property-read Collection|Task[] $tasks
 * @property-read Collection|CourseRole[] $roles
 * @property-read int|null $tasks_count
 * @property string $max_groups
 * @property int $max_group_size
 * @property-read Collection|Group[] $groups
 * @property-read int|null $groups_count
 * @method static \Illuminate\Database\Eloquent\Builder|Course whereMaxGroupSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Course whereMaxGroups($value)
 * @property-read \Illuminate\Support\Collection<int|string,int>|null $task_engagement
 * @property-read \Illuminate\Support\Collection<int|string,int>|null $enrolment_per_day
 * @property int $gitlab_group_id
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = ['max_groups_amount', 'max_groups', 'max_group_size', 'name', 'gitlab_group_id'];
    protected $hidden = ['enroll_token'];

    // region Relationships

    /**
     * @return HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Group>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CourseUser::class)
            ->withPivot('role')
            ->as('courseMembership')
            ->withTimestamps();
    }

    /**
     * @return HasMany<CourseRole>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(CourseRole::class);
    }

    /**
     * @return HasManyThrough<Project>
     */
    public function projects(): HasManyThrough
    {
        return $this->hasManyThrough(Project::class, Task::class);
    }

    /**
     * @return HasManyThrough<GroupInvitation>
     */
    public function groupInvitations(): HasManyThrough
    {
        return $this->hasManyThrough(GroupInvitation::class, Group::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CourseUser::class)
            ->wherePivot('role', 'teacher')
            ->as('courseMembership')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CourseUser::class)
            ->wherePivot('role', 'student')
            ->as('courseMembership')
            ->withTimestamps();
    }

    /**
     * @return HasMany<CourseTrack>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(CourseTrack::class);
    }

    /**
     * @return HasMany<CourseActivity>
     */
    public function activities() : HasMany
    {
        return $this->hasMany(CourseActivity::class);
    }
    // endregion

    public static function booted()
    {
        static::creating(function(Course $course) {
            $course->enroll_token = Str::random(32);
        });
    }

    /**
     * @param User $user
     * @return Collection<int,Group>
     */
    public function userGroups(User $user): Collection
    {
        return $this->groups()
            ->with(['members', 'invitations.recipient', 'projects'])
            ->whereRelation('members', 'user_id', $user->id)
            ->latest()
            ->get();
    }

    public function hasMaxGroups(User $user): bool
    {
        $currentCount = $this->userGroups($user)->count();

        $upperLimit = match ($this->max_groups)
        {
            'same_as_assignments' => $this->tasks()->count(),
            'custom'              => $this->max_groups_amount,
            default               => 0
        };

        return $currentCount >= $upperLimit;
    }

    public function hasTeacher(User $user): bool
    {
        return $this->teachers()->where('user_id', $user->id)->exists();
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    // region Analytics

    /**
     * @return Attribute<\Illuminate\Support\Collection<int|string,int>|null,null>
     */
    public function taskEngagement(): Attribute
    {
        return Attribute::make(get: function($value, $attributes) {
            $taskIds = $this->tasks()->where('grouped_by', '!=', 'sidebar')->orderBy('order')->pluck('id');

            if($taskIds->count() == 0)
                return null;

            $enrolledCount = $this->students()->count();
            $teachers = $this->teachers()->pluck('users.id');

            return Grade::whereIn('task_id', $taskIds)
                ->whereNotIn('user_id', $teachers)
                ->leftJoin('tasks', 'grades.task_id', '=', 'tasks.id')
                ->select([DB::raw("count(tasks.id)/$enrolledCount as grade_count"), 'tasks.id', 'tasks.grouped_by', 'tasks.starts_at', 'tasks.name'])
                ->groupBy('task_id')
                ->orderBy('tasks.starts_at')
                ->orderBy('tasks.order')
                ->get()
                ->mapWithKeys(fn($result) => [$result->grouped_by  => round($result->grade_count, 2)]); // @phpstan-ignore-line
        });
    }

    /**
     * @return Attribute<\Illuminate\Support\Collection<int|string,int>|null,null>
     */
    public function enrolmentPerDay(): Attribute
    {
        return Attribute::make(get: function($value, $attributes) {
            /** @var CourseUser|null $lastEnrolment */
            $lastEnrolment = CourseUser::where('course_id', $this->id)->orderBy('created_at', 'desc')->first();

            return CourseUser::where('course_id', $this->id)->daily($this->created_at, $lastEnrolment->created_at->addDays(3))->total();
        });
    }
    // endregion
}

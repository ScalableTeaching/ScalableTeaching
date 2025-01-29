<?php

namespace App\Models;

use App\Models\Enums\GradeEnum;
use Domain\ActivityLogging\Course\CourseActivityLogging;
use Domain\ActivityLogging\Course\CourseActivityMessage;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;


/**
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $source_type
 * @property int $source_id
 * @property boolean $selected
 * @property Enums\GradeEnum $value
 * @property string|null $value_raw
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @mixin Eloquent
 */
class Grade extends Model
{
    use HasFactory;
    use CourseActivityLogging;

    protected $fillable = ['task_id', 'user_id', 'source_id', 'source_type', 'value', 'value_raw', 'selected', 'task_id', 'started_at', 'ended_at'];

    protected $casts = [
        'value'     => Enums\GradeEnum::class,
        'selected'  => 'boolean',
        'value_raw' => 'array',
    ];

    public $dates = ['started_at', 'ended_at'];

    // region relationships

    /**
     * @return BelongsTo<User,Grade>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Task,Grade>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return MorphTo<Model,Grade>
     */
    public function source(): MorphTo
    {
        return $this->morphTo("source");
    }

    // endregion

    public static function booted()
    {
        static::creating(function(Grade $grade) {
            if($grade->selected == true)
            {
                Grade::where('user_id', $grade->user_id)
                    ->where('task_id', $grade->task_id)
                    ->update(['selected' => false]);

                return;
            }
            $userOverridden = Grade::where('user_id', $grade->user_id)
                ->where('task_id', $grade->task_id)
                ->where('source_type', User::class)->exists();
            $grade->selected = ! $userOverridden;
            if($userOverridden)
                return;
            Grade::where('user_id', $grade->user_id)
                ->where('task_id', $grade->task_id)
                ->update(['selected' => false]);
        });
    }

    public function select(): void
    {
        Grade::where('user_id', $this->user_id)
            ->where('task_id', $this->task_id)
            ->update(['selected' => \DB::raw("id = $this->id")]);
    }

    // region ActivityLogging
    protected function logCreated(Grade $created): ?CourseActivityMessage
    {
        $class = $created->value == GradeEnum::Passed ? 'text-lime-green-600 dark:text-lime-green-400' : 'text-red-600 dark:text-red-400';
        $message = "<span class='$class'> " . $created->value->name . "</span> <a href='" . route('courses.tasks.show', [$created->task->course_id, $created->task_id]) . "'>{$created->task->name}</a>";

        /**
         * @var Grade $source
         */
        $source = $created->source();
        $affectedBy = match ($created->source_type)
        {
            User::class            => $created->source_id,
            ProjectFeedback::class => $source->user_id,
            default                => null
        };
        if($affectedBy == $created->user_id)
            $message .= " (self grading)";
        else if($created->source_type == Task::class)
            $message .= " (graded by system)";

        return new CourseActivityMessage($message, $created->task->course_id, $created->user_id, $affectedBy);
    }
    // endregion
}


<?php

namespace App\Http\Controllers\Course\Management;

use App\Http\Controllers\Controller;
use App\Jobs\Course\AddMemberToCourseGroup;
use App\Models\Course;
use App\Models\CourseUser;
use App\Models\Grade;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupUser;
use App\Models\User;
use Domain\GitLab\Definitions\GitLabUserAccessLevelEnum;
use Exception;
use GrahamCampbell\GitLab\GitLabManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function enrolment(Course $course): View
    {
        $members = $course->members()->orderBy('name')->get()->map(fn(User $user) => [
            'id'          => $user->id,
            'name'        => $user->name,
            'enrolled_at' => $user->courseMembership->created_at->diffForHumans(),
            'avatar'      => $user->avatar,
            'updatable'   => $user->id != auth()->id(),
            'role'        => $user->courseMembership->role,
        ]);

        $roles = [
            'student' => 'Student',
            'teacher' => 'Teacher',
        ];

        $roleRoute = route('courses.manage.update-role', $course);
        $kickRoute = route('courses.manage.kick-user', $course);
        $activityRoute = route('courses.manage.activity.index', $course);

        return view('courses.manage.enrolled', compact('members', 'roles', 'roleRoute', 'kickRoute', 'activityRoute'));
    }

    /**
     * @throws Exception
     */
    public function updateRole(Course $course): string
    {
        $validated = request()->validate([
            'role' => ['required', 'string'],
            'user' => ['required', 'numeric'],
        ]);

        $course->members()->updateExistingPivot($validated['user'], ['role' => $validated['role']]);
        $userToUpdate = User::find($validated['user']);
        if ( ! $userToUpdate)
        {
            throw new Exception("User not found");
        }

        try
        {
            $gitlabManager = app(GitLabManager::class);
            if ($validated['role'] == 'teacher')
            {
                AddMemberToCourseGroup::dispatch($userToUpdate->gitlab_id, $course->gitlab_group_id, GitLabUserAccessLevelEnum::OWNER->value);
            }
            else
            {
                // Remove from top-level group to avoid spying on other assignments.
                $gitlabManager->groups()->removeMember($course->gitlab_group_id, $userToUpdate->gitlab_id);
            }

        } catch (Exception $e)
        {
            logger()->error("Failed to update user role ({$validated['role']}) in GitLab for user with id: {$userToUpdate->gitlab_id}", ['exception' => $e]);
        }


        return "ok";
    }

    public function kickUser(Course $course): string
    {
        $validated = request()->validate([
            'user' => ['required', 'numeric'],
        ]);



        if (auth()->id() === $validated['user'])
        {
            flash()->addError("You are not allowed to kick yourself!");

            return redirect()->back();
        }

        $course->members()->detach($validated['user']);

        return "ok";
    }

    public function activity(Course $course): View
    {
        $activities = $course->activities();

        $searchFilter = request('user');
        if(is_numeric($searchFilter))
            $activities->where('affected_id', $searchFilter);
        else if(is_string($searchFilter))
            $activities->whereRelation('affected', 'name', 'like', "%$searchFilter%");

        if(filled(request('kind')))
        {
            $activities->where('resource_type', match (request('kind'))
            {
                'grade'            => Grade::class,
                'membership'       => CourseUser::class,
                'group'            => Group::class,
                'group-invitation' => GroupInvitation::class,
                'group-membership' => GroupUser::class,
                default            => null
            });
        }

        $activities = $activities->latest()->paginate(50);

        return view('courses.manage.activity', compact('course', 'activities'));
    }

    public function groups(Course $course) : View
    {
        $groups = $course->groups()->withCount('members')->withCount('projects')->orderBy('name');

        if(request()->has('filter'))
        {
            $groups->whereHas('members', function(Builder $query) {
                $query->where('name', 'like', '%' . request('filter') . '%');
            })->orWhere(function(Builder $query) use ($course) { // @phpstan-ignore-line
                $query->where('course_id', $course->id)->where('name', 'like', '%'. request('filter') . '%');
            });
        }

        $groups = $groups->paginate(30);

        return view('courses.manage.groups', compact('course', 'groups'));
    }

    public function showGroup(Course $course, Group $group) : View
    {
        $members = $group->members()->orderBy('name')->get();

        return view('courses.manage.group', compact('course', 'group', 'members'));
    }

    public function createGroup(Course $course) : RedirectResponse
    {
        $validated = request()->validate([
            'name' => 'required',
        ]);

        $group = $course->groups()->create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('courses.manage.groups.show', [$course, $group])->with('success', 'Group created');
    }

    public function deleteGroup(Course $course, Group $group) : RedirectResponse
    {
        $group->delete();

        return redirect()->route('courses.manage.groups.index', $course)->with('success', 'Group deleted');
    }

    public function addGroupMember(Course $course, Group $group) : RedirectResponse
    {
        $validated = request()->validate([
            'email' => 'required|email',
        ]);
        $user = User::where('email', $validated['email'])->first();
        if ($user == null)
            return redirect()->route('courses.manage.groups.show', [$course, $group])->withErrors(['email' => 'This user doesn\'t exist.']);

        $userIsPartOfCourse = $course->members()->where('users.id', $user->id)->exists();
        if( ! $userIsPartOfCourse)
            return redirect()->route('courses.manage.groups.show', [$course, $group])->withErrors(['name' => 'User is not part of the course']);

        $group->members()->syncWithoutDetaching([$user->id]);

        return redirect()->route('courses.manage.groups.show', [$course, $group])->with('success', $user->name . " added");
    }

    public function removeGroupMember(Course $course, Group $group) : RedirectResponse
    {
        $validated = request()->validate([
            'user' => 'required|numeric',
        ]);

        $user = $group->members()->where('users.id', $validated['user'])->first();
        if($user == null)
            return redirect()->route('courses.manage.groups.show', [$course, $group])->with('success', "User removed");

        $group->members()->detach($validated['user']);

        return redirect()->route('courses.manage.groups.show', [$course, $group])->with('success', "User removed");
    }

    public function updateGroup(Course $course, Group $group) : string
    {
        $validated = request()->validate([
            'name' => 'required',
        ]);

        $group->update([
            'name' => $validated['name'],
        ]);

        return "ok";
    }

    public function updateGroupSettings(Course $course) : string
    {
        $validated = request()->validate([
            'max-group-size'    => ['required_without:max-groups', 'nullable', 'numeric'],
            'max-groups'        => ['required_without:max-group-size'],
            'max-groups-amount' => ['required_if:max-groups,custom'],
        ]);

        if(array_key_exists('max-group-size', $validated))
        {
            $course->update(['max_group_size' => $validated['max-group-size']]);
        }

        if (array_key_exists('max-groups', $validated))
        {
            $course->update([
                'max_groups'        => $validated['max-groups'],
                'max_groups_amount' => array_key_exists('max-groups-amount', $validated) ? $validated['max-groups-amount'] : null,
            ]);
        }

        return "ok";
    }

    public function roles(Course $course): View
    {
        return view('courses.manage.roles');
    }
}

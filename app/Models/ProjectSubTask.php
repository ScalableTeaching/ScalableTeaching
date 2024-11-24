<?php

namespace App\Models;

use App\Models\Enums\CorrectionType;
use App\Modules\AutomaticGrading\AutomaticGrading;
use App\Modules\AutomaticGrading\AutomaticGradingSettings;
use App\Modules\AutomaticGrading\AutomaticGradingType;
use App\ProjectStatus;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * @property Project $project
 * @property int $points
 * @property int $sub_task_id
 * @mixin Eloquent
 */
class ProjectSubTask extends Model
{
    use HasFactory;

    protected $fillable = ['sub_task_id', 'source_id', 'source_type', 'points'];

    protected static function booted()
    {
        static::created(function(ProjectSubTask $projectSubTask) {
            $automaticGradingModule = $projectSubTask->project->task->module_configuration->resolveModule(AutomaticGrading::class);
            if ($automaticGradingModule == null)
                return;

            /** @var AutomaticGradingSettings $settings */
            $settings = $automaticGradingModule->settings();
            if ($settings->isPipelineBased())
            {
                // This case will be handled in the pipeline itself. See Pipeline#checkAutomaticGrading
                return;
            }

            $project = $projectSubTask->project;
            ProjectSubTask::checkAutomaticGrading($project, $settings->getGradingType());

            // Re-enable once validation is based on module installation.
            //if( ! $project->validateSubmission())
            //    return;


        });
    }

    private static function checkAutomaticGrading(Project $project, AutomaticGradingType $gradingType)
    {

        $isValid = match ($gradingType)
        {
            AutomaticGradingType::ALL_SUBTASKS      => ProjectSubTask::validateAllSubtasks($project),
            AutomaticGradingType::REQUIRED_SUBTASKS => ProjectSubTask::validateRequiredSubtasks($project),
            AutomaticGradingType::POINTS_REQUIRED   => ProjectSubTask::validatePointsRequired($project),

            default                                 => false
        };

        if ($isValid)
            $project->setProjectStatus(ProjectStatus::Finished);
        else
            $project->setProjectStatus(ProjectStatus::Active);
    }

    private static function validateAllSubtasks(Project $project): bool
    {
        Log::info("Validating project {$project->id} for all subtasks completed");
        $completedSubTasks = $project->subTasks->pluck('sub_task_id');
        $task = $project->task;
        $isValid = ! $task->sub_tasks->isMissingAny($completedSubTasks);

        if ( ! $isValid)
        {
            Log::info("Project {$project->id} is not valid for all subtasks completed");
        }

        return $isValid;
    }

    private static function validateRequiredSubtasks(Project $project): bool
    {
        Log::info("Validating project {$project->id} for all subtasks completed");
        $completedSubTasks = $project->subTasks->pluck('sub_task_id');

        $isValid = ! $project->task->isMissingRequiredSubtasks($completedSubTasks);

        if ( ! $isValid)
        {
            Log::info("Project {$project->id} is not valid for all subtasks completed");
        }

        return $isValid;
    }

    private static function validatePointsRequired(Project $project): bool
    {
        Log::info("Validating project {$project->id} for points required");

        $isValid = $project->task->hasProjectCompletedPointsRequired($project);

        if ( ! $isValid)
        {
            Log::info("Project {$project->id} is not valid for points required");
        }

        return $isValid;
    }

    /**
     * @return BelongsTo<Project,ProjectSubTask>
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

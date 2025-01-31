<?php

namespace GraphQL\SchemaObject;

class CiJobQueryObject extends QueryObject
{
    const OBJECT_NAME = "CiJob";

    public function selectActive()
    {
        $this->selectField("active");

        return $this;
    }

    public function selectAllowFailure()
    {
        $this->selectField("allowFailure");

        return $this;
    }

    public function selectArtifacts(CiJobArtifactsArgumentsObject $argsObject = null)
    {
        $object = new CiJobArtifactConnectionQueryObject("artifacts");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectBrowseArtifactsPath()
    {
        $this->selectField("browseArtifactsPath");

        return $this;
    }

    public function selectCanPlayJob()
    {
        $this->selectField("canPlayJob");

        return $this;
    }

    public function selectCancelable()
    {
        $this->selectField("cancelable");

        return $this;
    }

    public function selectCommitPath()
    {
        $this->selectField("commitPath");

        return $this;
    }

    public function selectCoverage()
    {
        $this->selectField("coverage");

        return $this;
    }

    public function selectCreatedAt()
    {
        $this->selectField("createdAt");

        return $this;
    }

    public function selectCreatedByTag()
    {
        $this->selectField("createdByTag");

        return $this;
    }

    public function selectDetailedStatus(CiJobDetailedStatusArgumentsObject $argsObject = null)
    {
        $object = new DetailedStatusQueryObject("detailedStatus");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectDownstreamPipeline(CiJobDownstreamPipelineArgumentsObject $argsObject = null)
    {
        $object = new PipelineQueryObject("downstreamPipeline");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectDuration()
    {
        $this->selectField("duration");

        return $this;
    }

    public function selectErasedAt()
    {
        $this->selectField("erasedAt");

        return $this;
    }

    public function selectFailureMessage()
    {
        $this->selectField("failureMessage");

        return $this;
    }

    public function selectFinishedAt()
    {
        $this->selectField("finishedAt");

        return $this;
    }

    public function selectId()
    {
        $this->selectField("id");

        return $this;
    }

    public function selectKind()
    {
        $this->selectField("kind");

        return $this;
    }

    public function selectManualJob()
    {
        $this->selectField("manualJob");

        return $this;
    }

    public function selectManualVariables(CiJobManualVariablesArgumentsObject $argsObject = null)
    {
        $object = new CiManualVariableConnectionQueryObject("manualVariables");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectName()
    {
        $this->selectField("name");

        return $this;
    }

    public function selectNeeds(CiJobNeedsArgumentsObject $argsObject = null)
    {
        $object = new CiBuildNeedConnectionQueryObject("needs");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectPipeline(CiJobPipelineArgumentsObject $argsObject = null)
    {
        $object = new PipelineQueryObject("pipeline");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectPlayPath()
    {
        $this->selectField("playPath");

        return $this;
    }

    public function selectPlayable()
    {
        $this->selectField("playable");

        return $this;
    }

    public function selectPreviousStageJobs(CiJobPreviousStageJobsArgumentsObject $argsObject = null)
    {
        $object = new CiJobConnectionQueryObject("previousStageJobs");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    /**
     * @deprecated Replaced by previousStageJobs and needs fields. Deprecated in 16.4.
     */
    public function selectPreviousStageJobsOrNeeds(CiJobPreviousStageJobsOrNeedsArgumentsObject $argsObject = null)
    {
        $object = new JobNeedUnionConnectionQueryObject("previousStageJobsOrNeeds");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectProject(CiJobProjectArgumentsObject $argsObject = null)
    {
        $object = new ProjectQueryObject("project");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectQueuedAt()
    {
        $this->selectField("queuedAt");

        return $this;
    }

    public function selectQueuedDuration()
    {
        $this->selectField("queuedDuration");

        return $this;
    }

    public function selectRefName()
    {
        $this->selectField("refName");

        return $this;
    }

    public function selectRefPath()
    {
        $this->selectField("refPath");

        return $this;
    }

    public function selectRetried()
    {
        $this->selectField("retried");

        return $this;
    }

    public function selectRetryable()
    {
        $this->selectField("retryable");

        return $this;
    }

    public function selectRunner(CiJobRunnerArgumentsObject $argsObject = null)
    {
        $object = new CiRunnerQueryObject("runner");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    /**
     * @deprecated **Status**: Experiment. Introduced in 15.11.
     */
    public function selectRunnerManager(CiJobRunnerManagerArgumentsObject $argsObject = null)
    {
        $object = new CiRunnerManagerQueryObject("runnerManager");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectScheduled()
    {
        $this->selectField("scheduled");

        return $this;
    }

    public function selectScheduledAt()
    {
        $this->selectField("scheduledAt");

        return $this;
    }

    public function selectSchedulingType()
    {
        $this->selectField("schedulingType");

        return $this;
    }

    public function selectShortSha()
    {
        $this->selectField("shortSha");

        return $this;
    }

    public function selectStage(CiJobStageArgumentsObject $argsObject = null)
    {
        $object = new CiStageQueryObject("stage");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectStartedAt()
    {
        $this->selectField("startedAt");

        return $this;
    }

    public function selectStatus()
    {
        $this->selectField("status");

        return $this;
    }

    public function selectStuck()
    {
        $this->selectField("stuck");

        return $this;
    }

    public function selectTags()
    {
        $this->selectField("tags");

        return $this;
    }

    public function selectTrace(CiJobTraceArgumentsObject $argsObject = null)
    {
        $object = new CiJobTraceQueryObject("trace");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectTriggered()
    {
        $this->selectField("triggered");

        return $this;
    }

    public function selectUserPermissions(CiJobUserPermissionsArgumentsObject $argsObject = null)
    {
        $object = new JobPermissionsQueryObject("userPermissions");
        if ($argsObject !== null) {
            $object->appendArguments($argsObject->toArray());
        }
        $this->selectField($object);

        return $object;
    }

    public function selectWebPath()
    {
        $this->selectField("webPath");

        return $this;
    }
}

<?php

namespace GraphQL\SchemaObject;

class PipelineConfigSourceEnumEnumObject extends EnumObject
{
    const UNKNOWN_SOURCE = "UNKNOWN_SOURCE";
    const REPOSITORY_SOURCE = "REPOSITORY_SOURCE";
    const AUTO_DEVOPS_SOURCE = "AUTO_DEVOPS_SOURCE";
    const WEBIDE_SOURCE = "WEBIDE_SOURCE";
    const REMOTE_SOURCE = "REMOTE_SOURCE";
    const EXTERNAL_PROJECT_SOURCE = "EXTERNAL_PROJECT_SOURCE";
    const BRIDGE_SOURCE = "BRIDGE_SOURCE";
    const PARAMETER_SOURCE = "PARAMETER_SOURCE";
    const COMPLIANCE_SOURCE = "COMPLIANCE_SOURCE";
}
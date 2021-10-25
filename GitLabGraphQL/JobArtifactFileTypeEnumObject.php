<?php

namespace GraphQL\SchemaObject;

class JobArtifactFileTypeEnumObject extends EnumObject
{
    const ARCHIVE = "ARCHIVE";
    const METADATA = "METADATA";
    const TRACE = "TRACE";
    const JUNIT = "JUNIT";
    const METRICS = "METRICS";
    const METRICS_REFEREE = "METRICS_REFEREE";
    const NETWORK_REFEREE = "NETWORK_REFEREE";
    const DOTENV = "DOTENV";
    const COBERTURA = "COBERTURA";
    const CLUSTER_APPLICATIONS = "CLUSTER_APPLICATIONS";
    const LSIF = "LSIF";
    const SAST = "SAST";
    const SECRET_DETECTION = "SECRET_DETECTION";
    const DEPENDENCY_SCANNING = "DEPENDENCY_SCANNING";
    const CONTAINER_SCANNING = "CONTAINER_SCANNING";
    const CLUSTER_IMAGE_SCANNING = "CLUSTER_IMAGE_SCANNING";
    const DAST = "DAST";
    const LICENSE_SCANNING = "LICENSE_SCANNING";
    const ACCESSIBILITY = "ACCESSIBILITY";
    const CODEQUALITY = "CODEQUALITY";
    const PERFORMANCE = "PERFORMANCE";
    const BROWSER_PERFORMANCE = "BROWSER_PERFORMANCE";
    const LOAD_PERFORMANCE = "LOAD_PERFORMANCE";
    const TERRAFORM = "TERRAFORM";
    const REQUIREMENTS = "REQUIREMENTS";
    const COVERAGE_FUZZING = "COVERAGE_FUZZING";
    const API_FUZZING = "API_FUZZING";
}

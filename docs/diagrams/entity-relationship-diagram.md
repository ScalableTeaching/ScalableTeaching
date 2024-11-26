# Entity Relationship Diagram over the Scalable Teaching System.

_NOTE: The survey tables are missing from this, as it is not something I have interacted with._

This should give a brief overview of how everything connects, instead of having to manually dig through the tables.

This ER diagram **DOES NOT** contain model fields, only a few has been picked out and described, as the purpose is more to document the relations and not the actual models itself.


```mermaid
---
title: "Scalable Teaching Entity Relationship"
---
erDiagram
    COURSE_USER {
        string role
    }

    COURSE_TRACK {
        int parent_id FK "The parent track id, to create nested tracks"
    }

    GRADE {
        entity source "A multi type entity, that is the source of the grade - could be project, user, pipeline, or feedback"
        bool selected "Whether or not this grade is the current active"
        string value "A string of either 'failed' or 'passed'"
    }

    GROUP_USER {
        int is_owner
    }

    PROJECT_SUB_TASK {
        entity source "The source that marked this subtask completed, and the amount of points"
        int sub_task_id "The id of the subtask that is used to lookup in the Task#module_configuration->subtask"
        int points "The amount of points rewarded - not necessarily the full amount of points"
    }

    USER {
        bool is_sys_admin "Whether or not the user is a system administrator, which can invite other teachers and make others sys admins"
        bool is_admin "Alias for professor/teacher, and has the right to create and manage courses."
    }

    COURSE ||--|{ TASK : has

    COURSE ||--|{ COURSE_USER : has_enrolled
    COURSE_USER ||--|| USER : pivots_on

    COURSE ||--|{ COURSE_ROLE : has

    COURSE ||--|{ COURSE_TRACK : has
    COURSE_TRACK ||--|{ COURSE_TRACK_PROJECT : has
    COURSE_TRACK_PROJECT ||--|{ PROJECT : linked_to
    COURSE ||--|{ COURSE_ACTIVITY : has
    COURSE_ACTIVITY }|--o| USER : affects
    COURSE_ACTIVITY }|--o| USER : initiator

    GRADE ||--|| USER : "grade owner"
    GRADE ||--|| TASK : linked
    

    COURSE ||--|{ GROUP : has
    GROUP ||--|{ GROUP_USER : has 
    GROUP_USER ||--|| USER : pivots_on
    GROUP ||--|{ GROUP_INVITATION : has_linked
    GROUP_INVITATION ||--|| USER : recipient
    GROUP_INVITATION ||--|| USER : invited_by

    PIPELINE }|--|| PROJECT : has


    PROJECT ||--|{ PROJECT_DIFF_INDEX : has
    PROJECT ||--|{ PROJECT_DOWNLOAD : has
    PROJECT ||--|{ PROJECT_PUSH : has
    PROJECT }|--|| USER : "ownable either user or group"
    
    PROJECT ||--|{ PROJECT_FEEDBACK : has
    PROJECT_FEEDBACK }|--|| USER : reciever
    PROJECT_FEEDBACK }|--|| TASK_DELEGATION : has
    PROJECT_FEEDBACK ||--|{ PROJECT_FEEDBACK_COMMENT : has
    PROJECT_FEEDBACK_COMMENT }|--|| USER : reviewer
    
    PROJECT ||--|{ PROJECT_SUB_TASK : "Completed"
    PROJECT ||--|{ PROJECT_SUB_TASK_COMMENT : has

    PROJECT_SUB_TASK_COMMENT }|--|| USER : author

    TASK ||--|{ PROJECT : has
    TASK }|--|o COURSE_TRACK : part_of
    TASK ||--|{ TASK_PROTECTED_FILE : has
    TASK ||--|{ TASK_DELEGATION : has
    
    TASK_DELEGATION }|--|| COURSE_ROLE : "role_of_user to delegate to"
    TASK_DELEGATION ||--|{ TASK_DELEGATION_USER : "user that has been delegated to"
    TASK_DELEGATION_USER }|--|| USER : "pivots_on"
```

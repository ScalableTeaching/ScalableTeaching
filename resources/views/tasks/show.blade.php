@php use App\Modules\AutomaticGrading\AutomaticGrading; @endphp
@extends('master')

@section('content')
    <task :task="{{ $task }}"
          @can('viewAnalytics', $task)
              edit-route="{{ route('courses.tasks.admin.preferences', [$course, $task]) }}"
          @endcan
          code-route="{{ $codeRoute }}"
          source_project_id="{{ $task->getGitlabProjectId() }}"
          :is-template-task="{!! $task->isTemplateTask() ? true : 'null' !!}"
          :grade="{{ $task->grade(auth()->user()) ?? 'null' }}"
          :survey="{{ json_encode($survey) }}"
          :sub-tasks="{{ json_encode($subTasks) }}" :project="{{ is_null($project) ? 'null' : $project}}"
          :progress="{{ json_encode($progress) }}"
          :build-graph="{{ collect($buildGraph) }}" :pushes="{{ json_encode($pushes) }}"
          warning="{{ session()->has('warning') ? session('warning') : '' }}" :groups="{{ $availableGroups }}"
          user-name="{{ auth()->user()->name }}" :total-builds="{{ $builds->sum() }}"
          :total-my-builds="{{ $myBuilds->sum() }}" csrf="{{ csrf_token() }}"
          :grading-config="{{ $task->isAutomaticallyGraded() ? json_encode($task->module_configuration->resolveModule(AutomaticGrading::class)->settings()) : json_encode([]) }}"
          new-project-url="{{ $newProjectRoute }}" is-text-task="{{ $task->isTextTask() }}" is-code-task="{{ $task->isCodeTask() }}"></task>
@endsection

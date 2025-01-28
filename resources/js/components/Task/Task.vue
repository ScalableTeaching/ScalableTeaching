<template>
    <div class="container mx-auto px-6 pt-4">
        <div class="flex gap-6 flex-wrap-reverse">
            <div class="flex-1 w-full lg:w-2/3">
                <div class="flex gap-5 mb-3">
                    <button v-if="showSurvey" @click="tab = 'survey'"
                            :class="[tab === 'survey' ? 'bg-lime-green-100 dark:bg-gray-400 text-lime-green-700 dark:text-gray-100 dark:hover:text-gray-100 hover:text-lime-green-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300']"
                            class="py-2 px-3 rounded-md font-semibold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <span>
                            Survey
                        </span>
                    </button>
                    <button @click="tab = 'description'"
                            :class="[tab === 'description' ? 'bg-lime-green-100 dark:bg-gray-400 text-lime-green-700 dark:text-gray-100 dark:hover:text-gray-100 hover:text-lime-green-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300']"
                            class="py-2 px-3 rounded-md font-semibold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>
                            Description
                        </span>
                    </button>
                    <button v-if="subTasks != null" @click="tab = 'tasks'"
                            :class="[tab === 'tasks' ? 'bg-lime-green-100 dark:bg-gray-400 text-lime-green-700 dark:text-gray-100 dark:hover:text-gray-100 hover:text-lime-green-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300']"
                            class="py-2 px-3 rounded-md font-semibold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>
                            Tasks
                        </span>
                    </button>
                    <button v-if="project != null && showBuilds" @click="tab = 'builds'"
                            :class="[tab === 'builds' ? 'bg-lime-green-100 dark:bg-gray-400 text-lime-green-700 dark:text-gray-100 dark:hover:text-gray-100 hover:text-lime-green-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300']"
                            class="py-2 px-3 rounded-md font-semibold flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>
                            Builds
                        </span>
                    </button>
                    <a class="text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300 py-2 px-3 rounded-md font-semibold flex" target="_blank" :href="codeRoute" v-if="codeRoute !== ''">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                        </svg>
                        <span>
                            Code
                        </span>
                    </a>
                    <a class="text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300 py-2 px-3 rounded-md font-semibold flex" target="_blank" :href="editRoute" v-if="editRoute != null">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-1" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>
                            Edit Exercise
                        </span>
                    </a>
                    <button v-if="project != null" @click="tab = 'settings'"
                            :class="[tab === 'settings' ? 'bg-lime-green-100 dark:bg-gray-400 text-lime-green-700 dark:text-gray-100 dark:hover:text-gray-100 hover:text-lime-green-700' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-500 dark:hover:text-gray-300']"
                            class="py-2 px-3 rounded-md font-semibold flex">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-1" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>
                            Settings
                        </span>
                    </button>
                </div>
                <div v-if="showSurvey" v-show="tab === 'survey'">
                    <survey :project-id="project.id" :survey="survey"></survey>
                </div>
                <div v-show="tab === 'description'" class="relative">
                    <div
                        class="absolute bg-white p-4 rounded-md shadow-md max-vh70 overflow-x-hidden overflow-scroll dark:bg-gray-800">
                        <div class="prose-sm dark:prose-light"
                             :class="[hideMissingAssignmentWarning || project != null || progress.ended || !isTemplateTask ? '': 'filter blur-sm']"
                             v-html="task.description"/>
                    </div>
                    <div class="absolute flex w-full justify-center"
                         v-if="!hideMissingAssignmentWarning && project == null && !progress.ended && isTemplateTask">
                        <div
                            class="bg-white shadow-lg border border-lime-green-600 dark:border-lime-green-400 px-4 py-6 rounded-md mt-8 dark:bg-gray-800">
                            <div class="flex justify-center items-center">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="mr-4 h-14 w-14 text-red-300 dark:text-red-400" fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <h3 class="font-bold text-lg mb-4 dark:text-white">You haven't started your
                                        assignment!</h3>
                                    <p class="bg-gray-100 text-red-700 dark:text-red-400 dark:bg-gray-900 rounded-md font-semibold px-2 py-2 text-sm max-w-xs mb-4 mt-2 text-center"
                                       v-html="errorMessage" v-show="errorMessage.length > 0"></p>
                                    <div class="mb-4 flex flex-col" v-if="Object.keys(groups).length > 0">
                                        <span class="mb-1 text-gray-600 dark:text-gray-400">Start Assignment as:</span>
                                        <select v-model="startAs"
                                                class="bg-gray-100 dark:bg-gray-600 border-gray-300 text-gray-900 dark:text-gray-200 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                            <option value="solo" v-text="userName"></option>
                                            <option :key="id" :value="id" v-for="(group, id) in groups"
                                                    v-text="group"></option>
                                        </select>
                                    </div>
                                    <div class="flex justify-center gap-4">
                                        <button @click="startAssignment(null)" :disabled="startingAssignment"
                                                :class="{'cursor-not-allowed': startingAssignment}"
                                                class="flex items-center px-2 py-2 tracking-wide text-white capitalize transition-colors duration-200 transform bg-lime-green-600 rounded-md hover:bg-lime-green-500 focus:outline-none focus:ring focus:ring-lime-green-300 focus:ring-opacity-80">
                                            <svg v-if="startingAssignment" class="animate-spin h-5 w-5 mr-1 text-white"
                                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span class="mx-1"
                                                  v-text="startingAssignment ? 'Creating...' : 'Start Assignment'"></span>
                                        </button>

                                        <button v-if="!startingAssignment" @click="hideMissingAssignmentWarning = true"
                                                class="flex items-center px-2 py-2 tracking-wide text-white capitalize transition-colors duration-200 transform bg-gray-600 rounded-md hover:bg-gray-500 focus:outline-none focus:ring focus:ring-gray-300 focus:ring-opacity-80">
                                            <span class="mx-1">Close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="subTasks != null" v-show="tab === 'tasks'">
                    <sub-tasks :ended="(project != null && project.status !== 'active') || progress.ended"
                               :tasks="subTasks" :tasks-required="task.correction_tasks_required"
                               :points-required="task.correction_points_required"
                               :correction-type="task.correction_type"
                               :is-code-task="this.isCodeTask"
                               :is-text-task="this.isTextTask"
                               :grading-config="this.gradingConfig"
                               :project-status="project !== null ? project.status : null"></sub-tasks>
                </div>
                <div v-show="tab === 'builds'">
                    <build-table :project-id="project.id" v-if="project != null"></build-table>
                </div>
                <div v-show="tab === 'settings'">
                    <settings :groups="groups" :project="project" :is-code-task="this.isCodeTask" v-if="project != null"></settings>
                </div>
            </div>
            <div class="w-full lg:w-1/3 mt-4 mb-4">
                <div v-if="project != null && project.ownable_type === 'App\\Models\\Group'"
                     class="bg-white shadow-lg px-4 py-4 rounded-md mt-8 dark:bg-gray-800">
                    <div class="flex items-center justify-center">
                        <h3 class="font-bold text-xl dark:text-white text-center">Group Project</h3>
                    </div>
                </div>
                <div v-if="project !== null && this.isCodeTask">
                    <validation-failed v-if="project != null && project.validation_errors != null && project.validation_errors.length > 0" :errors="project.validation_errors"></validation-failed>
                    <warning :message="warning" v-if="warning.length > 0"></warning>
                    <part-of-track v-if="task.track != null" :track="task.track"
                                   :is-started="project != null"></part-of-track>

                    <not-started :errorMessage.sync="errorMessage" @startAssignment="startAssignment"
                                 :starting-assignment="startingAssignment" :groups="groups" :user-name="userName"
                                 :is-text-task="this.isTextTask"
                                 v-if="(hideMissingAssignmentWarning || tab !== 'description') && project == null && !progress.ended"></not-started>
                    <started :project="project" :progress="progress"
                             v-else-if="project != null && project.status === 'active' && !progress.ended"></started>
                    <waiting
                        v-else-if="project != null && project.status === 'active' && progress.ended && pushes.length > 0"></waiting>
                    <completed v-else-if="project != null && project.status === 'finished'"
                               :project="project"></completed>
                    <overdue v-else-if="project != null && project.isMissed"></overdue>
                </div>
                <go-to-repo v-if="task.module != null && (task.type === 'exercise') || !isTemplateTask" :url="'https://gitlab.sdu.dk/projects/' + source_project_id"/>
                <mark-completed v-if="project != null && this.isTextTask" :csrf="this.csrf" :grade="this.grade" :course-id="this.task.course_id" :task-id="this.task.id" :project-id="this.project.id"></mark-completed>
                <div v-if="false" class="bg-white shadow-lg p-4 rounded-md mt-8 dark:bg-gray-800">
                    <h3 class="text-gray-800 dark:text-gray-100 text-xl font-semibold mb-3">Builds</h3>
                    <div>
                        <bar-chart :height="200" :data="datasets" :labels="labels"></bar-chart>
                    </div>
                    <p class="dark:text-gray-300">A total of <b
                        class="text-lime-green-400 dark:text-lime-green-500">{{ totalBuilds }}</b> builds have
                        completed during the task, of which
                        you account for <b class="text-lime-green-400 dark:text-lime-green-500">{{ totalMyBuilds }}</b>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LineChart from "../LineChart";
import BuildTable from "./Tabs/BuildTable";
import Settings from "./Tabs/Settings";
import NotStarted from "../Widgets/NotStarted";
import Started from "../Widgets/Started";
import Completed from "../Widgets/Completed";
import Overdue from "../Widgets/Overdue";
import Alert from "../Alert";
import BarChart from "../BarChart";
import Warning from "../Widgets/Warning";
import SubTasks from "./Tabs/SubTasks";
import PartOfTrack from "../Widgets/PartOfTrack";
import Waiting from "../Widgets/Waiting";
import Survey from "./Tabs/Survey";
import MarkCompleted from "../Widgets/MarkCompleted";
import ValidationFailed from "../Widgets/ValidationFailed.vue";
import GoToRepo from "../Widgets/GoToRepo.vue";

export default {
    components: {
        GoToRepo,
        ValidationFailed,
        MarkCompleted,
        Survey,
        PartOfTrack,
        SubTasks,
        Warning, BarChart, Overdue, Started, NotStarted, Settings, BuildTable, LineChart, Completed, Alert, Waiting
    },
    props: ['editRoute', 'task', 'grade', 'survey', 'pushes', 'project', 'progress', 'totalMyBuilds', 'totalBuilds', 'newProjectUrl', 'csrf', 'buildGraph', 'groups', 'userName', 'warning', 'subTasks', 'codeRoute', 'isTextTask', 'isCodeTask',
        'gradingConfig', 'isTemplateTask', 'source_project_id' // {gradingType: AutomaticGradingType enum, requiredSubtaskIds?: [int], pointsRequired?: int}
    ],
    methods: {
        startAssignment: async function (startAs) {
            let createAs = startAs == null ? this.startAs : startAs;
            this.startingAssignment = true;
            this.errorMessage = "";
            try {
                await axios.post(this.newProjectUrl, {
                    _token: this.csrf,
                    as: createAs
                });
                location.reload();
            } catch (e) {
                if (e.response.status === 404) {
                    location.reload();
                    return;
                }
                this.errorMessage = e.response.data.message;
                this.startingAssignment = false;
            }
        },
    },
    data: function () {
        return {
            tab: 'description',
            errorMessage: '',
            hideMissingAssignmentWarning: this.task.source_project_id === null || this.task.type === 'exercise',
            startingAssignment: false,
            labels: this.buildGraph.labels,
            datasets: this.buildGraph.datasets,
            startAs: "solo"
        }
    },
    computed: {
        showSurvey: function () {
            return this.project != null && this.survey.details != null && this.survey.can.view;
        },
        showBuilds: function () {
            return this.project !== null && this.isCodeTask;
        },
        /*taskCount: function () {
            return this.subTasks.list.reduce((total, group) => total + group.tasks.length, 0);
        },
        completedTaskCount: function () {
            return this.subTasks.list.reduce((total, group) => total + group.tasks.filter(x => x.completed).length, 0);
        }*/
    },
    mounted() {
        if (this.task.track != null)
            this.hideMissingAssignmentWarning = true;
        if (this.showSurvey)
            this.tab = 'survey';

    }
}
</script>

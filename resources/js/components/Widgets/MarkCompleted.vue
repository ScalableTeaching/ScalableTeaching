<template>
    <div class="shadow-lg">
        <div
            class="bg-white px-4 py-6 rounded-md mt-8 dark:bg-gray-800">

            <div v-if="this.project.status !== 'finished'" class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="h-24 w-24 text-gray-300 dark:text-gray-400 mr-4 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12.75 15l3-3m0 0l-3-3m3 3h-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex flex-col w-full justify-center">
                    <h3 class="font-bold text-lg dark:text-white">Mark completed</h3>
                    <p class="text-gray-600 dark:text-gray-300">Done with the task? You can mark the task completed
                        here.</p>
                    <div class="mt-2">
                        <button @click="mark()"
                                class="flex items-center px-2 py-2 tracking-wide text-white capitalize transition-colors duration-200 transform bg-lime-green-600 rounded-md hover:bg-lime-green-500 focus:outline-none focus:ring focus:ring-lime-green-300 focus:ring-opacity-80">
                            <svg v-if="marking" class="animate-spin h-5 w-5 mr-1 text-white"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="mx-1 " v-text="marking ? 'Marking...' : 'Mark Complete'"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex items-center" v-else>
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="h-24 w-24 text-lime-green-300 dark:text-lime-green-400 mr-4 flex-shrink-0" fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex flex-col w-full justify-center">
                    <h3 class="font-bold text-lg dark:text-white">Marked completed</h3>
                    <p class="text-gray-600 dark:text-gray-300">Nice! Task is marked as completed.</p>
                    <a v-if="next !== null" :href="next" class="flex mt-4 items-center px-2 py-2 tracking-wide text-white capitalize transition-colors duration-200 transform bg-lime-green-600 rounded-md hover:bg-lime-green-500 focus:outline-none focus:ring focus:ring-lime-green-300 focus:ring-opacity-80">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="w-6 h-6 mr-1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5"/>
                        </svg>
                        <span>Next exercise</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['grade', 'csrf', 'courseId', 'taskId', 'project'],
    data: function () {
        return {
            marking: false,
            next: null
        }
    },
    methods: {
        mark: async function () {
            if (this.marking === true)
                return;
            this.marking = true;
            await axios.post(`/courses/${this.courseId}/tasks/${this.taskId}/projects/${this.project.id}/mark-complete`);
            location.reload();
        }
    },
    async mounted() {
        let response = await axios.get(`/courses/${this.courseId}/tasks/${this.taskId}/next-exercise`);
        this.next = response.data.route;
    }
}
</script>

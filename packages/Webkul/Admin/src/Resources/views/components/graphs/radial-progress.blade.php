@pushOnce('scripts')
    <script type="text/x-template" id="v-radial-progress-template">
        <div class="relative">
            <div class="flex items-center justify-between w-full gap-2">
                <!-- Left label -->
                <div v-if="label" class="text-gray-600 dark:text-white">
                    <div v-text="label + ':'">
                    </div>
                    <div
                        class="text-xs"
                        v-text="subTitle ?? ''"
                    ></div>
                </div>
                <!-- Right radial circle -->
                <div class="relative" :style="{ width: containerSize + 'px', height: containerSize + 'px' }">
                    <svg class="size-full -rotate-90" :viewBox="`0 0 ${diameter} ${diameter}`">
                        <!-- Background circle -->
                        <circle
                            :cx="center"
                            :cy="center"
                            :r="radius"
                            fill="none"
                            class="stroke-current text-slate-200 dark:text-neutral-700"
                            stroke-width="5"
                        />

                        <!-- Progress circle -->
                        <circle
                            :cx="center"
                            :cy="center"
                            :r="radius"
                            fill="none"
                            :class="['stroke-current', strokeColor]"
                            stroke-width="4"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashOffset"
                            stroke-linecap="round"
                        />
                    </svg>

                    <div class="absolute top-1/2 start-1/2 transform -translate-y-1/2 -translate-x-1/2">
                        <span class="dark:text-white" :class="scoreClass">
                            @{{ score }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-radial-progress', {
            template: '#v-radial-progress-template',

            props: {
                score: {
                    type: Number,
                    required: true
                },
                label: {
                    type: String,
                    default: ''
                },
                subTitle: {
                    type: String,
                    default: ''
                },
                radius: {
                    type: Number,
                    default: 14
                },
                scoreClass: {
                    type: String,
                    default: 'text-xs'
                }
            },

            computed: {
                strokeColor() {
                    if (this.score > 70) return 'text-green-600';
                    if (this.score >= 30) return 'text-yellow-500';

                    return 'text-red-600';
                },

                containerSize() {
                    return (this.radius + 10) * 2;
                },

                circumference() {
                    return 2 * Math.PI * this.radius;
                },

                dashOffset() {
                    return this.circumference * (1 - this.score / 100);
                },

                center() {
                    return this.radius + 2;
                },

                diameter() {
                    return (this.radius + 2) * 2;
                }
            }
        });
    </script>
@endPushOnce

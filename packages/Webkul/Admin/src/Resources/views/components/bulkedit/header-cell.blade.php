@pushOnce('scripts')
    <script type="text/x-template" id="v-header-cell-template">
        <th class="font-semibold relative border border-white dark:bg-cherry-700 dark:border-cherry-800 bg-violet-50 text-center"
            :colspan="colspan"
            :rowspan="rowspan"
        >
        <div class="absolute right-0 top-0 w-[5px] h-full cursor-[col-resize] select-none" @mousedown="startResize($event)"></div>
            <div class="flex items-center justify-center p-2">
                <span>@{{ label }}</span>
            </div>
        </th>
    </script>

    <script type="module">
        app.component('v-header-cell', {
            template: '#v-header-cell-template',

            props: {
                column: {
                    type: Array,
                    required: true,
                },
                columnIndex: {
                    type: Number,
                    default: null,
                },
                sortBy: {
                    type:Number
                },
                sortDirection: {
                    type:String
                },
                columnWidth: { 
                    type: Number,
                    default: 150
                },
                colspan: { 
                    type: Number,
                    default: 1
                },
                rowspan: { 
                    type: Number,
                    default: 1
                },
                label:{ 
                    type: String,
                    default: ''
                }
            },

            computed: {
                isSorted() {
                    return this.columnIndex === this.sortBy;
                }
            },

            methods: {
                startResize(e) {
                    if (this.columnIndex == null) {
                        return;
                    }

                    const startX = e.pageX;
                    const colElement = document.getElementById('col_' + this.columnIndex);

                    if (!colElement) {
                        return;
                    }

                    const startWidth = colElement.offsetWidth;
                    const span = parseInt(colElement.getAttribute('span')) || 1;
                    let animationFrame = null;

                    const onMouseMove = (moveEvent) => {
                        const delta = moveEvent.pageX - startX;
                        const newTotalWidth = Math.max(40 * span, startWidth + delta);
                        const newWidthPerCol = newTotalWidth / span;

                        if (animationFrame) cancelAnimationFrame(animationFrame);

                        animationFrame = requestAnimationFrame(() => {
                            colElement.style.width = newWidthPerCol + 'px';
                        });
                    };

                    const onMouseUp = () => {
                        if (animationFrame) cancelAnimationFrame(animationFrame);
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                    };

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                },
            },
        });
    </script>
@endPushOnce

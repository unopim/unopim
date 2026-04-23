@pushOnce('scripts')
    <script type="text/x-template" id="v-header-cell-template">
        <th class="font-medium text-xs relative border border-gray-200 dark:bg-cherry-800 dark:border-cherry-700 bg-gray-100 text-center whitespace-nowrap overflow-hidden text-ellipsis"
            :colspan="colspan"
            :rowspan="rowspan"
            :title="label"
        >
            <div class="flex items-center justify-center px-2 py-1">
                <span class="truncate">@{{ label }}</span>
            </div>
            <div
                class="absolute right-[-4px] top-0 w-[10px] h-full cursor-[col-resize] select-none z-10 hover:bg-violet-400/50"
                @mousedown="startResize($event)"
                title="@lang('admin::app.catalog.products.bulk-edit.resize-column')"
            ></div>
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

                        const finalWidth = parseFloat(colElement.style.width) || startWidth;
                        this.$emitter?.emit?.('bulkedit-column-resized', {
                            index: this.columnIndex - 1,
                            width: finalWidth,
                        });
                    };

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                },
            },
        });
    </script>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-spreadsheet-row-template">
        <tr v-if="row" class="border-b border-gray-200 dark:border-cherry-700" :class="{ 'bg-gray-50 dark:bg-cherry-900': rowId % 2 === 1 }" :style="{ height: rowHeight + 'px' }">
            <td class="sticky left-0 z-10 bg-gray-100 dark:bg-cherry-800 border-r border-gray-200 dark:border-cherry-700 px-1 py-0 text-xs text-center text-gray-500 dark:text-gray-400 font-normal"
            >   @{{ row.id }}
                <div
                    class="absolute bottom-0 left-0 w-full h-1 cursor-row-resize z-20"
                    @mousedown="startRowResize"
                ></div>
            </td>

            <template v-for="(col, index) in fltColumns" >
                <v-spreadsheet-cell
                    :colId="index"
                    :rowId="rowId"
                    :value="getValue(row['values'], col)"
                    :entityId="row.id"
                    :col="col"
                    :attribute="columns[col.id]"
                    :locale="col.locale"
                    :channel="col.channel"
                />
            </template>
        </tr>
    </script>

    <script type="module">
        app.component('v-spreadsheet-row', {
            template: '#v-spreadsheet-row-template',

            props: {
                row: {
                    type: Array,
                    default: () => []
                },

                columns: {
                    type: Array,
                    default: () => []
                },

                rowId: {
                    type: Number
                },

                fltColumns: {
                    type: Array,
                    default: () => []
                },
            },

            data() {
                return {
                    isDragging: false,
                    rowHeight: 30,
                };
            },

            methods: {
                getValue(data, col) {
                    switch (col.key) {
                        case 'pcl':
                            return data.channel_locale_specific?.[col.channel]?.[col.locale]?.[col.code]?.[col.currency] ?? null;

                        case 'pl':
                            return data.locale_specific?.[col.locale]?.[col.code]?.[col.currency] ?? null;

                        case 'pc':
                            return data.channel_specific?.[col.channel]?.[col.code]?.[col.currency] ?? null;

                        case 'cl':
                            return data.channel_locale_specific?.[col.channel]?.[col.locale]?.[col.code] ?? null;

                        case 'c':
                            return data.channel_specific?.[col.channel]?.[col.code] ?? null;

                        case 'l':
                            return data.locale_specific?.[col.locale]?.[col.code] ?? null;

                        case 'p':
                            return data.common?.[col.code]?.[col.currency] ?? null;

                        default:
                            return data.common?.[col.code] ?? null;
                    }
                },

                startRowResize(e) {
                    const startY = e.clientY;
                    const startHeight = this.rowHeight;

                    const onMouseMove = (ev) => {
                        const newHeight = Math.max(24, startHeight + (ev.clientY - startY));
                        this.rowHeight = newHeight;
                    };

                    const onMouseUp = () => {
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

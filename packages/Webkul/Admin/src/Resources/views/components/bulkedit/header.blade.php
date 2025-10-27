@pushOnce('scripts')
    <script type="text/x-template" id="v-spreadsheet-header-template">
        <colgroup>
            <col style="width: 64px" id="col_0" />
            <template v-for="(header, index) in headers" :key="'col_' + index">
                <col :span="header.colspan" class="border dark:border-cherry-700 border-violet-50" :id="'col_' + (index + 1)" :style="{ width: '100px' }" />
            </template>
        </colgroup>

        <thead class="sticky top-0 z-50 ">
            <tr class="text-sm dark:text-white text-gray-600 border-b border-gray-600">
                <th 
                    class="sticky left-0 z-50 border border-white bg-violet-50 dark:bg-cherry-700 dark:border-cherry-800"
                >
                    @lang('admin::app.catalog.products.bulk-edit.id')
                </th>

                <template v-for="(header, index) in headers" :key="'main-' + index">
                    <v-header-cell
                        :columnIndex="(index + 1)"
                        :sortBy="sortBy"
                        :sortDirection="sortDirection"
                        :label="header.label"
                    />
                </template>
            </tr>
        </thead>
    </script>

    <script type="module">
        app.component('v-spreadsheet-header', {
            template: '#v-spreadsheet-header-template',

            props: {
                columns: {
                    type: Array,
                    required: true
                },

                headers: {
                    type: Array,
                    required: true
                },
            },

            data() {
                return {
                    sortBy: null,
                    sortDirection: 'asc',
                };
            },

            computed: {
                flatColIds() {
                    let ids = [];
                    let index = 1;

                    for (let header of this.headers) {
                        for (let i = 0; i < (header.colspan || 1); i++) {
                            ids.push(index++);
                        }
                    }

                    return ids;
                }
            },
        });
    </script>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-spreadsheet-grid-template">

    <tbody ref="tbody">
        <v-spreadsheet-row
            v-if="initialData.length"
            :columns="columns"
            v-for="(row, index) in initialData"
            :key="index"
            :rowId="index"
            :row="row"
            :fltColumns="fltColumns"
        ></v-spreadsheet-row>
    </tbody>

</script>

    <script type="module">
        app.component('v-spreadsheet-grid', {
            template: '#v-spreadsheet-grid-template',

            props: {
                url: {
                    type: String,
                    required: true
                },
                columns: {
                    type: Array,
                    default: () => []
                },
                initialData: {
                    type: Array,
                    default: () => []
                },
                locales: {
                    type: Array,
                    default: () => []
                },
                channels: {
                    type: Array,
                    default: () => []
                },
                channelLocales: {
                    type: Object,
                    default: () => ({})
                },
                fltColumns: {
                    type: Array,
                    default: () => []
                }
            },

            data() {
                return {
                    loading: false,
                    error: null,
                    activeRow: null,
                    activeCol: null,
                    instance: null,
                    suppressNextFocus: false,
                    valueCopied: null,
                    selected: false,
                    valuesCopied: {},
                    selecting: false,
                    dir: null,
                    count: 0,
                    activeCellInstance: null,
                    dragStart: null,
                    dragStop: null,
                    dragLastRow: null,
                    dragLastCol: null,
                    multipleDrag: null,
                    pasteDiff: null,
                    optionsCache: {},
                };
            },

            provide() {
                return {
                    gridContext: this,
                    optionsCache: this.optionsCache,
                };
            },

            created() {
                this.registerGlobalEvents();
                document.addEventListener("keydown", this.handleKeydown);
            },

            mounted() {
                document.addEventListener('click', this.handleClickOutside);
            },
            
            beforeUnmount() {
                document.removeEventListener("keydown", this.handleKeydown);
                document.removeEventListener('click', this.handleClickOutside);
            },

            methods: {
                fetchEntity() {
                    this.loading = true;
                    this.error = null;

                    this.$axios.get(this.url, {
                            params: {
                                limit: 10,
                                offset: 0
                            }
                        })
                        .then(response => {
                            let data = response.data?.data || [];
                            this.initialData = data;
                        })
                        .catch(console.error)
                        .finally(() => {
                            this.loading = false;
                        });
                },

                onCellSelected({instance}) {
                    if (!instance) {
                        if (this.instance) this.instance.isActive = false;
                        this.instance = null;
                        this.activeRow = null;
                        this.activeCol = null;
                        return;
                    }

                    this.instance = instance;
                    this.activeRow = instance.rowId;
                    this.activeCol = instance.colId;
                    this.focusActiveCell();
                },

                handleKeydown(e) {
                    if (this.activeRow === null || this.activeCol === null) return;

                    const editing = this.activeCellInstance?.isInputFocused;
                    if (editing) {
                        if (e.key === 'Escape') {
                            if (this.activeCellInstance?.isInputFocused) {
                                this.activeCellInstance.isInputFocused = false;
                                const input = this.activeCellInstance.$refs.component?.$refs?.input;
                                if (input && typeof input.blur === 'function') {
                                    input.blur();
                                }
                            }
                        }
                        return;
                    }

                    // Prevent browser default scrolling
                    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter', 'Tab'].includes(e.key)) {
                        e.preventDefault();
                    }

                    // F2 → Enter edit mode (focus input)
                    if (e.key === 'F2') {
                        this.$nextTick(() => {
                            const input = this.activeCellInstance?.$refs?.component?.$refs?.input;
                            if (input && typeof input.focus === 'function') {
                                this.activeCellInstance.isInputFocused = true;
                                input.focus();
                            }
                        });
                        return;
                    }
                    if ((e.ctrlKey || e.metaKey) && e.keyCode === 67) {
                        this.copyActiveCellToClipboard();
                        return;
                    }

                    if ((e.ctrlKey || e.metaKey) && e.keyCode === 86) {
                        this.handlePasteFromClipboard(e);
                        return;
                    }

                    if (e.key === 'Escape') {
                        if (this.activeCellInstance?.isInputFocused) {
                            this.activeCellInstance.isInputFocused = false;
                            const input = this.activeCellInstance.$refs.component?.$refs?.input;
                            if (input && typeof input.blur === 'function') {
                                input.blur();
                            }
                        }
                        return;
                    }

                    // Enter → Move down
                    if (e.key === 'Enter' && !e.shiftKey) {
                        this.moveActiveCell('ArrowDown');
                        this.focusActiveCell();
                        return;
                    }

                    // Tab → Move right
                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            this.moveActiveCell('ArrowLeft');
                        } else {
                            this.moveActiveCell('ArrowRight');
                        }

                        this.focusActiveCell();
                        return;
                    }

                    // Shift + Arrow → Range selection
                    if (e.shiftKey && e.key.startsWith('Arrow')) {
                        if (!this.selecting) this.enableShiftSelection();
                        this.handleArrowKeySelection(e.key);
                        return;
                    }

                    // Arrow keys (basic move)
                    if (e.key.startsWith('Arrow') && !e.shiftKey) {
                        this.suppressNextFocus = true;
                        this.moveActiveCell(e.key);
                        this.focusActiveCell();
                        return;
                    }

                },

                async preloadOptions() {
                    const multiColumns = this.columns.filter(c => 
                        c.type === 'multiselect' || c.type === 'select'
                    );

                    for (const col of multiColumns) {
                        if (this.optionsCache) continue;

                        try {
                            const baseUrl = "{{ route('admin.catalog.options.fetch-all') }}";
                            const url = new URL(baseUrl, window.location.origin);
                            url.searchParams.set("key", col.code);
                            url.searchParams.set("entityName", "attribute");
                            url.searchParams.set("page", 1);

                            const { data } = await this.$axios.get(url.toString());

                            this.optionsCache[col.code] = data;
                        } catch (err) {
                            console.error(`${col.code}`, err);
                            this.optionsCache[col.code] = [];
                        }
                    }
                },

                copyActiveCellToClipboard() {
                    this.valueCopied = null;
                    this.valuesCopied = {};

                    // Case 1: Multi-cell drag copy
                    if (this.dragStart && this.dragStop) {
                        const startRow = this.dragStart.row;
                        const endRow = this.dragStop.row;
                        const startCol = this.dragStart.col;
                        const endCol = this.dragStop.col;

                        const minRow = Math.min(startRow, endRow);
                        const maxRow = Math.max(startRow, endRow);
                        const minCol = Math.min(startCol, endCol);
                        const maxCol = Math.max(startCol, endCol);

                        for (let row = minRow; row <= maxRow; row++) {
                            for (let col = minCol; col <= maxCol; col++) {
                                const eventKey = `spreadsheet-cell-value-${row}-${col}`;
                                this.$emitter.emit(eventKey);
                            }
                        }
                    }

                    else if (this.instance) {
                        this.valueCopied = this.instance.internalValue;
                        this.valuesCopied = {};
                    }
                },

                handlePasteFromClipboard(e) {
                    e.preventDefault();

                    if (Object.keys(this.valuesCopied).length > 0 && !this.selected) {
                        const startRow = this.activeRow;
                        const startCol = this.activeCol;

                        // Step 1: Get origin of copied area
                        const keys = Object.keys(this.valuesCopied);
                        const rowCols = keys.map(k => k.split('-').map(Number));
                        const minRow = Math.min(...rowCols.map(([r]) => r));
                        const minCol = Math.min(...rowCols.map(([, c]) => c));

                        // Step 2: Paste with correct offset
                        for (const key in this.valuesCopied) {
                            const [row, col] = key.split('-').map(Number);
                            const value = this.valuesCopied[key];

                            const offsetRow = row - minRow;
                            const offsetCol = col - minCol;

                            const targetRow = startRow + offsetRow;
                            const targetCol = startCol + offsetCol;

                            if (targetRow < 0 || targetCol < 0) continue;

                            const pasteKey = `spreadsheet-cell-paste-${targetRow}-${targetCol}`;
                            this.$emitter.emit(pasteKey, value);
                        }

                        this.valueCopied = null;

                        this.focusActiveCell();

                        return;
                    }

                    if (this.selected && this.valueCopied) {
                        this.emitPasteRange();
                    }

                    if (this.valueCopied !== null && this.instance?.updateValue) {
                        this.instance.updateValue(this.valueCopied);
                        this.focusActiveCell();
                    }
                },

                emitPasteRange() {
                    if (!this.dragStart || !this.dragStop ) return;

                    const startRow = this.dragStart.row;
                    const startCol = this.dragStart.col;
                    const endRow = this.dragStop.row;
                    const endCol = this.dragStop.col;

                    const minRow = Math.min(startRow, endRow);
                    const maxRow = Math.max(startRow, endRow);
                    const minCol = Math.min(startCol, endCol);
                    const maxCol = Math.max(startCol, endCol);

                    for (let row = minRow; row <= maxRow; row++) {
                        for (let col = minCol; col <= maxCol; col++) {
                            const pasteKey = `spreadsheet-cell-paste-${row}-${col}`;
                            this.$emitter.emit(pasteKey, this.valueCopied);
                        }
                    }
                },

                moveActiveCell(key) {
                    const maxRow = this.initialData.length - 1;
                    const maxCol = this.fltColumns.length - 1;

                    if (this.dragStop) {
                        this.clearHighlighted(this.dragStart.row, this.dragStart.col);
                    }

                    if (this.activeCellInstance && typeof this.activeCellInstance.deactivateCell === 'function') {
                        this.activeCellInstance.deactivateCell();
                    }

                    switch (key) {
                        case 'ArrowUp':
                            this.activeRow = Math.max(0, this.activeRow - 1);
                            break;
                        case 'ArrowDown':
                            this.activeRow = Math.min(maxRow, this.activeRow + 1);
                            break;
                        case 'ArrowLeft':
                            this.activeCol = Math.max(0, this.activeCol - 1);
                            break;
                        case 'ArrowRight':
                            this.activeCol = Math.min(maxCol, this.activeCol + 1);
                            break;
                    }

                    this.clickActiveCell();
                },

                clickActiveCell() {
                    this.$nextTick(() => {
                        const cell = this.$el.querySelector(
                            `[data-row="${this.activeRow}"][data-col="${this.activeCol}"]`);
                        cell?.click();
                    });
                },

                finalizeDragFill(startRow, lastRow, col) {
                    const value = this.valueCopied;

                    if (startRow === lastRow) return;

                    const [min, max] = startRow < lastRow ?
                        [startRow + 1, lastRow] :
                        [lastRow, startRow - 1];
                    let eventKey = null;
                    for (let row = min; row <= max; row++) {
                        eventKey = `spreadsheet-cell-paste-${row}-${col}`;
                        let cell = this.$el.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                        if (cell) {
                            cell.classList.remove('bg-violet-200', 'dark:bg-cherry-900');
                        }
                        this.$emitter.emit(eventKey, value);
                    }
                },

                highlightCell(row, col) {
                    const cell = document.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                    if (cell) cell.classList.add('bg-violet-200', 'dark:bg-cherry-900');
                },

                unhighlightCell(row, col) {
                    const cell = document.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                    if (cell) cell.classList.remove('bg-violet-200', 'dark:bg-cherry-900');
                },

                updateSelectionDelta(newStopRow, newStopCol) {
                    let startRow = this.dragStart.row;
                    let startCol = this.dragStart.col;

                    let oldStopRow = this.dragStop?.row;
                    let oldStopCol = this.dragStop?.col;

                    if (oldStopRow == null || oldStopCol == null) {
                        this.dragStop = {
                            row: newStopRow,
                            col: newStopCol
                        }

                        this.highlightSelected();

                        return;
                    }

                    const minRow = Math.min(startRow, oldStopRow);
                    const maxRow = Math.max(startRow, oldStopRow);
                    const minCol = Math.min(startCol, oldStopCol);
                    const maxCol = Math.max(startCol, oldStopCol);

                    const newMinRow = Math.min(startRow, newStopRow);
                    const newMaxRow = Math.max(startRow, newStopRow);
                    const newMinCol = Math.min(startCol, newStopCol);
                    const newMaxCol = Math.max(startCol, newStopCol);

                    if (newMaxCol < maxCol) {
                        for (let row = minRow; row <= maxRow; row++) {
                            for (let col = newMaxCol + 1; col <= maxCol; col++) {
                                this.unhighlightCell(row, col);
                            }
                        }
                    }

                    if (newMinCol > minCol) {
                        for (let row = minRow; row <= maxRow; row++) {
                            for (let col = minCol; col < newMinCol; col++) {
                                this.unhighlightCell(row, col);
                            }
                        }
                    }

                    if (newMaxRow < maxRow) {
                        for (let row = newMaxRow + 1; row <= maxRow; row++) {
                            for (let col = minCol; col <= maxCol; col++) {
                                this.unhighlightCell(row, col);
                            }
                        }
                    }
                    if (newMinRow > minRow) {
                        for (let row = minRow; row < newMinRow; row++) {
                            for (let col = minCol; col <= maxCol; col++) {
                                this.unhighlightCell(row, col);
                            }
                        }
                    }

                    if (newMaxCol > maxCol) {
                        for (let row = newMinRow; row <= newMaxRow; row++) {
                            for (let col = maxCol + 1; col <= newMaxCol; col++) {
                                this.highlightCell(row, col);
                            }
                        }
                    }
                    if (newMinCol < minCol) {
                        for (let row = newMinRow; row <= newMaxRow; row++) {
                            for (let col = newMinCol; col < minCol; col++) {
                                this.highlightCell(row, col);
                            }
                        }
                    }

                    if (newMaxRow > maxRow) {
                        for (let row = maxRow + 1; row <= newMaxRow; row++) {
                            for (let col = newMinCol; col <= newMaxCol; col++) {
                                this.highlightCell(row, col);
                            }
                        }
                    }
                    if (newMinRow < minRow) {
                        for (let row = newMinRow; row < minRow; row++) {
                            for (let col = newMinCol; col <= newMaxCol; col++) {
                                this.highlightCell(row, col);
                            }
                        }
                    }

                    this.dragStop = {
                        row: newStopRow,
                        col: newStopCol
                    }
                },

                clearHighlighted(startRow, startCol) {
                    if (!this.dragStop) return;

                    let endRow = this.dragStop.row;
                    let endCol = this.dragStop.col;

                    const minRow = Math.min(startRow, endRow);
                    const maxRow = Math.max(startRow, endRow);
                    const minCol = Math.min(startCol, endCol);
                    const maxCol = Math.max(startCol, endCol);

                    for (let row = minRow; row <= maxRow; row++) {
                        for (let col = minCol; col <= maxCol; col++) {
                            this.unhighlightCell(row, col);
                        }
                    }

                    this.selected = false;
                    this.dragStart = null;
                    this.multipleDrag = false;
                    this.dragStop = null;
                },

                highlightSelected() {
                    let startRow = this.dragStart.row;
                    let endRow = this.dragStop.row;

                    let startCol = this.dragStart.col;
                    let endCol = this.dragStop.col;

                    const minRow = Math.min(startRow, endRow);
                    const maxRow = Math.max(startRow, endRow);
                    const minCol = Math.min(startCol, endCol);
                    const maxCol = Math.max(startCol, endCol);

                    for (let row = minRow; row <= maxRow; row++) {
                        for (let col = minCol; col <= maxCol; col++) {
                            this.highlightCell(row, col);
                        }
                    }

                    this.selected = true;
                },

                updateDragPreview(lastRow) {
                    const start = this.dragStart.row;
                    let col = this.dragStart.col;
                    const prev = this.dragLastRow;

                    if (lastRow === prev) return;

                    const [minNew, maxNew] = start < lastRow ? [start + 1, lastRow] : [lastRow, start - 1];
                    const [minOld, maxOld] = start < prev ? [start + 1, prev] : [prev, start - 1];

                    const toUnselect = [];
                    const toSelect = [];

                    if (maxOld < minNew || maxNew < minOld) {
                        for (let i = minOld; i <= maxOld; i++) toUnselect.push(i);
                        for (let i = minNew; i <= maxNew; i++) toSelect.push(i);
                    } else {
                        if (minOld < minNew) {
                            for (let i = minOld; i < minNew; i++) toUnselect.push(i);
                        }
                        if (maxOld > maxNew) {
                            for (let i = maxNew + 1; i <= maxOld; i++) toUnselect.push(i);
                        }
                        if (minNew < minOld) {
                            for (let i = minNew; i < minOld; i++) toSelect.push(i);
                        }
                        if (maxNew > maxOld) {
                            for (let i = maxOld + 1; i <= maxNew; i++) toSelect.push(i);
                        }
                    }


                    toUnselect.forEach(row => {
                        const cell = this.$el.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                        if (cell) {
                            cell.classList.remove('bg-violet-200', 'dark:bg-cherry-900');
                        }
                    });

                    toSelect.forEach(row => {
                        const cell = this.$el.querySelector(`[data-row="${row}"][data-col="${col}"]`);
                        if (cell) {
                            cell.classList.add('bg-violet-200', 'dark:bg-cherry-900');
                        }
                    });

                    this.dragLastRow = lastRow;
                },

                handleArrowKeySelection(key) {
                    const maxRow = this.initialData.length - 1;
                    const maxCol = this.fltColumns.length - 1;

                    if (!['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight'].includes(key)) return;

                    let startRow = this.dragStop ? this.dragStop.row : this.activeRow;
                    let startCol = this.dragStop ? this.dragStop.col : this.activeCol;

                    let nextRow = startRow;
                    let nextCol = startCol;

                    if (key === 'ArrowDown') nextRow++;
                    if (key === 'ArrowUp') nextRow--;
                    if (key === 'ArrowRight') nextCol++;
                    if (key === 'ArrowLeft') nextCol--;

                    nextRow = Math.max(0, Math.min(maxRow, nextRow));
                    nextCol = Math.max(0, Math.min(maxCol, nextCol));

                    if (this.activeRow !== null && this.activeCol !== null) {
                        this.updateSelectionDelta(nextRow, nextCol);
                    }

                    this.dragStop = { row: nextRow, col: nextCol };
                },

                focusActiveCell() {
                    this.$nextTick(() => {
                        const cell = this.$el.querySelector(
                            `[data-row="${this.activeRow}"][data-col="${this.activeCol}"]`);
                        cell?.focus();
                    });
                },

                enableShiftSelection() {
                    if (this.selecting) return;

                    const keyUpEvent = (event) => {
                        if (event.key === 'Shift') {
                            this.selecting = false;
                            this.dir = null;
                            this.count = 0;
                            document.removeEventListener('keyup', keyUpEvent);
                        }
                    };

                    this.selecting = true;

                    this.dragStart = {
                        row: this.activeRow,
                        col: this.activeCol
                    };

                    this.dragStop = {
                        row: this.activeRow,
                        col: this.activeCol
                    };

                    this.highlightCell(this.activeRow, this.activeCol);
                    document.addEventListener('keyup', keyUpEvent);
                },

                registerGlobalEvents() {
                    this.$emitter.on('spreadsheet-cell-selected', this.onCellSelected);
                },

                handleClickOutside(event) {
                    if (!this.$el.contains(event.target)) {
                        if (this.activeCellInstance?.isInputFocused) {
                            const input = this.activeCellInstance.$refs.component?.$refs?.input;
                            if (input) input.blur();
                            this.activeCellInstance.isInputFocused = false;
                        }
                        this.activeRow = null;
                        this.activeCol = null;
                        this.instance = null;
                    }
                },
            },
        });
    </script>
@endPushOnce

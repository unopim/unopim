@pushOnce('scripts')

<script type="text/x-template" id="v-spreadsheet-cell-template">
    <td
        tabindex="0"
        class="relative text-sm border-box whitespace-nowrap focus:outline-none"
        :class="{ 'border border-gray-600 dark:border-white': isActive || isSelected }"
        :data-row="rowId"
        :data-col="colId"
        @click="onClick"
        @dblclick="onDoubleClick"
        @focus="onFocus"
        @blur="onBlur"
        @mouseenter="onHover"
        @mousedown.prevent="onSelectStart"
    >
        <component
            ref="component"
            :is="getComponentType(col.type)"
            @focus="onInputFocus"
            :isActive="isActive"
            v-model="internalValue"
            :column="col"
            :entityId="entityId"
            :attribute="attribute"
        />

        <div
            v-if="isActive"
            class="absolute bottom-[-3px] right-[-2px] w-[9px] h-[9px] dark:bg-white bg-cherry-800 cursor-crosshair z-10"
            @mousedown.stop.prevent="onDragHandleDown($event)"
        ></div>
    </td>
</script>

<script type="module">
    app.component('v-spreadsheet-cell', {
        template: '#v-spreadsheet-cell-template',

        props: {
            value: { default: null },
            rowId: Number,
            colId: [String, Number],
            entityId: Number,
            col: Object,
            attribute: Object,
        },

        data() {
            return {
                isSelected: false,
                isActive: false,
                internalValue: this.value,
                isInputFocused: false,
                valuePasted: false,
            };
        },

        inject: ['gridContext'],

        mounted() {
            const pasteKey = `spreadsheet-cell-paste-${this.rowId}-${this.colId}`;
            const getValueKey = `spreadsheet-cell-value-${this.rowId}-${this.colId}`;
            this.gridContext.$emitter.on(getValueKey, this.copyValue);
            this.gridContext.$emitter.on(pasteKey, this.onPasteFromGrid);
        },

        beforeUnmount() {
            this.removeKeyDownListener();
        },

        methods: {
            copyValue() {
                const key = `${this.rowId}-${this.colId}`;
                this.gridContext.valuesCopied[key] = this.internalValue ?? '';
            },

            onSelectStart(e) {
                const onMouseUp = () => {
                    this.gridContext.selecting = false;

                    if (!this.gridContext.multipleDrag) return;

                    document.removeEventListener('mouseup', onMouseUp);
                    this.gridContext.multipleDrag = false;

                    if (!this.gridContext.dragStop) {

                        this.gridContext.dragStart = null;
                        return;
                    }

                    const { row, col } = this.gridContext.dragStart;

                    const onClickOutside = () => {
                        this.gridContext.clearHighlighted(row, col);
                        window.removeEventListener('mousedown', onClickOutside);
                    };

                    setTimeout(() => window.addEventListener('mousedown', onClickOutside), 0);
                };

                document.addEventListener('mouseup', onMouseUp);

                if (this.gridContext.selected) {
                    this.gridContext.clearHighlighted(this.gridContext.dragStart.row, this.gridContext.dragStart.col);
                }

                if (this.gridContext.dragStop) {
                    this.gridContext.clearHighlighted(this.gridContext.dragStart.row, this.gridContext.dragStart.col);
                }

                this.gridContext.dragStart = {
                    row: this.rowId,
                    col: this.colId,
                    value: this.internalValue,
                };

                this.gridContext.selecting = true;
            },

            onFocus() {
                if (this.isInputFocused) return;
                if (this.gridContext.selecting) {
                    if (this.gridContext.activeCol !== this.colId) return;

                    this.isSelected = true;

                    return;
                }

                this.isActive = true;
                this.addKeyDownListener();

                this.$emitter.emit('spreadsheet-cell-selected', { instance: this });

                if (this.gridContext.suppressNextFocus) {
                    this.gridContext.suppressNextFocus = false;
                    return;
                }
            },

            addKeyDownListener() {
                if (this._keyDownHandler) return;
                this._keyDownHandler = (e) => {
                    if (
                        e.key === 'Tab' ||
                        e.key.startsWith('Arrow') ||
                        e.metaKey || e.ctrlKey || e.altKey || e.shiftKey
                    ) return;
                    const input = this.$refs.component?.$refs?.input;
                    if (input && typeof input.focus === 'function') input.focus();
                };
                document.addEventListener('keydown', this._keyDownHandler);
            },

            removeKeyDownListener() {
                if (this._keyDownHandler) {
                    document.removeEventListener('keydown', this._keyDownHandler);
                    this._keyDownHandler = null;
                }
            },

            onClick() {
                if (this.gridContext.selecting || this.isInputFocused) return;

                if (
                    this.gridContext.activeCellInstance &&
                    this.gridContext.activeCellInstance !== this
                ) {
                    this.gridContext.activeCellInstance.deactivateCell();
                }

                this.isActive = true;
                this.gridContext.activeCellInstance = this;
                this.addKeyDownListener();

                this.$emitter.emit('spreadsheet-cell-selected', { instance: this });
            },

            deactivateCell() {
                this.isActive = false;
                this.isSelected = false;

                if (this.isInputFocused) {
                    this.isInputFocused = false;
                    const input = this.$refs.component?.$refs?.input;
                    if (input && typeof input.blur === 'function') input.blur();
                }
                this.removeKeyDownListener();
            },

            onDoubleClick() {
                if (!this.isInputFocused) {
                    this.isInputFocused = true;

                    const input = this.$refs.component?.$refs?.input;
                    if (input && typeof input.focus === 'function') {
                        input.focus();
                        console.log("input double click")
                        if (this.keyDownListener) {
                            document.removeEventListener('keydown', this.keyDownListener);
                            this.keyDownListener = null;
                        }
                    }
                }
            },

            onPasteFromGrid(value) {
                this.updateValue(value);
            },

            onBlur(e) {
                this.removeKeyDownListener();
                if (this.gridContext.selecting || this.gridContext.dragStart) return;
                this.isSelected = false;
                this.isInputFocused = false;
                this.isActive = false;
            },

            updateValue(value) {
                this.internalValue = value;

                this.$emitter.emit('update-spreadsheet-data', {
                    value: value,
                    entityId: this.entityId,
                    column: this.col,
                });
            },

            onHighlightChange(highlight) {
                this.isSelected = highlight;
            },

            onInputFocus() {
                if (!this.keyDownListener) return;

                this.isInputFocused = true;
                this.isSelected = true;

                if (!this.valuePasted) {
                    this.isActive = false;
                    this.$emitter.emit('spreadsheet-cell-selected', { instance: null });
                } else {
                    this.valuePasted = false;
                }
            },

            getComponentType(type) {
                switch (type) {
                    case 'textarea': return 'v-spreadsheet-textarea';
                    case 'text': return 'v-spreadsheet-text';
                    case 'select': return 'v-spreadsheet-select';
                    case 'checkbox':
                    case 'multiselect': return 'v-spreadsheet-multiselect';
                    case 'date': return 'v-spreadsheet-date';
                    case 'boolean': return 'v-spreadsheet-boolean';
                    case 'image': return 'v-spreadsheet-image';
                    case 'gallery': return 'v-spreadsheet-gallery';
                    default: return 'v-spreadsheet-text';
                }
            },

            onDragHandleDown(e) {
                this.gridContext.dragStart = {
                    row: this.rowId,
                    col: this.colId,
                    value: this.internalValue,
                };

                this.gridContext.selecting = true;
                this.gridContext.valueCopied = this.internalValue;
                this.gridContext.dragLastRow = this.rowId;

                const onMouseUp = () => {
                    this.gridContext.selecting = false;
                    
                    this.gridContext.finalizeDragFill(
                        this.gridContext.dragStart.row,
                        this.gridContext.dragLastRow,
                        this.colId
                    );
                    
                    this.gridContext.dragStart = null;
                    this.gridContext.dragLastRow = null;
                    document.removeEventListener('mouseup', onMouseUp);
                };

                document.addEventListener('mouseup', onMouseUp);
            },

            onHover() {
                if (!this.gridContext.selecting) return;

                if (this.gridContext.dragLastRow !== null || this.gridContext.dragLastCol !== null) {
                    this.gridContext.updateDragPreview(this.rowId);
                } else if (
                    this.gridContext.dragStart.row !== this.rowId ||
                    this.gridContext.dragStart.col !== this.colId
                ) {
                    this.gridContext.selecting = true;
                    this.gridContext.multipleDrag = true;
                    this.gridContext.updateSelectionDelta(this.rowId, this.colId);
                }
            },
        },
    });
</script>

@endPushOnce

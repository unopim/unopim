@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-datetime-template">
    <div class="w-full h-full relative">
      <input
        ref="nativeUndoInput"
        type="text"
        :name="`${entityId}_${column.code}`"
        class="absolute opacity-0 w-0 h-0 pointer-events-none"
        @input="updateFromNative"
      />

      <input
        ref="input"
        type="datetime-local"
        :name="`${entityId}_${column.code}`"
        v-bind="field"
        :value="modelValue"
        @input="onInput"
        class="w-full py-2.5 px-3 text-sm bg-transparent text-gray-600 dark:text-gray-300 transition-all focus:border-gray-400 dark:focus:border-gray-400"
        autocomplete="off"
      />
    </div>
  </script>

  <script type="module">
    app.component('v-spreadsheet-datetime', {
      template: '#v-spreadsheet-datetime-template',

      props: {
        isActive: Boolean,
        modelValue: String,
        entityId: Number,
        column: Object,
        attribute: Object,
      },

      watch: {
        modelValue(newVal) {
          const inputEl = this.$refs.input;
          if (inputEl && inputEl.value !== newVal) {
            inputEl.value = newVal ?? '';
            this.$refs.nativeUndoInput.value = newVal ?? '';
          }

          if (!this.isUpdated) {
            this.$emitter?.emit('update-spreadsheet-data', {
              value: newVal,
              entityId: this.entityId,
              column: this.column,
            });
          }
        },
      },

      data () {
        return {
          isUpdated: false
        }
      },

      mounted() {
        const value = this.modelValue ?? '';
        if (this.$refs.input) {
          this.$refs.input.value = value;
        }
        if (this.$refs.nativeUndoInput) {
          this.$refs.nativeUndoInput.value = value;
        }
      },

      methods: {
        onInput(event) {
          const value = event.target.value;
          this.emitChange(value);
        },

        updateFromNative() {
          const value = this.$refs.nativeUndoInput.value;

          if (this.$refs.input) {
            this.$refs.input.value = value;
          }

          this.emitChange(value);
        },

        emitChange(value) {
          // Validate datetime-local format when a value is present.
          if (value && isNaN(Date.parse(value))) {
            this.$emitter?.emit('add-flash', {
              type: 'error',
              message: "@lang('admin::app.catalog.products.bulk-edit.invalid-datetime')",
            });
            return;
          }

          this.isUpdated = true;
          this.$emit('update:modelValue', value);
          this.$emitter?.emit('update-spreadsheet-data', {
            value,
            entityId: this.entityId,
            column: this.column,
          });
        },
      },
    });
  </script>
@endPushOnce

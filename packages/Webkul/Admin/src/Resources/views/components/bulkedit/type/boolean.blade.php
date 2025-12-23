@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-boolean-template">
    <div class="w-full h-full flex items-center justify-center">
      <input
        ref="nativeUndoInput"
        type="text"
        :name="`${entityId}_${column.code}`"
        @input="update"
        style="opacity: 0; height: 0; width: 0;"
        class="bg-violet-700"
      />
      <button
        type="button"
        :class="[
          'relative inline-flex items-center h-6 rounded-full w-11 transition-colors duration-200',
          isChecked ? 'bg-violet-700' : 'bg-gray-300 dark:bg-gray-600'
        ]"
        @click="toggle"
      >
        <span
          :class="[
            'inline-block w-4 h-4 transform bg-white dark:bg-gray-100 rounded-full shadow transition-transform duration-200',
            isChecked ? 'translate-x-6' : 'translate-x-1'
          ]"
        ></span>
      </button>
    </div>
  </script>

  <script type="module">
    app.component('v-spreadsheet-boolean', {
      template: '#v-spreadsheet-boolean-template',

      props: {
        isActive: Boolean,
        modelValue: {
          type: [Boolean, Number, String],
          default: false,
        },
        entityId: Number,
        column: Object,
        attribute: Object
      },

      computed: {
        isChecked() {
          return this.modelValue === "true" || this.modelValue === true;
        }
      },

      data () {
        return {
          isUpdated: false
        }
      },

      mounted() {
        if (this.$refs.nativeUndoInput) this.$refs.nativeUndoInput.value = this.modelValue;
      },

      watch: {
        modelValue(newVal) {
          if (newVal === this.$refs.nativeUndoInput.value) {
              return;
          }

          this.$refs.nativeUndoInput.focus();
          this.$refs.nativeUndoInput.select();
          document.execCommand('insertText', false, newVal);

          if (! this.isUpdated) {
              this.$emitter.emit('update-spreadsheet-data', {
                  value: newVal,
                  entityId: this.entityId,
                  column: this.column,
          });
          }
        },
      },

      methods: {
        toggle() {
          const newValue = this.isChecked ? "false" : "true";

          this.$emit('update:modelValue', newValue);

          this.$emitter.emit('update-spreadsheet-data', {
            value: newValue,
            entityId: this.entityId,
            column: this.column,
          });
        },

        update() {
          const inputValue = this.$refs.nativeUndoInput.value;
          const modelValue = this.isChecked ? "true" : "false";

          if (modelValue === inputValue) {
            return;
          }

          this.isUpdated = true;
          this.$emit('update:modelValue', inputValue);
          this.$emitter.emit('update-spreadsheet-data', {
            value: inputValue,
            entityId: this.entityId,
            column: this.column,
          });
        }
      }
    });
  </script>
@endPushOnce

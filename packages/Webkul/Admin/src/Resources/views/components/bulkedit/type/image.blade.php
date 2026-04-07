@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-image-template">
    <div class="w-full h-full flex items-center gap-1.5 px-1">
      <!-- Thumbnail preview -->
      <div v-if="modelValue" class="flex-shrink-0 w-6 h-6 rounded overflow-hidden border border-gray-200 dark:border-cherry-700">
        <img
          :src="imageUrl"
          class="w-full h-full object-cover"
          v-on:error="$event.target.style.display='none'"
        />
      </div>

      <input
        ref="input"
        type="text"
        :name="`${entityId}_${column.code}`"
        v-bind="field"
        class="flex-1 min-w-0 text-xs text-gray-600 dark:text-gray-300 bg-transparent truncate focus:outline-none"
        readonly
      />

      <div class="flex items-center gap-0.5 flex-shrink-0">
        <span
          v-if="modelValue"
          @click="preview"
          class="cursor-pointer text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 text-base icon-view"
        ></span>

        <span
          @click="triggerUpload"
          class="cursor-pointer text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 text-base icon-edit"
        ></span>

        <span
          v-if="modelValue"
          @click="removeImage"
          class="cursor-pointer text-gray-400 hover:text-red-500 text-base icon-delete"
        ></span>
      </div>

      <input
        type="file"
        ref="fileInput"
        class="hidden"
        accept="image/*"
        @change="onFileChange"
      />

      <!-- Preview handled by editor-level overlay via emitter -->
    </div>
  </script>

  <script type="module">
    app.component('v-spreadsheet-image', {
      template: '#v-spreadsheet-image-template',

      props: {
        isActive: Boolean,
        modelValue: String,
        entityId: Number,
        column: Object,
        attribute: Object,
      },

      data() {
        return {
          baseUrl: "{{ Storage::url('') }}",
          isUpdated: false,
        }
      },

      computed: {
        imageUrl() {
          return this.modelValue ? this.baseUrl + this.modelValue : '';
        },
      },

      watch: {
        modelValue(newVal) {
          if (this.$refs.input && newVal !== this.$refs.input.value) {
            this.$refs.input.value = newVal ? this.getFileName(newVal) : '';
          }

          if (! this.isUpdated) {
            this.$emitter.emit('update-spreadsheet-data', {
              value: newVal,
              entityId: this.entityId,
              column: this.column,
            });
          }
        },
      },

      mounted() {
        if (this.$refs.input) {
          this.$refs.input.value = this.modelValue ? this.getFileName(this.modelValue) : '';
        }
      },

      methods: {
        getFileName(path) {
          if (! path) return '';

          const parts = path.split('/');

          return parts[parts.length - 1] || path;
        },

        triggerUpload() {
          this.$refs.fileInput.click();
        },

        async onFileChange(event) {
          const file = event.target.files[0];
          if (! file) return;

          const formData = new FormData();
          formData.append('file', file);
          formData.append('sku', this.entityId);
          formData.append('attribute', this.column.code);

          this.$axios.post("{{ route('admin.catalog.products.bulk-edit.save-media') }}", formData)
            .then(response => {
              const data = response.data.data;
              if (data.filePath) {
                this.emitUpdate(data.filePath);
              }
            })
            .catch(error => {
              this.$emitter.emit('add-flash', { type: 'warning', message: error });
            });
        },

        removeImage() {
          this.emitUpdate('');
        },

        preview() {
          if (this.imageUrl) {
            this.$emitter.emit('preview-image', this.imageUrl);
          }
        },

        emitUpdate(value) {
          this.isUpdated = true;
          this.$emit('update:modelValue', value);
          this.$emitter.emit('update-spreadsheet-data', {
            value,
            entityId: this.entityId,
            column: this.column,
          });
        },

        updateValue(val) {
          this.emitUpdate(val);
        },
      },
    });
  </script>
@endPushOnce

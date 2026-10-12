@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-image-template">
    <div class="w-full h-full flex items-center gap-1.5 px-1">
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
        class="flex-1 min-w-0 text-xs text-gray-600 dark:text-gray-300 bg-transparent truncate focus:outline-none"
        readonly
      />

      <div v-if="! locked" class="flex items-center gap-0.5 flex-shrink-0">
        <span
          v-if="modelValue"
          @click="preview"
          class="cursor-pointer text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 text-base icon-view"
        ></span>

        <span
          @click="triggerUpload"
          class="cursor-pointer text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 text-base icon-edit"
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
        :accept="acceptedTypes"
        @change="onFileChange"
      />

    </div>
  </script>

  <script type="module">
    const mediaCell = {
      template: '#v-spreadsheet-image-template',

      props: {
        isActive: Boolean,
        modelValue: String,
        entityId: Number,
        column: Object,
        attribute: Object,
        locked: Boolean,
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

        acceptedTypes() {
          const extensions = this.attribute?.allowed_extensions;

          const list = Array.isArray(extensions)
            ? extensions
            : (typeof extensions === 'string' ? extensions.split(',') : []);

          if (list.length) {
            return list
              .map(extension => '.' + String(extension).trim().replace(/^\./, ''))
              .join(',');
          }

          return this.column?.type === 'image' ? 'image/*' : '';
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
              this.$emitter.emit('add-flash', {
                type: 'warning',
                message: error?.response?.data?.message || @json(trans('admin::app.catalog.products.bulk-edit.img-fail')),
              });
            });
        },

        removeImage() {
          this.emitUpdate('');
        },

        preview() {
          if (this.imageUrl) {
            this.$emitter.emit('preview-image', {
              url: this.imageUrl,
              fileName: this.getFileName(this.modelValue),
            });
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
    };

    app.component('v-spreadsheet-image', mediaCell);
    app.component('v-spreadsheet-file', mediaCell);
  </script>
@endPushOnce

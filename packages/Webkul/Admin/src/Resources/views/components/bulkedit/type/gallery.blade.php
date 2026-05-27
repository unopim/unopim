@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-gallery-template">
    <div class="w-full h-full flex items-center gap-1.5 px-1">
      <!-- Thumbnail count -->
      <div v-if="imageList.length" class="flex-shrink-0 flex items-center gap-0.5">
        <div class="w-6 h-6 rounded overflow-hidden border border-gray-200 dark:border-cherry-700">
          <img
            :src="baseUrl + imageList[0]"
            class="w-full h-full object-cover"
            v-on:error="$event.target.style.display='none'"
          />
        </div>
        <span v-if="imageList.length > 1" class="text-xs text-gray-400">+@{{ imageList.length - 1 }}</span>
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
          v-if="imageList.length"
          @click="preview"
          class="cursor-pointer text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 text-base icon-view"
        ></span>

        <span
          @click="triggerUpload"
          class="cursor-pointer text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 text-base icon-edit"
        ></span>
      </div>

      <input
        type="file"
        ref="fileInput"
        class="hidden"
        accept="image/*, video/*"
        multiple
        @change="onFileChange"
      />

      <!-- Preview handled by editor-level overlay via emitter -->
    </div>
  </script>

  <script type="module">
    app.component('v-spreadsheet-gallery', {
      template: '#v-spreadsheet-gallery-template',

      props: {
        isActive: Boolean,
        modelValue: Array,
        entityId: Number,
        column: Object,
        attribute: Object
      },

      data() {
        return {
          baseUrl: "{{ Storage::url('') }}",
          imageList: [],
          isUpdated: false,
        };
      },

      mounted() {
        if (this.$refs.input) {
          this.$refs.input.value = this.modelValue
            ? (Array.isArray(this.modelValue) ? this.modelValue.length + ' image(s)' : '')
            : '';
        }

        if (this.modelValue) {
          this.imageList = Array.isArray(this.modelValue) ? this.modelValue : [];
        }
      },

      watch: {
        modelValue(newVal) {
          if (Array.isArray(newVal)) {
            this.imageList = newVal;
          } else if (typeof newVal === 'string' && newVal) {
            this.imageList = newVal.split(',').map(i => i.trim()).filter(Boolean);
          } else {
            this.imageList = [];
          }

          if (this.$refs.input) {
            this.$refs.input.value = this.imageList.length ? this.imageList.length + ' image(s)' : '';
          }

          if (! this.isUpdated) {
            this.$emitter.emit('update-spreadsheet-data', {
              value: this.imageList,
              entityId: this.entityId,
              column: this.column,
            });
          }
        },
      },

      methods: {
        triggerUpload() {
          this.$refs.fileInput.click();
        },

        onFileChange(event) {
          const files = Array.from(event.target.files);
          if (! files.length) return;

          const formData = new FormData();
          files.forEach(file => {
            formData.append('file[]', file);
          });
          formData.append('sku', this.entityId);
          formData.append('attribute', this.column.code);

          this.$axios.post("{{ route('admin.catalog.products.bulk-edit.save-media') }}", formData)
            .then(response => {
              const filePathStr = response.data?.data?.filePath || '';
              const newImages = filePathStr.split(',').map(p => p.trim()).filter(Boolean);
              this.imageList = [...this.imageList, ...newImages];
              this.commitChanges();
            })
            .catch(error => {
              console.error('Upload error:', error);
              this.$emitter.emit('add-flash', {
                type: 'warning',
                message: error?.response?.data?.message || "@lang('admin::app.catalog.products.bulk-edit.img-fail')",
              });
            });
        },

        removeImage(index) {
          this.imageList.splice(index, 1);
          this.commitChanges();

          if (this.imageList.length === 0) {
          }
        },

        preview() {
          if (this.imageList.length) {
            this.$emitter.emit('preview-image', this.baseUrl + this.imageList[0]);
          }
        },

        commitChanges() {
          this.isUpdated = true;
          this.$emit('update:modelValue', this.imageList.join(','));

          this.$emitter.emit('update-spreadsheet-data', {
            value: this.imageList,
            entityId: this.entityId,
            column: this.column,
          });
        },

        isVideo(filePath) {
          return /\.(mp4|webm|mkv)(\?.*)?$/i.test(filePath || '');
        },

        updateValue(val) {
          this.imageList = val ? String(val).split(',').map(i => i.trim()).filter(Boolean) : [];
          this.commitChanges();
        },
      },
    });
  </script>
@endPushOnce

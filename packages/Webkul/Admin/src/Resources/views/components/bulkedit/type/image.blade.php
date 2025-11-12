@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-image-template">
    <div class="w-full h-full flex items-center gap-2">
      <input
        ref="input"
        type="text"
        :name="`${entityId}_${column.code}`"
        v-bind="field"
        class="w-full text-sm text-gray-600 dark:text-gray-300 transition-all  focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600"
        @blur="update"
      />

      <span @click="preview" class="flex justify-end cursor-pointer icon-view"></span>
      <span @click="triggerUpload" class="flex justify-end cursor-pointer icon-edit "></span>

      <input
        type="file"
        ref="fileInput"
        class="hidden"
        accept="image/*"
        @change="onFileChange"
      />
    </div>

    <x-admin::modal ref="imagePreviewModal">
      <x-slot:header>
        <p class="text-lg text-gray-800 dark:text-white font-bold">
          @lang('admin::app.catalog.products.bulk-edit.img-preview')</p>
      </x-slot>

      <x-slot:content>
        <div v-if="imageUrl" class="relative max-w-full h-[260px] group">
          <img
            :src="imageUrl"
            class="w-full h-full object-contain object-top rounded"
          />
          <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
            <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>
            <div
              class="absolute top-2 right-0 bg-white dark:bg-cherry-800 bg-opacity-80 rounded p-1 transition-all"
            >
              <span
                class="icon-delete text-xl p-2 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                @click="removeImage"
              ></span>
            </div>
          </div>
        </div>
        <div v-else class="h-32 flex items-center justify-center text-gray-500 dark:text-gray-300 text-sm">
          @lang('admin::app.catalog.products.bulk-edit.no-image')
        </div>
      </x-slot>
    </x-admin::modal>
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
          if (newVal === this.$refs.input.value) {
              return;
          }

          this.$refs.input.focus();
          this.$refs.input.select();
          document.execCommand('insertText', false, newVal);

          if (!this.isUpdated) {
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
          this.$refs.input.value = this.modelValue || "@lang('admin::app.catalog.products.bulk-edit.no-image')";
        }
      },

      methods: {
        triggerUpload() {
          this.$refs.fileInput.click();
        },

        async onFileChange(event) {
          const file = event.target.files[0];
          if (!file) return;

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

        update() {
          const updateValue = this.$refs.input.value;
          this.emitUpdate(updateValue);
        },

        removeImage() {
          this.emitUpdate('');
          this.$refs.imagePreviewModal.close();
        },

        preview() {
          this.$refs.imagePreviewModal.toggle();
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
      },
    });
  </script>
@endPushOnce

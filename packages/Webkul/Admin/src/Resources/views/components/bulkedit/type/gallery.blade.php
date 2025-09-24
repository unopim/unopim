@pushOnce('scripts')
<script type="text/x-template" id="v-spreadsheet-gallery-template">
  <div class="w-full h-full flex items-center gap-2">
    <input
      ref="input"
      type="text"
      :name="`${entityId}_${column.code}`"
      v-bind="field"
      class="w-full text-sm text-gray-600 dark:text-gray-300 transition-all focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600"
      @blur="update"
    />

    <span @click="preview" class="flex justify-end cursor-pointer icon-view"></span>
    <span @click="triggerUpload" class="flex justify-end cursor-pointer icon-edit"></span>

    <input
      type="file"
      ref="fileInput"
      class="hidden"
      accept="image/*"
      multiple
      @change="onFileChange"
    />
  </div>

  <x-admin::modal ref="imagePreviewModal">
    <x-slot:header>
      <p class="text-lg text-gray-800 dark:text-white font-bold">@lang('admin::app.catalog.products.bulk-edit.gallery-preview')</p>
    </x-slot>
   <x-slot:content>
  <div v-if="imageList.length" class="grid grid-cols-3 gap-4 max-h-[260px] overflow-auto">
    <div v-for="(img, index) in imageList" :key="index" class="relative group">
      <img :src="baseUrl + img" class="w-full h-24 object-cover rounded border" />
      <div class="flex flex-col justify-between invisible w-full p-3 bg-white dark:bg-cherry-800 absolute top-0 bottom-0 opacity-80 transition-all group-hover:visible">
        <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold break-all"></p>
 <div
            class="absolute inset-0 bg-white dark:bg-cherry-800 bg-opacity-80 rounded flex justify-end p-2 opacity-80 transition-all group-hover:visible"
          >
            <span
              class="icon-delete text-xl p-1 rounded-md cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
              @click="removeImage"
            ></span>
          </div>
      </div>
    </div>
  </div>

  <div v-else class="h-32 flex items-center justify-center text-gray-500 dark:text-gray-300 text-sm">
  @lang('admin::app.catalog.products.bulk-edit.no-image')  </div>
</x-slot>

  </x-admin::modal>
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
      this.$refs.input.value = this.modelValue || "@lang('admin::app.catalog.products.bulk-edit.no-image')";
    }
    if (this.modelValue) {
      this.imageList = this.modelValue;
    }

  },

  watch: {
    modelValue(newVal) {
        if (newVal === this.$refs.input.value) {
            return;
        }

        this.$refs.input.focus();
        this.$refs.input.select();

        document.execCommand('insertText', false, newVal);
        this.imageList = newVal.split(',');

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
      if (!files.length) return;

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
    },

    preview() {
      this.$refs.imagePreviewModal.toggle();
    },

    update() {
      this.imageList = (this.$refs.input.value || '').split(',').map(i => i.trim()).filter(Boolean);
      this.commitChanges();
    },

    commitChanges() {
      this.isUpdated = true;
      this.$emit('update:modelValue', this.imageList.join(','));

      this.$emitter.emit('update-spreadsheet-data', {
        value: this.imageList,
        entityId: this.entityId,
        column: this.column,
      });
    }
  },
});
</script>
@endPushOnce

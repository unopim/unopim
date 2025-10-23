@pushOnce('scripts')
<script type="text/x-template" id="v-spreadsheet-select-template">
  <div ref="trigger" class="w-full h-full relative">

  <input
    ref="nativeUndoInput"
    type="text"
    :name="`${entityId}_${column.code}`"
    @input="update"
    style="opacity: 0; height: 0; width: 0;"
    class="bg-cherry-900 bg-violet-300"
  />
    <input
      ref="input"
      :name="`${entityId}_${column.code}`"
      @focus="openDropdown"
      @dblclick="openDropdown"
      @input="onSearchInput"
      @blur="onBlur"
      type="text"
      class="w-full h-full px-1 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-300"
      :placeholder="'Select Option'"
    />

    <!-- Dropdown -->
      <div
        v-if="open"
      class="absolute left-0 top-full z-20 bg-white dark:bg-cherry-900 border border-gray-300 dark:border-gray-600 rounded shadow-lg h-[100px] w-full overflow-auto"
        @scroll.passive="onScroll"
      >
      <div
        v-if="options.length === 0"
        class="px-2 py-1 text-gray-500 dark:text-gray-300"
        >
          @lang('admin::app.catalog.products.bulk-edit.no-option')
      </div>

      <div
        v-for="(option, index) in options"
        :key="option[valueKey]"
        class="flex items-center justify-between px-2 py-1 hover:bg-gray-100 dark:hover:bg-cherry-700 cursor-pointer"
        :class="[
          { 'font-semibold text-violet-700': selectedOption === option[valueKey] },
          { 'bg-gray-100 dark:bg-cherry-700': highlightedIndex === index }
        ]"
        :title="option[labelKey]"
      >
        <span class="truncate w-full dark:text-white text-gray-600" @click.stop="selectOption(option)">
          @{{ option[labelKey] ? option[labelKey] : option[valueKey] }}
        </span>

        <button
          v-if="selectedOption === option[valueKey]"
          @click.stop.prevent="clearSelection"
          class="ml-2 text-red-500 hover:text-red-700"
          title="Clear selection"
        >
          &times;
        </button>
      </div>
      </div>

  </div>

</script>

<script type="module">
app.component('v-spreadsheet-select', {
  template: '#v-spreadsheet-select-template',

  props: {
    isActive: Boolean,
    modelValue: String,
    entityId: Number,
    column: Object,
    attribute: Object,

  },

  data() {
    return {
      open: false,
      loading: false,
      options: [],
      page: 1,
      selectedOption: null,
      hasMore: true,
      valueKey: 'code',
      labelKey: 'label',
      search: '',
      debounceTimeout: null,
      isUpdated: false,
      dropdownStyle: {
        top: '0px',
        left: '0px',
        width: '100px',
      },
      highlightedIndex: -1,
    };
  },

  computed: {
    routeUrl() {
      return "{{ route('admin.catalog.options.fetch-all') }}";
    },

    queryParams() {
      return {
        entityName: 'attribute',
        attributeId: this.attribute.id,
        page: this.page,
        query: this.search,
      };
    },
  },
   watch: {
      modelValue(newVal) {
          if (newVal === this.$refs.nativeUndoInput.value) {
              return;
          }

          const matchedOption = this.options.find(
            (opt) => opt.code === newVal || opt.label === newVal
          );
          
         if (!matchedOption && !this.isUpdated) {
            this.search = newVal;
            this.loadOptions(true);

            setTimeout(() => {
              const refreshedOption = this.options.find(
                (opt) => opt.code === newVal || opt.label === newVal
              );

              if (!refreshedOption) {
                console.warn('No matching option found for:', newVal);
                this.$emit('update:modelValue', this.selectedOption);
              } else {
                this.selectedOption = newVal;
                this.$refs.input.value = refreshedOption.label || refreshedOption.code;
              }
            }, 300);
            return;
          }

          this.selectedOption = newVal;
          this.$refs.input.value = matchedOption ? matchedOption.label : this.selectedOption;

          this.$refs.nativeUndoInput.focus();
          this.$refs.nativeUndoInput.select();
          document.execCommand('insertText', false, newVal);
      },
    },

  mounted() {
    this.selectedOption = this.modelValue;
    this.$refs.input.value = this.selectedOption;

    this.$refs.nativeUndoInput.value = this.modelValue ?? '';

    document.addEventListener('mousedown', this.handleClickOutside);
    this.$refs.input.addEventListener('keydown', this.handleKeyDown);

  },

  beforeUnmount() {
    document.removeEventListener('mousedown', this.handleClickOutside);
  },

  methods: {
    openDropdown() {
      if (this.search) {
        this.search = '';
        this.loadOptions(true);
      } else {
        this.loadOptions(false);
      }

      document.addEventListener('mousedown', this.handleClickOutside);

      this.open = true;
      this.setDropdownPosition();
    },

    setDropdownPosition() {
      if (this.$refs.trigger) {
        const rect = this.$refs.trigger.getBoundingClientRect();
        this.dropdownStyle = {
          top: `${rect.bottom + window.scrollY}px`,
          left: `${rect.left + window.scrollX}px`,
          width: `${rect.width}px`,
        };
      }
    },

    onBlur() {
      this.open = false
      if (this.$refs.input.value !== this.selectedOption) {
        this.$refs.input.value = this.selectedOption || '';
      }
    },

    loadOptions(reset = false) {
        if (this.loading || (!this.hasMore && !reset)) return;

        if (reset) {
            this.page = 1;
            this.options = [];
            this.hasMore = true;
        }

        this.loading = true;

        const url = new URL(this.routeUrl, window.location.origin);
        Object.entries(this.queryParams).forEach(([key, val]) => {
            if (val !== '') url.searchParams.append(key, val);
        });

        this.$axios
          .get(url.toString())
          .then((response) => {
              const data = response.data;

              if (!data.options || data.options.length === 0) {
                  this.hasMore = false;
              } else {
                  this.options.push(...data.options);
                  this.page++;
              }
          })
          .catch((err) => {
              console.error('Error fetching options:', err);
              this.hasMore = false;
          })
          .finally(() => {
              this.loading = false;
          });
    },

    onSearchInput() {
      this.search = this.$refs.input.value;
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.loadOptions(true);
      }, 500);
    },

    onScroll(e) {
      const el = e.target;
      if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
        this.loadOptions();
      }
    },

    selectOption(option) {
      this.$refs.input.value =
        option[this.labelKey] && option[this.labelKey].trim() !== ''
          ? option[this.labelKey]
          : option[this.valueKey];
      this.$refs.nativeUndoInput.focus();
      this.$refs.nativeUndoInput.select();
      document.execCommand('insertText', false, option[this.valueKey]);

      this.open = false;
    },

    clearSelection() {
      this.$refs.input.value = '';
      this.selectedOption = '';
      this.$refs.nativeUndoInput.focus();
      this.$refs.nativeUndoInput.select();
      document.execCommand('insertText', false, '');

      this.$emitter.emit('update-spreadsheet-data', {
        value: '',
        entityId: this.entityId,
        column: this.column,
      });
    },

    update() {
      if (this.selectedOption === this.$refs.nativeUndoInput.value) return;

      let updateValue = this.$refs.nativeUndoInput.value;
      if (updateValue === this.selectedOption) return;
      this.selectedOption = updateValue;

      const matchedOption = this.options.find(
          (opt) => opt.code === this.selectedOption
      );

      this.$refs.input.value = matchedOption? (matchedOption.label || matchedOption.code) : this.selectedOption;
      this.isUpdated = true;
      this.$emit('update:modelValue', updateValue);
      this.$emitter.emit('update-spreadsheet-data', {
        value: updateValue,
        entityId: this.entityId,
        column: this.column,
      });

      this.$nextTick(() => {
        this.$refs.input.focus();
      });
    },

    handleClickOutside(event) {
      if (!this.$refs.trigger.contains(event.target)) {
        this.open = false;
        document.removeEventListener('mousedown', this.handleClickOutside);
      }
    },

    handleKeyDown(e) {
        if (!this.open) return;

        const maxIndex = this.options.length - 1;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.highlightedIndex = Math.min(this.highlightedIndex + 1, maxIndex);
            this.scrollToHighlighted();
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
            this.scrollToHighlighted();
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            if (this.highlightedIndex >= 0 && this.highlightedIndex <= maxIndex) {
                const option = this.options[this.highlightedIndex];
                this.selectOption(option);
            }
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            this.open = false;
            this.highlightedIndex = -1;
        }
    },

    scrollToHighlighted() {
        this.$nextTick(() => {
            const list = this.$el.querySelector('.absolute');
            const option = list?.children[this.highlightedIndex];
            if (option) {
                const optionTop = option.offsetTop;
                const optionBottom = optionTop + option.offsetHeight;
                if (optionTop < list.scrollTop) list.scrollTop = optionTop;
                if (optionBottom > list.scrollTop + list.clientHeight) {
                    list.scrollTop = optionBottom - list.clientHeight;
                }
            }
        });
    },
  },
});
</script>

@endPushOnce

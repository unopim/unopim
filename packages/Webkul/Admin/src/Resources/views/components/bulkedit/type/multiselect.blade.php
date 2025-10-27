@pushOnce('scripts')
  <script type="text/x-template" id="v-spreadsheet-multiselect-template">
    <div ref="trigger" class="w-full h-full relative">
      <div class="overflow-hidden w-full flex flex-wrap items-center gap-1"   @dblclick="openDropdown"
      >
        <input
          ref="nativeUndoInput"
          type="text"
          :name="`${entityId}_${column.code}`"
          style="opacity: 0; height: 0; width: 0;"
          @input="update"
          autocomplete="off"
        />
        <template v-if="selectedOptions.length > 0">
          <span
            v-for="option in selectedOptions"
            :key="option[valueKey]"
            class="inline-flex items-center gap-1 max-w-full truncate bg-violet-100 dark:bg-cherry-700 text-violet-700 dark:text-white px-2 py-0.5 rounded"
          >
            @{{ option[labelKey] || option[valueKey] }}
            <button
              @click.stop.prevent="removeOption(option[valueKey])"
              class="text-red-600 hover:text-red-800 font-bold"
            >
              ×
            </button>
          </span>
        </template>
        <input
          ref="input"
          type="text"
          class="w-full h-full px-1 py-2 text-sm bg-transparent text-gray-700 dark:text-gray-300"        :placeholder="selectedOptions.length === 0 ? 'Select Options' : ''"
          @input="onSearchInput"
          @blur="onBlur"
          @focus="openDropdown"
          autocomplete="off"
        />
      </div>

      <div
        v-if="open"
        class="absolute left-0 top-full z-50 bg-white dark:bg-cherry-900 border border-gray-300 dark:border-gray-600 rounded shadow-lg h-[150px] w-full overflow-auto"
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
            selectedOptions.some(o => o[valueKey] === option[valueKey]) ? 'font-semibold text-violet-700' : '',
            index === highlightedIndex ? 'font-semibold bg-gray-100 dark:bg-cherry-700' : ''
          ]"
          :title="option[labelKey]"
          @mousedown.stop.prevent="toggleOption(option)"
        >
          <span class="truncate w-full dark:text-white text-gray-600">
            @{{ option[labelKey] || option[valueKey] }}
          </span>
        </div>
      </div>
    </div>
  </script>

  <script type="module">
    app.component('v-spreadsheet-multiselect', {
      template: '#v-spreadsheet-multiselect-template',

      inject: ["optionsCache"],
      props: {
        isActive: Boolean,
        modelValue: Array,
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
          hasMore: true,
          valueKey: 'code',
          isUpdated: false,
          labelKey: 'label',
          search: '',
          debounceTimeout: null,
          selectedOptions: [],
          allOptions: [],
          labelCache: {},
          loadedOnce: false,
          highlightedIndex: -1,
          routeUrl: "{{ route('admin.catalog.options.fetch-all') }}"
        };
      },

      computed: {
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
          if (newVal == this.selectedOptions.map(o => o[this.valueKey]).join(', ')) return;
          this.syncSelectedOptions(newVal);
          this.$refs.nativeUndoInput.focus();
          this.$refs.nativeUndoInput.select();

          document.execCommand('insertText', false, newVal);

          if (!this.isUpdated) {
            this.$emitter.emit('update-spreadsheet-data', {
              value: newVal,
              entityId: this.entityId,
              column: this.column,
            });
          }

        },
        options: {
          handler() {
            this.syncSelectedOptions(this.modelValue);
          },
          deep: true,
          immediate: true
        }
      },

      async mounted() {
        this.syncSelectedOptions(this.modelValue);
        this.$refs.nativeUndoInput.value = this.selectedOptions.map(o => o[this.valueKey]).join(', ');
        document.addEventListener('click', this.handleClickOutside);
        this.$refs.input.addEventListener('keydown', this.handleKeyDown);
      },

      methods: {
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
              this.toggleOption(option);
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
            const list = this.$el.querySelector('.absolute > div');
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

        syncSelectedOptions(val) {
          let codes = [];

          if (Array.isArray(val)) {
            codes = val;
          } else if (typeof val === 'string' && val.length) {
            codes = val.split(',').map(v => v.trim()).filter(Boolean);
          }

          this.selectedOptions = codes.map(code => {
            if (this.labelCache[code]) {
              return {
                [this.valueKey]: code,
                [this.labelKey]: this.labelCache[code],
              };
            }
            const opt = this.options.find(o => o[this.valueKey] === code);

            if (opt) {
              this.labelCache[code] = opt[this.labelKey];
              return opt;
            }
            return { [this.valueKey]: code, [this.labelKey]: code };
          });
        },

        openDropdown() {
          if(this.open) return;
          document.addEventListener('click', this.handleClickOutside);

          this.search = '';
          this.$refs.input.value = '';
          if (this.options.length === 0 && !this.loadedOnce) {
            this.loadOptions(true);
          } else {
            this.loadOptions(false);
          }

          this.open = true;
        },

        handleClickOutside(event) {
          if (!this.$el.contains(event.target)) {
            this.open = false;
            document.removeEventListener('click', this.handleClickOutside);
          }
        },

        onBlur() {
          this.open = false;
          this.search = '';
          this.$refs.input.value = '';
          document.removeEventListener('click', this.handleClickOutside);
        },

        loadOptions(reset = false) {
            if (this.loading || (!this.hasMore && !reset)) return;
            if (this.loadedOnce && !reset) return;
            if (reset) {
                this.page = 1;
                this.options = [];
                this.hasMore = true;
                this.loadedOnce = false;
            }

            this.loading = true;

            const url = new URL(this.routeUrl, window.location.origin);

            Object.entries(this.queryParams).forEach(([key, val]) => {
                if (val !== '') url.searchParams.append(key, val);
            });

            this.$axios
                .get(url.toString())
                .then(response => {
                    const data = response.data;

                    if (!data.options || data.options.length === 0) {
                        this.hasMore = false;
                    } else {
                        this.options.push(...data.options);
                        this.page++;
                        this.loadedOnce = true;
                    }
                })
                .catch(err => {
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
          }, 400);
        },

        onScroll(e) {
          const el = e.target;
          if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
            this.loadOptions();
          }
        },

        toggleOption(option) {
          if (this.selectedOptions.find(o => o[this.valueKey] === option[this.valueKey])) {
            this.removeOption(option[this.valueKey]);
          } else {
            this.selectedOptions.push(option);
            this.labelCache[option[this.valueKey]] = option[this.labelKey];
            this.emitChange();
          }
      
          this.$refs.input.value = '';
          this.search = '';
        },

        removeOption(val) {
          this.selectedOptions = this.selectedOptions.filter(v => v[this.valueKey] !== val);
          this.emitChange();
        },

        emitChange() {
          this.$refs.nativeUndoInput.focus();
          this.$refs.nativeUndoInput.select();

          let val = this.selectedOptions.map(o => o[this.valueKey]).join(',');

          document.execCommand('insertText', false, val);

          const selectedValues = this.selectedOptions.map(option => option[this.valueKey]);
          this.$emit('update:modelValue', selectedValues); 

          this.$emitter.emit('update-spreadsheet-data', {
            value: val,
            entityId: this.entityId,
            column: this.column,
          });

          this.$nextTick(() => {
            this.$refs.input.focus();
          });
        },

        update() {
          if (this.selectedOptions.join(',') === this.$refs.nativeUndoInput.value) return;
          const inputVal = this.$refs.nativeUndoInput.value.trim();

          if (!inputVal) {
            this.selectedOptions = [];
            this.$emit('update:modelValue', '');
            return;
          }
          const codes = inputVal.split(',').map(v => v.trim()).filter(Boolean);

          this.selectedOptions = codes.map(code => {
            return this.options.find(o => o[this.valueKey] === code) || {
              [this.valueKey]: code,
              [this.labelKey]: code
            };
          });

          const val = codes.join(',');

          this.$emit('update:modelValue',val);

          this.$emitter.emit('update-spreadsheet-data', {
            value: val,
            entityId: this.entityId,
            column: this.column,
          });

          this.$nextTick(() => {
            this.$refs.input.focus();
          });
        },
      },
    });
  </script>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-spreadsheet-text-template">
        <input
            ref="input"
            type="text"
            :name="`${entityId}_${column.code}`"
            v-bind="field"
            class="w-full h-full text-sm text-gray-600 dark:text-gray-300 transition-all  focus:border-gray-400 dark:focus:border-gray-400 bg-transparent dark:border-gray-600"
            @blur="update"
        />
    </script>

    <script type="module">
        app.component('v-spreadsheet-text', {
            template: '#v-spreadsheet-text-template',

            props: {
                isActive: {
                    type: Boolean,
                    default: false,
                },

                modelValue: {
                    type: String,
                    default: null,
                },

                entityId: {
                    type: Number,
                },

                column : {
                    type: Array,
                },

                attribute : {
                    type: Array,
                }
            },

            data() {
                return {
                    isUpdated: false,
                    isCheckingSku: false,
                    skuCheckToken: 0,
                };
            },

            inject: ['gridContext'],

            mounted() {
                if (this.$refs.input) {
                    this.$refs.input.value = this.modelValue ?? '';
                }
            },

            watch: {
                async modelValue(newVal) {
                    if (newVal === this.$refs.input.value) return;

                    if (this.isUpdated) {
                        this.isUpdated = false;
                        return;
                    }

                    const { valid, message } = await this.validate(newVal);
                    if (!valid) {
                        console.warn('Validation failed:', message);
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: message,
                        });

                        this.$refs.input.value = this.modelValue ?? '';
                        return;
                    }

                    const input = this.$refs.input;

                    input.focus();
                    input.select();

                    document.execCommand('insertText', true, newVal);

                    this.$nextTick(() => {
                        input.blur();
                    });

                    if (this.isSkuColumn()) {
                        this.gridContext?.setSkuForEntity?.(this.entityId, newVal);
                    }

                    if (! this.isUpdated) {
                        this.$emitter.emit('update-spreadsheet-data', {
                            value: newVal,
                            entityId: this.entityId,
                            column: this.column,
                        });
                    }

                    this.isUpdated = false;
                },
            },

            methods: {
                focus() {
                    this.$refs.input?.focus();
                },

                update() {
                    let updateValue = this.$refs.input.value;

                    if (updateValue === this.modelValue || (!updateValue && !this.modelValue)) return;

                    this.performUpdate(updateValue);
                },

                async validateValue(value) {
                    const { valid, message } = await this.validate(value);

                    if (!valid) {
                        console.warn('Validation failed:', message);
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: message,
                        });

                        if (this.$refs.input) {
                            this.$refs.input.value = this.modelValue ?? '';
                        }
                    }

                    return { valid };
                },

                async performUpdate(updateValue) {
                    const { valid, message } = await this.validate(updateValue);

                    if (!valid) {
                        console.warn('Validation failed:', message);
                        this.$emitter.emit('add-flash', {
                            type: 'warning',
                            message: message,
                        });

                        this.$refs.input.value = this.modelValue ?? '';
                        return;
                    }

                    this.isUpdated = true;

                    this.$emit('update:modelValue', updateValue);

                    if (this.isSkuColumn()) {
                        this.gridContext?.setSkuForEntity?.(this.entityId, updateValue);
                    }

                    this.$emitter.emit('update-spreadsheet-data', {
                        value: updateValue,
                        entityId: this.entityId,
                        column: this.column,
                    });
                },

                isSkuColumn() {
                    return this.column?.code === 'sku';
                },

                async validate(value) {
                    if (this.isSkuColumn()) {
                        const trimmedValue = (value ?? '').toString().trim();

                        if (!trimmedValue) {
                            return {
                                valid: false,
                                message: "@lang('validation.required', ['attribute' => 'SKU'])",
                            };
                        }

                        if (!/^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/.test(trimmedValue)) {
                            return {
                                valid: false,
                                message: "@lang('core::validation.slug', ['attribute' => 'SKU'])",
                            };
                        }

                        if (this.gridContext?.isSkuUnique && !this.gridContext.isSkuUnique(trimmedValue, this.entityId)) {
                            const messageTemplate = '@lang("admin::app.catalog.products.index.create.sku-exists-error", ["sku" => ":sku"])';

                            return {
                                valid: false,
                                message: messageTemplate.replace(':sku', trimmedValue),
                            };
                        }

                        const remoteResult = await this.validateSkuRemote(trimmedValue);
                        if (!remoteResult.valid) {
                            return remoteResult;
                        }
                    }

                    const type = this.attribute?.validation;
                    const regex = this.attribute?.regex_pattern;
                    const trimmedValue = (value ?? '').toString().trim();

                    const validators = {
                        decimal(val) {
                            const isValid = !isNaN(val) && !Number.isNaN(parseFloat(val));
                            return {
                                valid: isValid,
                                message: isValid ? null : "@lang('admin::app.catalog.products.bulk-edit.validation.decimal')",
                            };
                        },
                        number(val) {
                            const isValid = Number.isInteger(Number(val));
                            return {
                                valid: isValid,
                                message: isValid ? null : "@lang('admin::app.catalog.products.bulk-edit.validation.number')",
                            };
                        },
                        email(val) {
                            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
                            return {
                                valid: isValid,
                                message: isValid ? null : "@lang('admin::app.catalog.products.bulk-edit.validation.email')",
                            };
                        },
                        url(val) {
                            try {
                                new URL(val);
                                return { valid: true, message: null };
                            } catch {
                                return {
                                    valid: false,
                                    message: "@lang('admin::app.catalog.products.bulk-edit.validation.url')",
                                };
                            }
                        },
                    };

                    let result = { valid: true, message: null };

                    if (type && typeof validators[type] === 'function') {
                        result = validators[type].call(this, trimmedValue);
                    } else if (type && !validators[type]) {
                        console.warn(`Unknown validation type: "${type}"`);
                    }

                    if (result.valid && regex) {
                        try {
                            const customRegex = new RegExp(regex);
                            const isCustomValid = customRegex.test(trimmedValue);
                            if (!isCustomValid) {
                                result = {
                                    valid: false,
                                    message: "@lang('admin::app.catalog.products.bulk-edit.validation.regex')",
                                };
                            }
                        } catch (e) {
                            console.error('Invalid custom regex:', regex);
                            result = {
                                valid: false,
                                message: "@lang('admin::app.catalog.products.bulk-edit.validation.invalid-pattern')",
                            };
                        }
                    }

                    return result;
                },

                async validateSkuRemote(trimmedValue) {
                    if (!this.gridContext?.skuUniqueUrl) {
                        return { valid: true, message: null };
                    }

                    if (!trimmedValue) {
                        return { valid: true, message: null };
                    }

                    const token = ++this.skuCheckToken;
                    this.isCheckingSku = true;

                    try {
                        await this.$axios.post(this.gridContext.skuUniqueUrl, {
                            sku: trimmedValue,
                            entity_id: this.entityId,
                        });

                        if (token !== this.skuCheckToken) {
                            return { valid: true, message: null };
                        }

                        return { valid: true, message: null };

                    } catch (error) {
                        if (token !== this.skuCheckToken) {
                            return { valid: true, message: null };
                        }

                        if (error.response?.status === 422) {
                            const skuErrors = error.response.data?.errors?.sku;
                            const errMessage = '@lang("admin::app.catalog.products.index.create.sku-exists-error")';
                            errMessage.replace(':sku', trimmedValue);

                            return {
                                valid: false,
                                message: skuErrors?.[0]
                                    || errMessage,
                            };
                        }

                        return {
                            valid: false,
                            message: "@lang('admin::app.catalog.products.bulk-edit.validation.failed')",
                        };
                    } finally {
                        if (token === this.skuCheckToken) {
                            this.isCheckingSku = false;
                        }
                    }
                }

            },
        });
    </script>
@endPushOnce

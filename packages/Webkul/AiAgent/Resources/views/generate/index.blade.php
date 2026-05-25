<x-admin::layouts>
    <x-slot:title>
        @lang('ai-agent::app.generate.title')
    </x-slot>

    {!! view_render_event('unopim.admin.ai-agent.generate.before') !!}

    <v-ai-generate></v-ai-generate>

    {!! view_render_event('unopim.admin.ai-agent.generate.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-ai-generate-template">
            <div>
                <!-- Header -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('ai-agent::app.generate.title')
                    </p>
                </div>

                <!-- Main card -->
                <div class="flex justify-center mt-7">
                    <div class="w-full max-w-3xl">
                        <div class="relative bg-white dark:bg-cherry-900 rounded-xl box-shadow">

                            <!-- Image upload area -->
                            <div class="p-6 pb-0">
                                <div
                                    class="relative flex flex-wrap gap-3 p-4 min-h-[140px] border-2 border-dashed rounded-lg transition-colors"
                                    :class="isDragOver
                                        ? 'border-violet-500 bg-violet-50 dark:bg-cherry-800'
                                        : 'border-gray-300 dark:border-cherry-800 bg-gray-50 dark:bg-cherry-950'"
                                    @dragover.prevent="isDragOver = true"
                                    @dragleave.prevent="isDragOver = false"
                                    @drop.prevent="onDrop"
                                >
                                    <!-- Uploaded image previews -->
                                    <div
                                        v-for="(image, index) in images"
                                        :key="index"
                                        class="relative group"
                                    >
                                        <img
                                            :src="image.preview"
                                            :alt="image.file.name"
                                            class="w-[100px] h-[100px] object-cover rounded-lg border border-gray-200 dark:border-cherry-800"
                                        />

                                        <!-- Remove button -->
                                        <button
                                            type="button"
                                            class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                                            @click="removeImage(index)"
                                        >
                                            &times;
                                        </button>
                                    </div>

                                    <!-- Empty state / Add more prompt -->
                                    <label
                                        v-if="images.length === 0"
                                        class="flex flex-col items-center justify-center w-full cursor-pointer"
                                    >
                                        <span class="icon-export text-gray-400 dark:text-gray-500 text-4xl mb-2"></span>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-semibold text-violet-600">@lang('ai-agent::app.generate.click-to-upload')</span>
                                            @lang('ai-agent::app.generate.or-drag-drop')
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            @lang('ai-agent::app.generate.file-types')
                                        </p>
                                        <input
                                            type="file"
                                            ref="fileInput"
                                            class="hidden"
                                            accept="image/jpeg,image/png,image/webp,image/gif"
                                            multiple
                                            @change="onFileSelect"
                                        />
                                    </label>
                                </div>
                            </div>

                            <!-- Instruction textarea -->
                            <div class="p-6 pt-4">
                                <textarea
                                    v-model="instruction"
                                    rows="3"
                                    class="w-full resize-none border border-gray-200 dark:border-cherry-800 rounded-lg p-3 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-cherry-950 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                    placeholder="@lang('ai-agent::app.generate.instruction-placeholder')"
                                ></textarea>
                            </div>

                            <!-- Footer: Add Assets + Generate -->
                            <div class="flex justify-between items-center px-6 pb-6">
                                <label class="flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-cherry-800 rounded-full cursor-pointer hover:bg-gray-50 dark:hover:bg-cherry-800 transition-colors">
                                    <span class="text-lg text-violet-600">+</span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        @lang('ai-agent::app.generate.add-assets')
                                    </span>
                                    <input
                                        type="file"
                                        ref="addMoreInput"
                                        class="hidden"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        multiple
                                        @change="onFileSelect"
                                    />
                                </label>

                                <button
                                    type="button"
                                    class="flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-500 rounded-lg text-white font-semibold text-sm transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="images.length === 0 || isGenerating"
                                    @click="generate"
                                >
                                    <template v-if="isGenerating">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        @lang('ai-agent::app.generate.generating')
                                    </template>

                                    <template v-else>
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor" stroke="none"/>
                                        </svg>
                                        @lang('ai-agent::app.generate.generate-btn')
                                    </template>
                                </button>
                            </div>

                        </div>

                        <!-- Credential selector (below the card) -->
                        <div class="mt-4 flex justify-end">
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-500 dark:text-gray-400">
                                    @lang('ai-agent::app.generate.credential')
                                </label>
                                <select
                                    v-model="credentialId"
                                    class="text-xs border border-gray-200 dark:border-cherry-800 rounded px-2 py-1 bg-white dark:bg-cherry-900 text-gray-700 dark:text-gray-300 focus:outline-none focus:border-violet-500"
                                >
                                    <option
                                        v-for="cred in credentials"
                                        :key="cred.id"
                                        :value="cred.id"
                                        v-text="cred.label"
                                    ></option>
                                </select>
                            </div>
                        </div>

                        <!-- Result panel (shown after generation) -->
                        <div
                            v-if="result"
                            class="mt-6 bg-white dark:bg-cherry-900 rounded-xl box-shadow p-6"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-base text-gray-800 dark:text-white font-semibold">
                                    @lang('ai-agent::app.generate.result-title')
                                </p>
                                <span
                                    class="text-xs px-2 py-1 rounded-full font-medium"
                                    :class="result.confidence >= 0.7
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : result.confidence >= 0.4
                                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                >
                                    @lang('ai-agent::app.generate.confidence'): @{{ Math.round(result.confidence * 100) }}%
                                </span>
                            </div>

                            <!-- Detected product -->
                            <div class="mb-3" v-if="result.detected_product">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    @lang('ai-agent::app.generate.detected-product')
                                </span>
                                <p class="text-sm text-gray-800 dark:text-gray-200 mt-0.5" v-text="result.detected_product"></p>
                            </div>

                            <!-- Category -->
                            <div class="mb-3" v-if="result.category">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    @lang('ai-agent::app.generate.category')
                                </span>
                                <p class="text-sm text-gray-800 dark:text-gray-200 mt-0.5" v-text="result.category"></p>
                            </div>

                            <!-- Attributes table -->
                            <div v-if="Object.keys(result.attributes).length > 0" class="mb-3">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    @lang('ai-agent::app.generate.attributes')
                                </span>
                                <div class="mt-1 border border-gray-200 dark:border-cherry-800 rounded-lg overflow-hidden">
                                    <div
                                        v-for="(value, key) in result.attributes"
                                        :key="key"
                                        class="flex border-b border-gray-100 dark:border-cherry-800 last:border-b-0"
                                    >
                                        <span class="w-1/3 px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-cherry-950" v-text="key"></span>
                                        <span class="w-2/3 px-3 py-2 text-xs text-gray-700 dark:text-gray-300" v-text="formatValue(value)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Enrichment data -->
                            <div v-if="Object.keys(result.enrichment).length > 0" class="mb-3">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    @lang('ai-agent::app.generate.enrichment')
                                </span>
                                <div class="mt-1 border border-gray-200 dark:border-cherry-800 rounded-lg overflow-hidden">
                                    <div
                                        v-for="(value, key) in result.enrichment"
                                        :key="key"
                                        class="flex border-b border-gray-100 dark:border-cherry-800 last:border-b-0"
                                    >
                                        <span class="w-1/3 px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-cherry-950" v-text="key"></span>
                                        <span class="w-2/3 px-3 py-2 text-xs text-gray-700 dark:text-gray-300" v-text="formatValue(value)"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Product ID (if created) -->
                            <div v-if="result.product_id" class="mt-4 flex justify-end">
                                <a
                                    :href="'{{ route('admin.catalog.products.edit', ':id:') }}'.replace(':id:', result.product_id)"
                                    class="primary-button text-sm"
                                >
                                    @lang('ai-agent::app.generate.view-product')
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-ai-generate', {
                template: '#v-ai-generate-template',

                data() {
                    return {
                        images: [],
                        instruction: '',
                        credentialId: null,
                        credentials: @json($credentials ?? []),
                        isDragOver: false,
                        isGenerating: false,
                        result: null,
                    };
                },

                mounted() {
                    if (this.credentials.length > 0) {
                        this.credentialId = this.credentials[0].id;
                    }
                },

                methods: {
                    onFileSelect(event) {
                        this.addFiles(event.target.files);
                        event.target.value = '';
                    },

                    onDrop(event) {
                        this.isDragOver = false;
                        this.addFiles(event.dataTransfer.files);
                    },

                    addFiles(files) {
                        const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                        Array.from(files).forEach(file => {
                            if (! allowed.includes(file.type)) {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: "{{ trans('ai-agent::app.generate.validation.unsupported-type') }}".replace(':name', file.name),
                                });
                                return;
                            }

                            if (file.size > 10 * 1024 * 1024) {
                                this.$emitter.emit('add-flash', {
                                    type: 'warning',
                                    message: "{{ trans('ai-agent::app.generate.validation.file-too-large') }}".replace(':name', file.name),
                                });
                                return;
                            }

                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.images.push({
                                    file: file,
                                    preview: e.target.result,
                                });
                            };
                            reader.readAsDataURL(file);
                        });
                    },

                    removeImage(index) {
                        this.images.splice(index, 1);
                    },

                    async generate() {
                        if (this.images.length === 0) return;
                        if (! this.credentialId) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: "@lang('ai-agent::app.generate.select-credential')",
                            });
                            return;
                        }

                        this.isGenerating = true;
                        this.result = null;

                        const formData = new FormData();

                        this.images.forEach((img, i) => {
                            formData.append(`images[${i}]`, img.file);
                        });

                        formData.append('instruction', this.instruction);
                        formData.append('credential_id', this.credentialId);

                        try {
                            const response = await this.$axios.post(
                                "{{ route('ai-agent.generate.process') }}",
                                formData,
                                { headers: { 'Content-Type': 'multipart/form-data' } }
                            );

                            this.result = response.data.data;

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message,
                            });
                        } catch (error) {
                            const msg = error.response?.data?.message
                                || "@lang('ai-agent::app.generate.error-generic')";

                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: msg,
                            });
                        } finally {
                            this.isGenerating = false;
                        }
                    },

                    formatValue(value) {
                        if (Array.isArray(value)) return value.join(', ');
                        if (typeof value === 'object' && value !== null) return JSON.stringify(value);
                        return String(value);
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

{{-- Variant axis nav (sidebar). Each axis is a dropdown listing ONLY created variants
     (fetched async from variant-children: search + infinite scroll → scales to 10k+).
     "Add" uses the standard async option select (all options) + SKU to create a new one. --}}
@php
    $variationTrans = trans('admin::app.catalog.products.edit.variations');

    $variantUrls = [
        'children' => route('admin.catalog.products.variant_children', ':configurable'),
        'create'   => route('admin.catalog.products.variant_node.create', ':configurable'),
        'edit'     => route('admin.catalog.products.edit', ':id'),
    ];
@endphp

<script type="text/javascript">
    window.__variantTree = @json($variantTree);
    window.__variantTrans = @json($variationTrans);
    window.__variantUrls = @json($variantUrls);
</script>

@pushOnce('scripts')
    @verbatim
    <script type="text/x-template" id="v-variant-axis-nav-template">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <div class="flex items-center justify-between mb-3">
                <p class="text-base text-gray-800 dark:text-white font-semibold">{{ t('title') }}</p>
                <span v-if="navigating" class="flex items-center gap-1.5 text-xs text-primary-600">
                    <span class="w-3 h-3 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></span> {{ t('switching') }}
                </span>
                <span v-else-if="totalVariants !== null" class="text-xs text-gray-400">{{ t(totalVariants === 1 ? 'variant-count' : 'variants-count', { count: totalVariants }) }}</span>
            </div>

            <!-- COMMON -->
            <button type="button"
                class="w-full flex items-center gap-2 h-11 px-3 rounded-md text-sm font-semibold uppercase border mb-3"
                :class="selected === rootId ? 'bg-primary-100 dark:bg-cherry-800 text-primary-600 border-primary-300 dark:border-cherry-700' : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-cherry-900 border-gray-200 dark:border-cherry-800 hover:bg-gray-100 dark:hover:bg-cherry-800'"
                @click="selectCommon">
                {{ t('common') }}
                <span v-if="pendingId === configurableId" class="ltr:ml-auto rtl:mr-auto w-3.5 h-3.5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></span>
            </button>

            <!-- Parent axis dropdown (level 1 — indented under COMMON) -->
            <div class="relative mb-3 ltr:ml-2 rtl:mr-2">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ label(parentAxes) }}</p>
                <div class="relative" ref="pWrap">
                    <button type="button"
                        class="w-full flex items-center justify-between gap-2 h-11 px-3 rounded-md text-sm border"
                        :class="currentLabel('p') ? 'bg-primary-50 dark:bg-cherry-800 border-primary-300 dark:border-cherry-700' : 'bg-white dark:bg-cherry-900 border-gray-200 dark:border-cherry-700 hover:border-primary-400'"
                        @click="toggle('p')">
                        <span class="flex items-center gap-2 truncate min-w-0" :class="pNode ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-400'">
                            <img v-if="pNode && pNode.image" :src="pNode.image" class="w-6 h-6 rounded object-cover shrink-0 border border-gray-200 dark:border-cherry-700" alt="">
                            <span v-else-if="pNode" class="w-6 h-6 rounded bg-gray-100 dark:bg-cherry-800 flex items-center justify-center shrink-0"><span class="icon-image text-gray-400 text-base"></span></span>
                            <span class="truncate">{{ currentLabel('p') || t('select', { attribute: label(parentAxes) }) }}</span>
                        </span>
                        <span class="flex items-center gap-2 shrink-0">
                            <span v-if="pNode && levels === 2" class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="countClass(pNode.variantComplete, pNode.variantTotal)" :title="t('complete-count', { done: pNode.variantComplete, total: pNode.variantTotal })">{{ pNode.variantTotal }}</span>
                            <span v-else-if="pNode && pNode.completeness != null" class="text-[10px] font-semibold px-1.5 py-0.5 rounded border" :class="completenessClass(pNode.completeness)">{{ pNode.completeness }}%</span>
                            <span class="icon-chevron-down text-lg text-gray-400"></span>
                        </span>
                    </button>

                    <div v-show="p.open" class="absolute mt-1 w-full bg-white dark:bg-cherry-900 border border-gray-200 dark:border-cherry-700 rounded-md shadow-lg" style="z-index: 60">
                        <div class="p-2 border-b border-gray-100 dark:border-cherry-800">
                            <div class="flex items-center w-full border rounded-md px-2.5 py-1.5 dark:border-gray-600">
                                <span class="icon-search text-gray-400"></span>
                                <input v-model="p.q" @input="onSearch('p')" @keydown="onKey($event, 'p')" ref="pSearch" type="text" :placeholder="t('search')" class="w-full ltr:ml-2 rtl:mr-2 bg-transparent text-sm outline-none text-gray-700 dark:text-gray-200">
                            </div>
                        </div>
                        <div class="overflow-y-auto py-1" style="max-height: 14rem" @scroll="onScroll($event, 'p')" ref="pList">
                            <div v-for="(it, idx) in p.items" :key="it.id"
                                class="flex items-center gap-2.5 px-3 py-2 text-sm"
                                :class="[isActive(it.id) ? 'bg-primary-50 dark:bg-cherry-800 text-primary-600 font-semibold' : (p.active === idx ? 'bg-gray-100 dark:bg-cherry-800 text-gray-700 dark:text-gray-200' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800'), navigating ? 'cursor-default' : 'cursor-pointer']"
                                @mouseenter="p.active = idx" @click="go(it.id)">
                                <img v-if="it.image" :src="it.image" class="w-7 h-7 rounded object-cover shrink-0 border border-gray-200 dark:border-cherry-700" alt="">
                                <span v-else class="w-7 h-7 rounded bg-gray-100 dark:bg-cherry-800 flex items-center justify-center shrink-0"><span class="icon-image text-gray-400 text-base"></span></span>
                                <span class="truncate">{{ it.label }}</span>
                                <span v-if="pendingId === it.id" class="ltr:ml-auto rtl:mr-auto w-3.5 h-3.5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin shrink-0"></span>
                                <span v-else-if="levels === 2" class="ltr:ml-auto rtl:mr-auto text-[11px] font-semibold px-2 py-0.5 rounded-full shrink-0" :class="countClass(it.variantComplete, it.variantTotal)" :title="t('complete-count', { done: it.variantComplete, total: it.variantTotal })">{{ it.variantTotal }}</span>
                                <span v-else-if="it.completeness != null" class="ltr:ml-auto rtl:mr-auto text-[10px] font-semibold px-1.5 py-0.5 rounded border shrink-0" :class="completenessClass(it.completeness)">{{ it.completeness }}%</span>
                            </div>
                            <p v-if="p.loading" class="px-3 py-2 text-xs text-gray-400">{{ t('loading') }}</p>
                            <p v-else-if="!p.items.length" class="px-3 py-2 text-xs text-gray-400">{{ t('empty', { attribute: label(parentAxes) }) }}</p>
                            <p v-if="p.loadingMore" class="px-3 py-1 text-xs text-center text-gray-400">{{ t('loading-more') }}</p>
                        </div>
                        <div class="p-2 border-t border-gray-100 dark:border-cherry-800">
                            <button type="button" class="primary-button w-full justify-center !py-2"
                                @click="openAdd('p')">{{ t('add-new-btn') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Child axis dropdown (level 2 — indented deeper: parent → leaf) -->
            <div v-if="levels === 2" class="relative mb-1 ltr:ml-6 rtl:mr-6">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1">{{ label(childAxes) }}</p>
                <div class="relative" ref="cWrap">
                    <!-- Children can only be listed under a picked parent, so the axis stays
                         visible but inert until one is selected. -->
                    <button type="button"
                        class="w-full flex items-center justify-between gap-2 h-11 px-3 rounded-md text-sm border"
                        :class="[
                            currentLabel('c') ? 'bg-primary-50 dark:bg-cherry-800 border-primary-300 dark:border-cherry-700' : 'bg-white dark:bg-cherry-900 border-gray-200 dark:border-cherry-700',
                            currentGroupId ? 'hover:border-primary-400' : 'opacity-60 cursor-not-allowed'
                        ]"
                        :disabled="! currentGroupId"
                        @click="toggle('c')">
                        <span class="flex items-center gap-2 truncate min-w-0" :class="cNode ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-400'">
                            <img v-if="cNode && cNode.image" :src="cNode.image" class="w-6 h-6 rounded object-cover shrink-0 border border-gray-200 dark:border-cherry-700" alt="">
                            <span v-else-if="cNode" class="w-6 h-6 rounded bg-gray-100 dark:bg-cherry-800 flex items-center justify-center shrink-0"><span class="icon-image text-gray-400 text-base"></span></span>
                            <span class="truncate">{{ currentLabel('c') || t('select', { attribute: label(childAxes) }) }}</span>
                        </span>
                        <span class="flex items-center gap-2 shrink-0">
                            <span v-if="cNode && cNode.completeness != null" class="text-[10px] font-semibold px-1.5 py-0.5 rounded border" :class="completenessClass(cNode.completeness)">{{ cNode.completeness }}%</span>
                            <span class="icon-chevron-down text-lg text-gray-400"></span>
                        </span>
                    </button>

                    <div v-show="c.open" class="absolute mt-1 w-full bg-white dark:bg-cherry-900 border border-gray-200 dark:border-cherry-700 rounded-md shadow-lg" style="z-index: 60">
                        <div class="p-2 border-b border-gray-100 dark:border-cherry-800">
                            <div class="flex items-center w-full border rounded-md px-2.5 py-1.5 dark:border-gray-600">
                                <span class="icon-search text-gray-400"></span>
                                <input v-model="c.q" @input="onSearch('c')" @keydown="onKey($event, 'c')" ref="cSearch" type="text" :placeholder="t('search')" class="w-full ltr:ml-2 rtl:mr-2 bg-transparent text-sm outline-none text-gray-700 dark:text-gray-200">
                            </div>
                        </div>
                        <div class="overflow-y-auto py-1" style="max-height: 14rem" @scroll="onScroll($event, 'c')" ref="cList">
                            <div v-for="(it, idx) in c.items" :key="it.id"
                                class="flex items-center gap-2.5 px-3 py-2 text-sm"
                                :class="[isActive(it.id) ? 'bg-primary-50 dark:bg-cherry-800 text-primary-600 font-semibold' : (c.active === idx ? 'bg-gray-100 dark:bg-cherry-800 text-gray-700 dark:text-gray-200' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-cherry-800'), navigating ? 'cursor-default' : 'cursor-pointer']"
                                @mouseenter="c.active = idx" @click="go(it.id)">
                                <img v-if="it.image" :src="it.image" class="w-7 h-7 rounded object-cover shrink-0 border border-gray-200 dark:border-cherry-700" alt="">
                                <span v-else class="w-7 h-7 rounded bg-gray-100 dark:bg-cherry-800 flex items-center justify-center shrink-0"><span class="icon-image text-gray-400 text-base"></span></span>
                                <span class="truncate">{{ it.label }}</span>
                                <span v-if="pendingId === it.id" class="ltr:ml-auto rtl:mr-auto w-3.5 h-3.5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin shrink-0"></span>
                                <span v-else-if="it.completeness != null" class="ltr:ml-auto rtl:mr-auto text-[10px] font-semibold px-1.5 py-0.5 rounded border shrink-0" :class="completenessClass(it.completeness)">{{ it.completeness }}%</span>
                            </div>
                            <p v-if="c.loading" class="px-3 py-2 text-xs text-gray-400">{{ t('loading') }}</p>
                            <p v-else-if="!c.items.length" class="px-3 py-2 text-xs text-gray-400">{{ t('empty', { attribute: label(childAxes) }) }}</p>
                            <p v-if="c.loadingMore" class="px-3 py-1 text-xs text-center text-gray-400">{{ t('loading-more') }}</p>
                        </div>
                        <div class="p-2 border-t border-gray-100 dark:border-cherry-800">
                            <button type="button" class="primary-button w-full justify-center !py-2"
                                @click="openAdd('c')">{{ t('add-new-btn') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add-new modal. Teleported so its inputs sit outside the product <form> DOM
                 (nothing here may ride along on a product save). -->
            <teleport to="body">
            @endverbatim
                {{-- Plain input, not x-admin::form.control-group.control: a v-field here would
                     register with the product form's VeeValidate provider and block its save. --}}
                <x-admin::modal ref="addModal" prevent-submit @close="cancelAdd">
                    <x-slot:header>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-primary-600">
                                @lang('admin::app.catalog.products.edit.variations.title')
                            </p>

                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @{{ addModalTitle }}
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <template v-for="axis in addAxes" :key="axis">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @{{ label(axis) }}
                                </x-admin::form.control-group.label>

                                <v-async-select-handler
                                    v-if="attrIdFor(axis)"
                                    entity-name="attribute"
                                    :attribute-id="attrIdFor(axis)"
                                    track-by="code"
                                    label-by="label"
                                    :placeholder="axisPlaceholder(axis)"
                                    @select-option="onPick($event, axis)"
                                ></v-async-select-handler>
                            </x-admin::form.control-group>
                        </template>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.catalog.products.edit.variations.sku')
                            </x-admin::form.control-group.label>

                            <input
                                v-model="newSku"
                                @input="skuEdited = true"
                                type="text"
                                placeholder="@lang('admin::app.catalog.products.edit.variations.sku')"
                                class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                            >
                        </x-admin::form.control-group>
                    </x-slot>

                    <x-slot:footer>
                        <div class="flex gap-2">
                            <button type="button" class="secondary-button" @click="cancelAdd">
                                @lang('admin::app.catalog.products.edit.variations.cancel')
                            </button>

                            <button
                                type="button"
                                class="primary-button"
                                :disabled="! addComplete || ! newSku || creating"
                                @click="create(addFor)"
                            >
                                @{{ creating ? createBusyLabel : createLabel }}
                            </button>
                        </div>
                    </x-slot>
                </x-admin::modal>
            @verbatim
            </teleport>
        </div>
    </script>

    <script type="module">
        const treeData = window.__variantTree || { nodes: {}, attributes: [], axesByLevel: {}, levels: 1 };
        const TRANS = window.__variantTrans || {};
        const t = (key, params) => Object.keys(params || {}).reduce((line, k) => line.replace(':' + k, params[k]), TRANS[key] || key);
        const URLS = window.__variantUrls || {};
        const rootId = (() => { const r = Object.values(treeData.nodes).find(n => n.role === 'configurable') || Object.values(treeData.nodes)[0]; return r ? r.id : null; })();
        const ATTR = {}, ATTRID = {};
        treeData.attributes.forEach(a => { ATTR[a.code] = a.label; ATTRID[a.code] = a.attributeId; });
        const axisL1 = treeData.axesByLevel.level_1 || [];
        const axisL2 = treeData.axesByLevel.level_2 || [];
        const CHILDREN_URL = (cfg) => (URLS.children || '').replace(':configurable', cfg);
        const CREATE_URL = (cfg) => (URLS.create || '').replace(':configurable', cfg);
        const EDIT_URL = (id) => (URLS.edit || '').replace(':id', id);

        app.component('v-variant-axis-nav', {
            template: '#v-variant-axis-nav-template',
            data() {
                const cfgId = treeData.configurableId || rootId;
                const nodes = {};
                Object.values(treeData.nodes).forEach(n => { nodes[n.id] = { id: n.id, role: n.role, parent: n.parentId, fix: Object.assign({}, n.axisFix || {}), sku: n.sku, image: n.image || null, completeness: n.completeness != null ? n.completeness : null, variantTotal: n.variantTotal != null ? n.variantTotal : null, variantComplete: n.variantComplete != null ? n.variantComplete : null }; });
                const axisLabels = treeData.axisLabels || {};
                const cur = treeData.currentNodeId || cfgId;
                const curNode = nodes[cur] || {};
                let groupId = null;
                if (curNode.role === 'variant_group') { groupId = curNode.id; }
                else if (curNode.role === 'simple' && nodes[curNode.parent] && nodes[curNode.parent].role === 'variant_group') { groupId = curNode.parent; }
                return {
                    nodes, axisLabels, rootId: cfgId, configurableId: cfgId, selected: cur, currentGroupId: groupId,
                    levels: treeData.levels, attrId: ATTRID,
                    parentAxes: treeData.levels === 2 ? axisL1 : (axisL1.length ? axisL1 : axisL2), childAxes: axisL2,
                    p: { items: [], page: 0, lastPage: 1, total: 0, q: '', loading: false, loadingMore: false, loaded: false, open: false, active: -1, timer: null },
                    c: { items: [], page: 0, lastPage: 1, total: 0, q: '', loading: false, loadingMore: false, loaded: false, open: false, active: -1, timer: null },
                    addFor: null, newValues: {}, newSku: '', skuEdited: false, creating: false,
                    totalVariants: treeData.totalVariants != null ? treeData.totalVariants : null,
                    navigating: false, pendingId: null,
                };
            },
            mounted() {
                this._away = (e) => {
                    // Use composedPath (captured at dispatch) rather than contains(e.target):
                    // clicking "Add New" swaps the button for the add-form in the same click,
                    // detaching the original target so contains() would wrongly report "outside".
                    const path = (e.composedPath && e.composedPath()) || [];
                    const inside = (ref) => ref && (path.includes(ref) || ref.contains(e.target));
                    if (this.p.open && !inside(this.$refs.pWrap)) { this.p.open = false; }
                    if (this.c.open && !inside(this.$refs.cWrap)) { this.c.open = false; }
                };
                document.addEventListener('mousedown', this._away);
            },
            beforeUnmount() { document.removeEventListener('mousedown', this._away); clearTimeout(this._navTimer); },
            computed: {
                pNode() { return this.currentNode('p'); },
                cNode() { return this.currentNode('c'); },
                addAxes() { return this.addFor === 'p' ? this.parentAxes : (this.addFor === 'c' ? this.childAxes : []); },
                addAxisLabel() { return this.label(this.addAxes); },
                addModalTitle() { return t('add-new', { attribute: this.addAxisLabel }); },
                addComplete() { return this.addAxes.length > 0 && this.addAxes.every(code => !! this.newValues[code]); },
                createLabel() { return t('create'); },
                createBusyLabel() { return t('creating'); },
            },
            methods: {
                t(key, params) { return t(key, params); },
                label(codes) {
                    return (Array.isArray(codes) ? codes : [codes])
                        .filter(Boolean)
                        .map(code => ATTR[code] || code)
                        .join(', ');
                },
                attrIdFor(code) { return this.attrId[code] != null ? String(this.attrId[code]) : ''; },
                axisPlaceholder(code) { return t('select', { attribute: this.label(code) }); },
                isCurrent(id) { return this.selected === id || this.currentGroupId === id; },
                isActive(id) { return this.isCurrent(id) || this.pendingId === id; },
                currentNode(w) {
                    return w === 'p'
                        ? (this.currentGroupId ? this.nodes[this.currentGroupId] : (this.selected !== this.configurableId ? this.nodes[this.selected] : null))
                        : (this.selected !== this.configurableId && this.selected !== this.currentGroupId ? this.nodes[this.selected] : null);
                },
                currentLabel(w) {
                    const node = this.currentNode(w);
                    if (!node || !node.fix) { return ''; }
                    const axes = w === 'p' ? this.parentAxes : this.childAxes;
                    return axes
                        .map(axis => node.fix[axis])
                        .filter(Boolean)
                        .map(code => this.axisLabels[code] || code)
                        .join(', ');
                },
                go(id) {
                    if (this.navigating) { return; }
                    if (id === this.selected) { this.p.open = false; this.c.open = false; return; }
                    this.navigating = true;
                    this.pendingId = id;
                    this._navTimer = setTimeout(() => { this.navigating = false; this.pendingId = null; }, 15000);
                    const url = EDIT_URL(id);
                    if (this.$navigate) { this.$navigate(url); } else { window.location.href = url; }
                },
                selectCommon() { this.go(this.configurableId); },
                stateFor(w) { return w === 'p' ? this.p : this.c; },
                toggle(w) {
                    if (this.navigating) { return; }
                    if (w === 'c' && ! this.currentGroupId) { return; }
                    const st = this.stateFor(w), other = w === 'p' ? this.c : this.p;
                    other.open = false;
                    st.open = !st.open;
                    if (st.open) {
                        st.active = -1;
                        if (!st.loaded) { this.fetch(w, true); }
                        this.$nextTick(() => { const el = this.$refs[w === 'p' ? 'pSearch' : 'cSearch']; if (el && el.focus) { el.focus(); } });
                    }
                },
                completenessClass(v) {
                    if (v >= 100) { return 'text-green-600 border-green-300 bg-green-50 dark:bg-transparent'; }
                    if (v >= 50) { return 'text-amber-600 border-amber-300 bg-amber-50 dark:bg-transparent'; }
                    return 'text-red-600 border-red-300 bg-red-50 dark:bg-transparent';
                },
                countClass(done, total) {
                    if (total > 0 && done >= total) { return 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300'; }
                    return 'bg-gray-100 text-gray-500 dark:bg-cherry-800 dark:text-gray-300';
                },
                onKey(e, w) {
                    const st = this.stateFor(w);
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        st.active = Math.min(st.active + 1, st.items.length - 1);
                        this.scrollActive(w);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        st.active = Math.max(st.active - 1, 0);
                        this.scrollActive(w);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (st.active >= 0 && st.items[st.active]) { this.go(st.items[st.active].id); }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        st.open = false;
                    }
                },
                scrollActive(w) {
                    this.$nextTick(() => {
                        const list = this.$refs[w === 'p' ? 'pList' : 'cList'];
                        if (!list) { return; }
                        const row = list.children[this.stateFor(w).active];
                        if (row && row.scrollIntoView) { row.scrollIntoView({ block: 'nearest' }); }
                    });
                },
                fetch(w, reset) {
                    const st = this.stateFor(w);
                    const parentId = w === 'p' ? null : this.currentGroupId;
                    if (reset) { st.page = 0; st.lastPage = 1; st.items = []; st.loading = true; } else { st.loadingMore = true; }
                    const nextPage = st.page + 1;
                    this.$axios.get(CHILDREN_URL(this.configurableId), { params: { parent_id: parentId, query: st.q || '', page: nextPage, perPage: 50 } })
                        .then(r => {
                            const d = r.data || {};
                            const mapped = (d.options || []).map(o => ({ id: o.id, label: o.label || o.code || o.sku, sku: o.sku, image: o.image || null, completeness: o.completeness != null ? o.completeness : null, variantTotal: o.variantTotal != null ? o.variantTotal : null, variantComplete: o.variantComplete != null ? o.variantComplete : null }));
                            st.items = reset ? mapped : st.items.concat(mapped);
                            if (reset) { st.active = -1; }
                            st.page = d.page || nextPage; st.lastPage = d.lastPage || st.page; st.total = d.total != null ? d.total : st.items.length;
                            st.loading = false; st.loadingMore = false; st.loaded = true;
                        })
                        .catch(() => { st.loading = false; st.loadingMore = false; });
                },
                onSearch(w) { const st = this.stateFor(w); clearTimeout(st.timer); st.timer = setTimeout(() => this.fetch(w, true), 300); },
                onScroll(e, w) {
                    const st = this.stateFor(w), el = e.target;
                    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40 && st.page < st.lastPage && !st.loadingMore && !st.loading) { this.fetch(w, false); }
                },
                openAdd(w) {
                    this.p.open = false; this.c.open = false;
                    this.addFor = w; this.newValues = {}; this.newSku = ''; this.skuEdited = false;
                    this.$nextTick(() => { if (this.$refs.addModal) { this.$refs.addModal.open(); } });
                },
                cancelAdd() {
                    this.addFor = null; this.newValues = {}; this.newSku = ''; this.skuEdited = false;
                    // close() flips isOpen before it emits, so this re-entry from @close stops here.
                    if (this.$refs.addModal && this.$refs.addModal.isOpen) { this.$refs.addModal.close(); }
                },
                onPick(e, axis) {
                    const opt = e && e.target ? e.target.value : e;
                    if (!opt || !opt.code) { return; }
                    this.newValues = Object.assign({}, this.newValues, { [axis]: opt.code });
                    this.suggestSku();
                },
                // Keep the suggestion in step with the picks until the user edits it.
                suggestSku() {
                    if (this.skuEdited) { return; }
                    const base = this.nodes[this.rootId] && this.nodes[this.rootId].sku ? this.nodes[this.rootId].sku : 'sku';
                    const picked = this.addAxes.map(code => this.newValues[code]).filter(Boolean);
                    this.newSku = picked.length ? base + '-' + picked.join('-').toLowerCase() : '';
                },
                create(w) {
                    if (!this.addComplete || this.creating) { return; }
                    const role = (this.levels === 2 && w === 'p') ? 'variant_group' : 'simple';
                    const parentId = w === 'p' ? null : this.currentGroupId;
                    this.creating = true;
                    this.$axios.post(CREATE_URL(this.configurableId), { parent_id: parentId, role: role, values: this.newValues, sku: this.newSku || null })
                        .then(r => { const d = (r.data && r.data.data) || r.data || {}; if (d.redirect_url) { this.$navigate ? this.$navigate(d.redirect_url) : (window.location.href = d.redirect_url); } else if (d.id) { this.go(d.id); } })
                        .catch(error => {
                            this.creating = false;
                            const message = (error && error.response && error.response.data && error.response.data.message) || t('create-error');
                            this.$emitter && this.$emitter.emit('add-flash', { type: 'error', message: message });
                        });
                },
            },
        });
    </script>
    @endverbatim
@endPushOnce

{{-- Agenting PIM — Non-blocking side panel (Copilot style) --}}

@php
    $magicAiEnabled  = (bool) core()->getConfigData('general.magic_ai.settings.enabled');
    $magicAiPlatform = core()->getConfigData('general.magic_ai.settings.ai_platform') ?? 'openai';
    $magicAiModels   = core()->getConfigData('general.magic_ai.settings.api_model') ?? '';
    $magicAiModel    = trim(explode(',', $magicAiModels)[0]) ?: ucfirst($magicAiPlatform);

    $platformRepo    = app(\Webkul\MagicAI\Repository\MagicAIPlatformRepository::class);
    $aiPlatforms     = $platformRepo->getActivePlatformOptions();
    $defaultPlatform = collect($aiPlatforms)->firstWhere('is_default', true);
    $defaultPlatformId = $defaultPlatform['id'] ?? ($aiPlatforms[0]['id'] ?? null);
@endphp

<v-agenting-pim></v-agenting-pim>

@pushOnce('scripts')
<script type="text/x-template" id="v-agenting-pim-template">
    <div class="ap-shell">
        {{-- ── Backdrop (small screens: covers page behind panel) ── --}}
        <transition name="ap-fade">
            <div
                v-if="isOpen"
                class="ap-backdrop"
                @click="close"
            ></div>
        </transition>

        {{-- ── Side Panel ──────────────────────────────────── --}}
        <transition :name="noTransition ? '' : 'ap-slide'">
            <div
                v-if="isOpen"
                class="ap-panel"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-2.5 flex-shrink-0" style="background:linear-gradient(135deg,#6d28d9 0%,#7c3aed 50%,#8b5cf6 100%);">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                            <svg width="14" height="14" style="color:#fff;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        </div>
                        <div>
                            <p style="color:#fff;font-weight:600;font-size:13px;line-height:1.25;margin:0;">@lang('ai-agent::app.widget.panel-title')</p>
                            <p style="color:rgba(255,255,255,0.55);font-size:10px;line-height:1.25;margin:0;">@lang('ai-agent::app.widget.panel-subtitle')</p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <a href="{{ route('ai-agent.settings') }}" title="@lang('ai-agent::app.widget.ai-settings')" style="color:rgba(255,255,255,0.65);display:flex;align-items:center;padding:5px;border-radius:6px;text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        </a>
                        <button v-if="activeTab === 'chat' && messages.length > 0" @click="newSession" :title="trans.newConversation" style="color:rgba(255,255,255,0.65);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;padding:5px;border-radius:6px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        </button>
                        <button @click="close" :title="trans.close" style="color:rgba(255,255,255,0.65);background:transparent;border:none;cursor:pointer;display:flex;align-items:center;padding:5px;border-radius:6px;" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Product Context Banner --}}
                <div v-if="productContext" class="ap-context-banner">
                    <svg width="13" height="13" class="ap-context-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="ap-context-text" v-text="trans.editing + ': ' + (productContext.sku || trans.product + ' #' + productContext.id)"></span>
                    <button @click="productContext = null" class="ap-context-close">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Tab Bar --}}
                <div class="ap-tab-bar">
                    <button @click="activeTab = 'capabilities'" class="ap-tab-btn" :class="{ 'ap-tab-active': activeTab === 'capabilities' }">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        @lang('ai-agent::app.widget.capabilities')
                    </button>
                    <button @click="activeTab = 'chat'" class="ap-tab-btn" :class="{ 'ap-tab-active': activeTab === 'chat' }">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        @lang('ai-agent::app.widget.chat')
                        <span v-if="messages.filter(m => m.role === 'assistant').length > 0"
                            style="min-width:16px;height:16px;background:#ede9fe;color:#7c3aed;border-radius:9999px;font-size:8px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;padding:0 3px;"
                            v-text="messages.filter(m => m.role === 'assistant').length"></span>
                    </button>
                    <button @click="activeTab = 'sessions'" class="ap-tab-btn" :class="{ 'ap-tab-active': activeTab === 'sessions' }">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
                        @lang('ai-agent::app.widget.sessions')
                        <span v-if="sessions.length > 0"
                            style="min-width:16px;height:16px;background:#ede9fe;color:#7c3aed;border-radius:9999px;font-size:8px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;padding:0 3px;"
                            v-text="sessions.length"></span>
                    </button>
                </div>

                {{-- Capabilities Tab --}}
                <div v-show="activeTab === 'capabilities'" style="flex:1;overflow-y:auto;padding:16px;">
                    {{-- Search bar --}}
                    <div style="position:relative;margin-bottom:12px;">
                        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input
                            v-model="capabilitySearch"
                            type="text"
                            :placeholder="trans.searchCapabilities"
                            class="ap-search-input"
                            @focus="$event.target.style.borderColor='#7c3aed'"
                            @blur="$event.target.style.borderColor='#e5e7eb'"
                        />
                    </div>

                    {{-- Capability tiles --}}
                    <div v-if="filteredCapabilities.length" class="grid grid-cols-2 gap-2.5">
                        <button v-for="cap in filteredCapabilities" :key="cap.key" @click="activateCapability(cap)"
                            class="flex flex-col items-start gap-2 p-3 rounded-lg border border-gray-200 dark:border-cherry-700 hover:border-violet-300 dark:hover:border-violet-600 hover:bg-violet-50 dark:hover:bg-cherry-800 transition-all text-left group">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="{ background: cap.color + '15' }">
                                <span v-html="sanitizeSvg(cap.iconSvg)" :style="{ color: cap.color }"></span>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-200 group-hover:text-violet-700 dark:group-hover:text-violet-400 leading-tight" v-text="cap.label"></p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 leading-snug" v-text="cap.description"></p>
                            </div>
                        </button>
                    </div>
                    <p v-else class="text-xs text-gray-400 text-center py-8" v-text="trans.noMatch"></p>
                </div>

                {{-- Sessions Tab --}}
                <div v-show="activeTab === 'sessions'" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;">
                    {{-- New session button --}}
                    <div class="ap-session-new-wrap">
                        <button @click="createNewSession"
                            class="ap-session-new-btn">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            <span v-text="trans.newSession"></span>
                        </button>
                    </div>

                    {{-- Session list --}}
                    <div v-if="sessions.length" style="flex:1;overflow-y:auto;padding:8px;">
                        <div v-for="(session, idx) in sessions" :key="session.id"
                            @click="switchToSession(session.id)"
                            class="ap-session-card"
                            :class="{ 'ap-session-card-active': session.id === activeSessionId }">
                            {{-- Session icon --}}
                            <div class="ap-session-icon"
                                :style="{ background: session.id === activeSessionId ? '#7c3aed' : '#f3f4f6' }">
                                <svg width="14" height="14" fill="none" :stroke="session.id === activeSessionId ? '#fff' : '#9ca3af'" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </div>

                            {{-- Session info --}}
                            <div style="flex:1;min-width:0;">
                                <p class="ap-session-title" v-text="session.name"></p>
                                <p class="ap-session-meta">
                                    <span v-text="session.messageCount + ' ' + trans.messages"></span>
                                    &middot; <span v-text="session.lastActive"></span>
                                </p>
                            </div>

                            {{-- Active indicator --}}
                            <div v-if="session.id === activeSessionId"
                                style="width:8px;height:8px;border-radius:50%;background:#7c3aed;flex-shrink:0;"></div>

                            {{-- Delete button --}}
                            <button v-else @click.stop="deleteSession(session.id)"
                                :title="trans.deleteSession"
                                class="ap-session-delete"
                                @mouseenter="$event.currentTarget.style.color='#ef4444'"
                                @mouseleave="$event.currentTarget.style.color='#9ca3af'"
                            >
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Empty state --}}
                    <div v-else style="flex:1;display:flex;align-items:center;justify-content:center;padding:32px;">
                        <div style="text-align:center;">
                            <svg style="margin:0 auto 12px;color:#d1d5db;" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
                            <p style="font-size:12px;color:#9ca3af;" v-text="trans.noSessions"></p>
                        </div>
                    </div>
                </div>

                {{-- Chat Tab --}}
                <div v-show="activeTab === 'chat'" style="flex:1;display:flex;flex-direction:column;min-height:0;overflow:hidden;">

                    {{-- Chat sub-header: capability badge + clear --}}
                    <div class="ap-chat-subheader">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span v-if="activeCapability"
                                style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:9999px;"
                                :style="{ background: activeCapability.color + '18', color: activeCapability.color }"
                                v-text="activeCapability.label"></span>
                            <span v-else class="ap-chat-meta" v-text="trans.generalChat"></span>
                            <span v-if="messages.length > 0" class="ap-chat-meta">· <span v-text="messages.filter(m => m.role === 'user').length"></span> @lang('ai-agent::app.widget.messages')</span>
                        </div>
                        <button v-if="messages.length > 0" @click="clearChat"
                            class="ap-clear-chat-btn"
                            :title="trans.clearChat">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            @lang('ai-agent::app.widget.clear-chat')
                        </button>
                    </div>

                    <div ref="messagesEl" style="flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:18px;min-height:0;">
                        {{-- Empty state --}}
                        <div v-if="messages.length === 0 && !isLoading" class="flex flex-col items-center justify-center h-full text-center py-8">
                            <svg class="w-10 h-10 text-violet-200 dark:text-violet-800 mb-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                <template v-if="activeCapability"><span v-text="trans.readyFor"></span> <strong class="text-violet-600 dark:text-violet-400" v-text="activeCapability.label"></strong></template>
                                <template v-else><span v-text="trans.catalogHelp"></span></template>
                            </p>
                            <p v-if="activeCapability" class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5 max-w-[260px] leading-relaxed" v-text="activeCapability.hint"></p>
                            <p v-if="productContext" class="text-[11px] text-violet-500 dark:text-violet-400 mt-2"><span v-text="trans.context"></span>: <strong v-text="productContext.sku || trans.product + ' #' + productContext.id"></strong></p>
                        </div>

                        <template v-for="(msg, idx) in messages" :key="idx">
                            {{-- User --}}
                            <div v-if="msg.role === 'user'" class="flex justify-end gap-2 items-end">
                                <div class="max-w-[85%] space-y-1.5">
                                    <div v-if="msg.files && msg.files.length" class="flex flex-wrap gap-1.5 justify-end">
                                        <template v-for="(f, fi) in msg.files" :key="fi">
                                            <img v-if="f.type === 'image'" :src="f.preview" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-cherry-700"/>
                                            <div v-else class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 text-violet-600 dark:text-violet-400">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                <span class="max-w-[100px] truncate font-medium" v-text="f.name"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div v-if="msg.content" style="background:#7c3aed;color:#fff;font-size:13px;padding:10px 14px;border-radius:14px 14px 4px 14px;white-space:pre-wrap;line-height:1.55;max-width:100%;word-break:break-word;" v-text="msg.content"></div>
                                </div>
                            </div>

                            {{-- Assistant --}}
                            <div v-else>
                                <div class="space-y-2.5">
                                    {{-- Response --}}
                                    <div style="padding:2px 0;">
                                        <div class="text-[13px] text-gray-700 dark:text-gray-300 leading-[1.75] ap-ai-response" v-html="renderMarkdown(renderContent(msg, idx))"></div>
                                    </div>

                                    {{-- Result details card --}}
                                    <div v-if="msg.result && Object.keys(msg.result).length" class="rounded-xl border border-violet-200 dark:border-cherry-700 overflow-hidden">
                                        <div class="px-4 py-2 border-b border-violet-100 dark:border-cherry-700" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);">
                                            <p class="text-[10px] font-bold text-violet-500 dark:text-violet-400 uppercase tracking-wider" v-text="trans.result"></p>
                                        </div>
                                        <div class="px-4 py-3 space-y-2 bg-white dark:bg-cherry-800">
                                            <template v-for="(val, key) in msg.result" :key="key">
                                                <div v-if="val !== null && val !== ''" class="text-xs leading-relaxed">
                                                    <span class="text-violet-400 dark:text-violet-500 capitalize font-semibold" v-text="String(key).replace(/_/g, ' ') + ': '"></span>
                                                    {{-- Boolean --}}
                                                    <span v-if="typeof val === 'boolean'" class="inline-flex items-center gap-1 font-semibold" :class="val ? 'text-emerald-600' : 'text-red-400'">
                                                        <span v-text="val ? '✓ Yes' : '✗ No'"></span>
                                                    </span>
                                                    {{-- Array --}}
                                                    <template v-else-if="Array.isArray(val)">
                                                        <div v-if="val.length === 0" class="mt-0.5 text-gray-400 italic">None</div>
                                                        <div v-else class="mt-1 flex flex-wrap gap-1">
                                                            <span v-for="(item, i) in val" :key="i" class="inline-block px-2 py-0.5 rounded-md text-[11px] font-medium bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-800" v-text="item"></span>
                                                        </div>
                                                    </template>
                                                    {{-- Number --}}
                                                    <span v-else-if="typeof val === 'number'" class="font-bold text-gray-800 dark:text-gray-200" v-text="val"></span>
                                                    {{-- String --}}
                                                    <span v-else class="text-gray-700 dark:text-gray-300 font-medium" v-text="val"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Action buttons --}}
                                    <div class="flex gap-2.5 flex-wrap mt-3 mb-1">
                                        <a v-if="msg.product_url" :href="msg.product_url" class="inline-flex items-center gap-1.5 text-xs font-semibold text-white px-4 py-2 rounded-lg transition-all hover:shadow-md" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            @lang('ai-agent::app.widget.view-product')
                                        </a>
                                        <button
                                            v-if="msg.download_url"
                                            @click="downloadFile(msg.download_url)"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-white px-4 py-2 rounded-lg transition-all hover:shadow-md"
                                            style="background:linear-gradient(135deg,#059669,#10b981);border:none;cursor:pointer;"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            @lang('ai-agent::app.widget.download')
                                        </button>
                                    </div>

                                    {{-- Message actions: retry, copy, helpful, not helpful — shown AFTER result card --}}
                                    <div v-if="!msg.isStreaming && !msg.isRedirect" class="flex items-center gap-0.5 ap-msg-actions" style="margin-top:2px;">
                                        <button @click="retryFrom(idx)" title="Retry" class="ap-action-btn">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                        </button>
                                        <button @click="copyMessage(idx)" :title="msg._copied ? 'Copied!' : 'Copy'" class="ap-action-btn">
                                            <svg v-if="!msg._copied" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </button>
                                        <button @click="rateMessage(idx, 'helpful')" title="Helpful" class="ap-action-btn" :class="{ 'ap-action-active': msg._rating === 'helpful' }">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                                        </button>
                                        <button @click="rateMessage(idx, 'not_helpful')" title="Not helpful" class="ap-action-btn" :class="{ 'ap-action-active': msg._rating === 'not_helpful' }">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/></svg>
                                        </button>
                                    </div>

                                    {{-- Confirmation buttons — at the very bottom --}}
                                    <div v-if="needsConfirmation(msg, idx)" class="flex gap-2 mt-1">
                                        <button @click="confirmAction('yes', idx)" :disabled="isLoading" class="inline-flex items-center gap-1.5 text-xs font-semibold text-white px-4 py-2 rounded-lg transition-all hover:shadow-md" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            Yes, proceed
                                        </button>
                                        <button @click="confirmAction('no', idx)" :disabled="isLoading" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-cherry-800 transition-all">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            No
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Typing / Streaming indicator --}}
                        <div v-if="isLoading">
                            <div style="padding:2px 0;">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                    <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                    <span v-if="streamingStatus" class="text-xs text-violet-500 dark:text-violet-400 ml-1 font-medium" v-text="streamingStatus"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pending files --}}
                    <div v-if="pendingFiles.length > 0" class="ap-pending-files">
                        <div v-for="(f, idx) in pendingFiles" :key="idx" class="relative group">
                            <img v-if="f.type === 'image'" :src="f.preview" class="w-10 h-10 object-cover rounded-md border border-gray-200 dark:border-cherry-700"/>
                            <div v-else class="flex items-center gap-1 px-2 py-1.5 rounded-md border text-xs bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800 text-violet-600 dark:text-violet-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span class="max-w-[80px] truncate font-medium" v-text="f.name"></span>
                            </div>
                            <button @click="removeFile(idx)" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] hidden group-hover:flex items-center justify-center shadow-sm">&times;</button>
                        </div>
                    </div>

                    {{-- Input — always-visible bordered box --}}
                    <div class="ap-input-wrap">

                        {{-- Outer bordered container --}}
                        <div class="ap-input-box">

                            {{-- Textarea --}}
                            <textarea
                                ref="textInput"
                                v-model="inputText"
                                @keydown.enter.exact.prevent="send"
                                rows="3"
                                class="ap-input-textarea"
                                :placeholder="inputPlaceholder"
                                :disabled="isLoading"
                                @input="autoResize"
                                @focus="$event.target.parentElement.style.border='1.5px solid #7c3aed'"
                                @blur="$event.target.parentElement.style.border='1.5px solid #d1d5db'"
                            ></textarea>

                            {{-- Toolbar row: Attach + Platform/Model + Send --}}
                            <div class="ap-input-toolbar">

                                {{-- Left: attach + platform + model --}}
                                <div style="display:flex;align-items:center;gap:4px;flex:1;min-width:0;">
                                    <label
                                        :title="fileInputTitle"
                                        class="ap-input-chip">
                                        <svg v-if="activeCapability && activeCapability.acceptsSpreadsheet" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                        <svg v-else width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        <input type="file" ref="fileInput" class="hidden" :accept="fileAccept" multiple @change="onFileSelect"/>
                                    </label>

                                    <template v-if="platforms.length > 0">
                                        <select
                                            v-model="selectedPlatformId"
                                            @change="onPlatformChange"
                                            class="ap-input-select"
                                            :title="trans.selectPlatform"
                                        >
                                            <option v-for="p in platforms" :key="p.id" :value="p.id" v-text="p.label"></option>
                                        </select>
                                        <select
                                            v-model="selectedModel"
                                            class="ap-input-select"
                                            :title="trans.selectModel"
                                        >
                                            <option v-for="m in availableModels" :key="m" :value="m" v-text="m"></option>
                                        </select>
                                    </template>
                                </div>

                                {{-- Right: Send button --}}
                                <button
                                    @click="send"
                                    :disabled="isLoading || (!inputText.trim() && pendingFiles.length === 0)"
                                    style="display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;background:linear-gradient(135deg,#7c3aed,#8b5cf6);transition:opacity 0.2s,transform 0.1s;"
                                    :style="{ opacity: (isLoading || (!inputText.trim() && pendingFiles.length === 0)) ? '0.35' : '1', cursor: (isLoading || (!inputText.trim() && pendingFiles.length === 0)) ? 'not-allowed' : 'pointer' }"
                                    :title="isLoading ? trans.sending : trans.send"
                                >
                                    <svg v-if="!isLoading" width="14" height="14" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2" fill="white" stroke="none"/></svg>
                                    <svg v-else class="animate-spin" width="14" height="14" fill="none" viewBox="0 0 24 24"><circle style="opacity:.3" cx="12" cy="12" r="10" stroke="white" stroke-width="4"/><path style="opacity:.8" fill="white" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                </button>
                            </div>
                        </div>

                        <p class="ap-input-footnote">@lang('ai-agent::app.widget.enter-to-send') &middot; @lang('ai-agent::app.widget.shift-enter-newline')</p>
                    </div>
                </div>
            </div>
        </transition>

        {{-- ── Trigger Button ──────────────────────────────── --}}
        <button
            v-show="!isOpen"
            @click="toggle"
            :title="trans.openPanel"
            style="position:fixed;bottom:24px;right:24px;z-index:10002;width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(124,58,237,0.4);border:none;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;"
            @mouseenter="$event.currentTarget.style.transform='scale(1.1)';$event.currentTarget.style.boxShadow='0 6px 20px rgba(124,58,237,0.5)'"
            @mouseleave="$event.currentTarget.style.transform='';$event.currentTarget.style.boxShadow='0 4px 15px rgba(124,58,237,0.4)'"
        >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
        </button>
    </div>
</script>

<style>
/* Panel base — desktop (>1024px): 420px sidebar */
.ap-panel {
    position: fixed; top: 0; right: 0; height: 100vh;
    display: flex; flex-direction: column;
    background: #fff; border-left: 1px solid #e5e7eb;
    width: 420px; max-width: 100vw; z-index: 10000;
}
.dark .ap-panel {
    background: #1f1b2d;
    border-left-color: #453c5f;
}

/* Backdrop — hidden on desktop */
.ap-backdrop { display: none; }

/* Tablet & mobile: full-screen overlay above navbar */
@media (max-width: 1024px) {
    .ap-panel {
        width: 100vw;
        border-left: none;
        z-index: 10002;
    }

    .ap-backdrop {
        display: block;
        position: fixed; inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10001;
    }
}

/* Backdrop fade */
.ap-fade-enter-active, .ap-fade-leave-active { transition: opacity 0.2s ease; }
.ap-fade-enter-from, .ap-fade-leave-to { opacity: 0; }

/* Panel slide */
.ap-slide-enter-active, .ap-slide-leave-active { transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.ap-slide-enter-from, .ap-slide-leave-to { transform: translateX(100%); }

/* AI response text styling */
.ap-ai-response { word-break: break-word; }
.ap-ai-response strong { color: #5b21b6; font-weight: 700; }
.dark .ap-ai-response strong { color: #c4b5fd; }
.ap-ai-response code { background: #ede9fe; color: #6d28d9; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: 500; }
.dark .ap-ai-response code { background: rgba(139,92,246,0.15); color: #a78bfa; }
.ap-ai-response a { color: #7c3aed; text-decoration: underline; text-underline-offset: 2px; }
.ap-ai-response a:hover { color: #6d28d9; text-decoration: none; }

/* Bullet and numbered list spacing */
.ap-ai-response p { margin-bottom: 2px; }
.ap-ai-response br + br { display: block; content: ''; margin-top: 6px; }

/* Message action buttons */
.ap-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px; border: none;
    background: transparent; color: #9ca3af; cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.ap-action-btn:hover { background: #f3f4f6; color: #6b7280; }
.dark .ap-action-btn:hover { background: rgba(255,255,255,0.08); color: #d1d5db; }
.ap-action-btn.ap-action-active { color: #7c3aed; }
.dark .ap-action-btn.ap-action-active { color: #a78bfa; }
.ap-msg-actions { opacity: 0.4; transition: opacity 0.2s; }
.ap-msg-actions:hover { opacity: 1; }

.ap-context-banner {
    display:flex; align-items:center; gap:8px; padding:6px 16px;
    background:#f5f3ff; border-bottom:1px solid #e9d5ff; flex-shrink:0;
}
.dark .ap-context-banner { background:#2b223d; border-bottom-color:#5b4a80; }
.ap-context-icon { flex-shrink:0; color:#7c3aed; }
.dark .ap-context-icon { color:#c4b5fd; }
.ap-context-text {
    font-size:11px; color:#5b21b6; font-weight:500; flex:1;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.dark .ap-context-text { color:#ddd6fe; }
.ap-context-close {
    color:#8b5cf6; background:none; border:none; cursor:pointer; padding:0;
    display:flex; align-items:center;
}
.dark .ap-context-close { color:#c4b5fd; }

.ap-tab-bar {
    display:flex; border-bottom:1px solid #e5e7eb; background:#f9fafb; flex-shrink:0;
}
.dark .ap-tab-bar { background:#241f35; border-bottom-color:#453c5f; }
.ap-tab-btn {
    flex:1; padding:8px 12px; font-size:11px; cursor:pointer; border:none;
    display:flex; align-items:center; justify-content:center; gap:4px;
    transition:all 0.15s; color:#6b7280; background:transparent;
}
.dark .ap-tab-btn { color:#b9b4cb; }
.ap-tab-btn.ap-tab-active {
    border-bottom:2px solid #7c3aed; color:#7c3aed; font-weight:600; background:#fff;
}
.dark .ap-tab-btn.ap-tab-active { background:#1f1b2d; color:#c4b5fd; border-bottom-color:#a78bfa; }

.ap-search-input {
    width:100%; padding:7px 10px 7px 32px; font-size:12px;
    border:1px solid #e5e7eb; border-radius:8px; outline:none;
    background:#f9fafb; color:#374151; box-sizing:border-box;
}
.dark .ap-search-input { background:#241f35; border-color:#453c5f; color:#e5e7eb; }

.ap-session-new-wrap { padding:12px 16px; border-bottom:1px solid #e5e7eb; flex-shrink:0; }
.dark .ap-session-new-wrap { border-bottom-color:#453c5f; }
.ap-session-new-btn {
    width:100%; display:flex; align-items:center; justify-content:center; gap:6px;
    padding:8px; font-size:12px; font-weight:600; color:#7c3aed;
    background:#f5f3ff; border:1.5px dashed #c4b5fd; border-radius:8px;
    cursor:pointer; transition:all 0.15s;
}
.ap-session-new-btn:hover { background:#ede9fe; }
.dark .ap-session-new-btn { background:#2b223d; border-color:#6d5c94; color:#c4b5fd; }
.dark .ap-session-new-btn:hover { background:#34284b; }

.ap-session-card {
    display:flex; align-items:center; gap:10px; padding:10px 12px; margin-bottom:6px;
    border-radius:8px; border:1px solid #e5e7eb; cursor:pointer; transition:all 0.15s;
    background:#fff;
}
.dark .ap-session-card { background:#1f1b2d; border-color:#453c5f; }
.ap-session-card:hover:not(.ap-session-card-active) { background:#fafafa; }
.dark .ap-session-card:hover:not(.ap-session-card-active) { background:#2a233c; }
.ap-session-card-active { background:#f5f3ff; border-color:#c4b5fd; }
.dark .ap-session-card-active { background:#2b223d; border-color:#8b7bb8; }
.ap-session-icon {
    width:32px; height:32px; border-radius:8px; display:flex; align-items:center;
    justify-content:center; flex-shrink:0;
}
.ap-session-title {
    font-size:12px; font-weight:600; color:#374151; margin:0;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.dark .ap-session-title { color:#f3f4f6; }
.ap-session-meta { font-size:10px; color:#9ca3af; margin:2px 0 0; }
.dark .ap-session-meta { color:#b9b4cb; }
.ap-session-delete {
    padding:4px; color:#9ca3af; background:none; border:none; cursor:pointer;
    border-radius:4px; display:flex; align-items:center;
}

.ap-chat-subheader {
    display:flex; align-items:center; justify-content:space-between; padding:6px 16px;
    background:#f9fafb; border-bottom:1px solid #e5e7eb; flex-shrink:0;
}
.dark .ap-chat-subheader { background:#241f35; border-bottom-color:#453c5f; }
.ap-chat-meta { font-size:10px; color:#9ca3af; font-weight:500; }
.dark .ap-chat-meta { color:#b9b4cb; }
.ap-clear-chat-btn {
    display:flex; align-items:center; gap:4px; font-size:10px; color:#ef4444;
    padding:3px 8px; border-radius:6px; border:1px solid #fecaca;
    background:#fff5f5; cursor:pointer; transition:all 0.15s;
}
.dark .ap-clear-chat-btn { background:#3b1f29; border-color:#7f4456; color:#fda4af; }
.ap-clear-chat-btn:hover { background:#ffe7e7; }
.dark .ap-clear-chat-btn:hover { background:#4a2733; }

.ap-pending-files {
    display:flex; flex-wrap:wrap; gap:8px; padding:8px 16px;
    border-top:1px solid #e5e7eb; background:#f9fafb; flex-shrink:0;
}
.dark .ap-pending-files { background:#241f35; border-top-color:#453c5f; }

.ap-input-wrap {
    border-top:1px solid #e5e7eb; padding:12px; flex-shrink:0; background:#fff;
    position:relative; z-index:1;
}
.dark .ap-input-wrap { background:#1f1b2d; border-top-color:#453c5f; }
.ap-input-box {
    border:1.5px solid #d1d5db; border-radius:12px; background:#f9fafb; overflow:hidden;
}
.dark .ap-input-box { background:#241f35; border-color:#5b4a80; }
.ap-input-textarea {
    width:100%; resize:none; font-size:13px; color:#374151; background:transparent;
    padding:12px 14px 6px; border:none; outline:none; min-height:76px;
    max-height:160px; line-height:1.55; display:block; box-sizing:border-box;
}
.dark .ap-input-textarea { color:#f3f4f6; }
.ap-input-textarea::placeholder { color:#9ca3af; }
.dark .ap-input-textarea::placeholder { color:#9f97b8; }
.ap-input-toolbar {
    display:flex; align-items:center; justify-content:space-between; padding:6px 8px;
    border-top:1px solid #f3f4f6; gap:4px;
}
.dark .ap-input-toolbar { border-top-color:#453c5f; }
.ap-input-chip {
    display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#6b7280;
    padding:4px 8px; border-radius:6px; border:1px solid #e5e7eb; background:#fff;
    cursor:pointer; transition:background 0.15s; flex-shrink:0;
}
.ap-input-chip:hover { background:#f5f0ff; color:#7c3aed; border-color:#c4b5fd; }
.dark .ap-input-chip { background:#1f1b2d; border-color:#5b4a80; color:#d1d5db; }
.dark .ap-input-chip:hover { background:#34284b; color:#ddd6fe; border-color:#8b7bb8; }
.ap-input-select {
    font-size:10px; color:#6b7280; background:#fff; border:1px solid #e5e7eb;
    border-radius:6px; padding:3px 6px; cursor:pointer; outline:none;
    max-width:120px; min-width:0; flex-shrink:1;
}
.dark .ap-input-select { background:#1f1b2d; border-color:#5b4a80; color:#f3f4f6; }
.ap-input-select:focus { border-color:#7c3aed; }
.dark .ap-input-select:focus { border-color:#a78bfa; }
.ap-input-footnote { font-size:10px; color:#9ca3af; text-align:center; margin-top:6px; }
.dark .ap-input-footnote { color:#9f97b8; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.0/purify.min.js" integrity="sha384-/knAMB4gMqm3mPGf8xMfFjCF0Fw3GMdmF6Bj25kjGp9TzFKGefvtsYzn/7BNEUU" crossorigin="anonymous"></script>

<script type="module">
app.component('v-agenting-pim', {
    template: '#v-agenting-pim-template',

    data() {
        const svg = (d, opts = {}) => {
            const w = opts.w || 16, h = opts.h || 16;
            return `<svg width="${w}" height="${h}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
        };
        const platforms = @json($aiPlatforms);
        const defaultPlatformId = {{ $defaultPlatformId ?? 'null' }};
        const initialPlatform = platforms.find(p => p.id === defaultPlatformId) || platforms[0] || null;
        const initialModels = initialPlatform ? (initialPlatform.models || []) : [];

        const trans = {
            newConversation: `@lang('ai-agent::app.widget.new-conversation')`,
            close: `@lang('ai-agent::app.widget.close')`,
            openPanel: `@lang('ai-agent::app.widget.open-panel')`,
            editing: `@lang('ai-agent::app.widget.editing')`,
            product: `@lang('ai-agent::app.widget.product')`,
            selectOperation: `@lang('ai-agent::app.widget.select-operation')`,
            searchCapabilities: `@lang('ai-agent::app.widget.search-capabilities')`,
            noMatch: `@lang('ai-agent::app.widget.no-match')`,
            generalChat: `@lang('ai-agent::app.widget.general-chat')`,
            clearChat: `@lang('ai-agent::app.widget.clear-chat')`,
            sessions: `@lang('ai-agent::app.widget.sessions')`,
            newSession: `@lang('ai-agent::app.widget.new-session')`,
            noSessions: `@lang('ai-agent::app.widget.no-sessions')`,
            sessionDefaultName: `@lang('ai-agent::app.widget.session-default-name')`,
            deleteSession: `@lang('ai-agent::app.widget.delete-session')`,
            renameSession: `@lang('ai-agent::app.widget.rename-session')`,
            readyFor: `@lang('ai-agent::app.widget.ready-for')`,
            catalogHelp: `@lang('ai-agent::app.widget.catalog-help')`,
            context: `@lang('ai-agent::app.widget.context')`,
            result: `@lang('ai-agent::app.widget.result')`,
            selectPlatform: `@lang('ai-agent::app.widget.select-platform')`,
            selectModel: `@lang('ai-agent::app.widget.select-model')`,
            attachCsvXlsx: `@lang('ai-agent::app.widget.attach-csv-xlsx')`,
            attachImage: `@lang('ai-agent::app.widget.attach-image')`,
            processing: `@lang('ai-agent::app.widget.processing')`,
            noResponse: `@lang('ai-agent::app.widget.no-response')`,
            errorGeneric: `@lang('ai-agent::app.widget.error-generic')`,
            askCatalog: `@lang('ai-agent::app.widget.ask-catalog')`,
            send: `@lang('ai-agent::app.widget.send')`,
            sending: `@lang('ai-agent::app.widget.sending')`,
            openingProduct: `@lang('ai-agent::app.widget.opening-product')`,
        };

        return {
            isOpen: false,
            activeTab: 'capabilities',
            activeCapability: null,
            messages: [],
            inputText: '',
            pendingFiles: [],
            isLoading: false,
            streamingStatus: '',
            productContext: null,
            noTransition: false,
            capabilitySearch: '',
            // Session management
            sessions: [],
            activeSessionId: null,
            showSessions: false,
            platforms: platforms,
            selectedPlatformId: initialPlatform ? initialPlatform.id : null,
            selectedModel: initialModels[0] || '',
            trans: trans,
            capabilities: [
                // Row 1: Product creation & updates
                { key: 'create_from_image', label: `@lang('ai-agent::app.widget.capabilities-list.create-from-image')`, description: `@lang('ai-agent::app.widget.capabilities-list.create-from-image-desc')`,
                  iconSvg: svg('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'),
                  color: '#7C3AED', hint: `@lang('ai-agent::app.widget.capabilities-list.create-from-image-hint')`, acceptsImages: true, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.create-from-image-prompt')`, autoFileUpload: true },
                { key: 'update_products', label: `@lang('ai-agent::app.widget.capabilities-list.update-products')`, description: `@lang('ai-agent::app.widget.capabilities-list.update-products-desc')`,
                  iconSvg: svg('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'),
                  color: '#059669', hint: `@lang('ai-agent::app.widget.capabilities-list.update-products-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.update-products-prompt')`, autoFileUpload: false },
                // Row 2: Search & explore
                { key: 'search_products', label: `@lang('ai-agent::app.widget.capabilities-list.search-products')`, description: `@lang('ai-agent::app.widget.capabilities-list.search-products-desc')`,
                  iconSvg: svg('<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>'),
                  color: '#2563EB', hint: `@lang('ai-agent::app.widget.capabilities-list.search-products-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.search-products-prompt')`, autoFileUpload: false },
                { key: 'find_similar', label: `@lang('ai-agent::app.widget.capabilities-list.find-similar')`, description: `@lang('ai-agent::app.widget.capabilities-list.find-similar-desc')`,
                  iconSvg: svg('<path d="M16 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="12" height="12" rx="2"/>'),
                  color: '#8B5CF6', hint: `@lang('ai-agent::app.widget.capabilities-list.find-similar-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.find-similar-prompt')`, autoFileUpload: false },
                // Row 3: Content & AI generation
                { key: 'generate_content', label: `@lang('ai-agent::app.widget.capabilities-list.generate-content')`, description: `@lang('ai-agent::app.widget.capabilities-list.generate-content-desc')`,
                  iconSvg: svg('<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>'),
                  color: '#F59E0B', hint: `@lang('ai-agent::app.widget.capabilities-list.generate-content-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.generate-content-prompt')`, autoFileUpload: false },
                { key: 'generate_image', label: `@lang('ai-agent::app.widget.capabilities-list.generate-image')`, description: `@lang('ai-agent::app.widget.capabilities-list.generate-image-desc')`,
                  iconSvg: svg('<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8"/><path d="M8 12h8"/>'),
                  color: '#EC4899', hint: `@lang('ai-agent::app.widget.capabilities-list.generate-image-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.generate-image-prompt')`, autoFileUpload: false },
                // Row 4: Categories & attributes
                { key: 'assign_categories', label: `@lang('ai-agent::app.widget.capabilities-list.assign-categories')`, description: `@lang('ai-agent::app.widget.capabilities-list.assign-categories-desc')`,
                  iconSvg: svg('<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>'),
                  color: '#6366F1', hint: `@lang('ai-agent::app.widget.capabilities-list.assign-categories-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.assign-categories-prompt')`, autoFileUpload: false },
                { key: 'list_attributes', label: `@lang('ai-agent::app.widget.capabilities-list.list-attributes')`, description: `@lang('ai-agent::app.widget.capabilities-list.list-attributes-desc')`,
                  iconSvg: svg('<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'),
                  color: '#14B8A6', hint: `@lang('ai-agent::app.widget.capabilities-list.list-attributes-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.list-attributes-prompt')`, autoFileUpload: false },
                // Row 5: Image editing & export
                { key: 'edit_image', label: `@lang('ai-agent::app.widget.capabilities-list.edit-image')`, description: `@lang('ai-agent::app.widget.capabilities-list.edit-image-desc')`,
                  iconSvg: svg('<circle cx="12" cy="12" r="3"/><path d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12z" stroke-dasharray="4 2"/>'),
                  color: '#D946EF', hint: `@lang('ai-agent::app.widget.capabilities-list.edit-image-hint')`, acceptsImages: true, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.edit-image-prompt')`, autoFileUpload: true },
                { key: 'export_products', label: `@lang('ai-agent::app.widget.capabilities-list.export-products')`, description: `@lang('ai-agent::app.widget.capabilities-list.export-products-desc')`,
                  iconSvg: svg('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'),
                  color: '#0891B2', hint: `@lang('ai-agent::app.widget.capabilities-list.export-products-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.export-products-prompt')`, autoFileUpload: false },
                // Row 6: Bulk & destructive operations
                { key: 'upload_csv', label: `@lang('ai-agent::app.widget.capabilities-list.bulk-import-csv')`, description: `@lang('ai-agent::app.widget.capabilities-list.bulk-import-csv-desc')`,
                  iconSvg: svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>'),
                  color: '#D97706', hint: `@lang('ai-agent::app.widget.capabilities-list.bulk-import-csv-hint')`, acceptsImages: false, acceptsSpreadsheet: true,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.bulk-import-csv-prompt')`, autoFileUpload: true },
                { key: 'delete_products', label: `@lang('ai-agent::app.widget.capabilities-list.delete-products')`, description: `@lang('ai-agent::app.widget.capabilities-list.delete-products-desc')`,
                  iconSvg: svg('<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>'),
                  color: '#DC2626', hint: `@lang('ai-agent::app.widget.capabilities-list.delete-products-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.delete-products-prompt')`, autoFileUpload: false },
                // Row 7: Category management
                { key: 'create_category', label: `@lang('ai-agent::app.widget.capabilities-list.create-category')`, description: `@lang('ai-agent::app.widget.capabilities-list.create-category-desc')`,
                  iconSvg: svg('<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/>'),
                  color: '#6366F1', hint: `@lang('ai-agent::app.widget.capabilities-list.create-category-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.create-category-prompt')`, autoFileUpload: false },
                { key: 'category_tree', label: `@lang('ai-agent::app.widget.capabilities-list.category-tree')`, description: `@lang('ai-agent::app.widget.capabilities-list.category-tree-desc')`,
                  iconSvg: svg('<path d="M6 3v12"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>'),
                  color: '#8B5CF6', hint: `@lang('ai-agent::app.widget.capabilities-list.category-tree-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.category-tree-prompt')`, autoFileUpload: false },
                // Row 8: Attribute management
                { key: 'create_attribute', label: `@lang('ai-agent::app.widget.capabilities-list.create-attribute')`, description: `@lang('ai-agent::app.widget.capabilities-list.create-attribute-desc')`,
                  iconSvg: svg('<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>'),
                  color: '#0D9488', hint: `@lang('ai-agent::app.widget.capabilities-list.create-attribute-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.create-attribute-prompt')`, autoFileUpload: false },
                { key: 'manage_options', label: `@lang('ai-agent::app.widget.capabilities-list.manage-options')`, description: `@lang('ai-agent::app.widget.capabilities-list.manage-options-desc')`,
                  iconSvg: svg('<polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'),
                  color: '#14B8A6', hint: `@lang('ai-agent::app.widget.capabilities-list.manage-options-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.manage-options-prompt')`, autoFileUpload: false },
                // Row 9: Families & bulk ops
                { key: 'manage_families', label: `@lang('ai-agent::app.widget.capabilities-list.manage-families')`, description: `@lang('ai-agent::app.widget.capabilities-list.manage-families-desc')`,
                  iconSvg: svg('<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>'),
                  color: '#7C3AED', hint: `@lang('ai-agent::app.widget.capabilities-list.manage-families-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.manage-families-prompt')`, autoFileUpload: false },
                { key: 'bulk_edit', label: `@lang('ai-agent::app.widget.capabilities-list.bulk-edit')`, description: `@lang('ai-agent::app.widget.capabilities-list.bulk-edit-desc')`,
                  iconSvg: svg('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/><line x1="4" y1="20" x2="20" y2="20"/>'),
                  color: '#EA580C', hint: `@lang('ai-agent::app.widget.capabilities-list.bulk-edit-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.bulk-edit-prompt')`, autoFileUpload: false },
                // Row 10: System & admin
                { key: 'catalog_summary', label: `@lang('ai-agent::app.widget.capabilities-list.catalog-summary')`, description: `@lang('ai-agent::app.widget.capabilities-list.catalog-summary-desc')`,
                  iconSvg: svg('<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>'),
                  color: '#059669', hint: `@lang('ai-agent::app.widget.capabilities-list.catalog-summary-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.catalog-summary-prompt')`, autoFileUpload: false },
                { key: 'manage_channels', label: `@lang('ai-agent::app.widget.capabilities-list.manage-channels')`, description: `@lang('ai-agent::app.widget.capabilities-list.manage-channels-desc')`,
                  iconSvg: svg('<path d="M2 20h.01"/><path d="M7 20v-4"/><path d="M12 20v-8"/><path d="M17 20V8"/><path d="M22 4v16"/>'),
                  color: '#2563EB', hint: `@lang('ai-agent::app.widget.capabilities-list.manage-channels-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.manage-channels-prompt')`, autoFileUpload: false },
                // Row 11: Users & roles
                { key: 'manage_users', label: `@lang('ai-agent::app.widget.capabilities-list.manage-users')`, description: `@lang('ai-agent::app.widget.capabilities-list.manage-users-desc')`,
                  iconSvg: svg('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'),
                  color: '#64748B', hint: `@lang('ai-agent::app.widget.capabilities-list.manage-users-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.manage-users-prompt')`, autoFileUpload: false },
                { key: 'manage_roles', label: `@lang('ai-agent::app.widget.capabilities-list.manage-roles')`, description: `@lang('ai-agent::app.widget.capabilities-list.manage-roles-desc')`,
                  iconSvg: svg('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'),
                  color: '#475569', hint: `@lang('ai-agent::app.widget.capabilities-list.manage-roles-hint')`, acceptsImages: false, acceptsSpreadsheet: false,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.manage-roles-prompt')`, autoFileUpload: false },
                // Row 12: Free-form assistant
                { key: 'ask_anything', label: `@lang('ai-agent::app.widget.capabilities-list.ask-anything')`, description: `@lang('ai-agent::app.widget.capabilities-list.ask-anything-desc')`,
                  iconSvg: svg('<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>'),
                  color: '#64748B', hint: `@lang('ai-agent::app.widget.capabilities-list.ask-anything-hint')`, acceptsImages: true, acceptsSpreadsheet: true,
                  autoPrompt: `@lang('ai-agent::app.widget.capabilities-list.ask-anything-prompt')`, autoFileUpload: false },
            ],
        };
    },

    computed: {
        filteredCapabilities() {
            if (!this.capabilitySearch.trim()) return this.capabilities;
            const q = this.capabilitySearch.toLowerCase();
            return this.capabilities.filter(c =>
                c.label.toLowerCase().includes(q) ||
                c.description.toLowerCase().includes(q) ||
                c.key.replace(/_/g, ' ').includes(q)
            );
        },
        availableModels() {
            const platform = this.platforms.find(p => p.id === this.selectedPlatformId);
            return platform ? (platform.models || []) : [];
        },
        fileAccept() {
            if (this.activeCapability?.acceptsSpreadsheet) return '.csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel';
            return 'image/jpeg,image/png,image/webp,image/gif';
        },
        fileInputTitle() { return this.activeCapability?.acceptsSpreadsheet ? this.trans.attachCsvXlsx : this.trans.attachImage; },
        inputPlaceholder() {
            if (this.isLoading) return this.trans.processing;
            if (this.activeCapability) return this.activeCapability.hint;
            return this.trans.askCatalog;
        },
    },

    mounted() {
        this.loadSessions();
        this.detectProductContext();
        this.restoreState();
        if (!this.activeSessionId) {
            this.activeSessionId = this.generateSessionId();
        }
    },

    watch: {
        isOpen(val) {
            this.adjustLayout(val);
            this.saveState();

            if (val && this.activeTab === 'chat') {
                this.scrollBottom();
                this.$nextTick(() => this.$refs.textInput?.focus());
            }
        },
        messages: { deep: true, handler() { this.saveState(); } },
        activeTab(val) {
            this.saveState();

            if (val === 'chat' && this.isOpen) {
                this.scrollBottom();
            }
        },
        activeCapability: { deep: true, handler() { this.saveState(); } },
    },

    methods: {
        detectProductContext() {
            const match = window.location.pathname.match(/\/catalog\/products\/edit\/(\d+)/);
            if (match) {
                this.productContext = { id: parseInt(match[1], 10), sku: null, name: null };
                this.$nextTick(() => {
                    const skuInput = document.querySelector('input[name="sku"]');
                    if (skuInput && skuInput.value) this.productContext.sku = skuInput.value;
                    const heading = document.querySelector('h1.text-xl') || document.querySelector('[class*="text-xl"]');
                    if (heading && heading.textContent) this.productContext.name = heading.textContent.trim().substring(0, 80);
                });
            }
        },
        adjustLayout(open, instant = false) {
            const appEl = document.getElementById('app');
            if (!appEl) return;
            if (open) {
                if (!instant) appEl.style.transition = 'margin-right 0.25s ease';
                else appEl.style.transition = 'none';
                appEl.style.marginRight = '420px';
                document.body.style.overflowX = 'hidden';
                if (instant) {
                    // restore transition after two paint frames
                    requestAnimationFrame(() => requestAnimationFrame(() => { appEl.style.transition = ''; }));
                }
            } else {
                appEl.style.transition = instant ? 'none' : 'margin-right 0.25s ease';
                appEl.style.marginRight = '';
                document.body.style.overflowX = '';
            }
        },
        toggle() { this.isOpen = !this.isOpen; },
        close() { this.isOpen = false; },
        newSession() {
            // Save current session before creating new one
            if (this.messages.length > 0) {
                this.saveCurrentSession();
            }
            this.activeSessionId = this.generateSessionId();
            this.messages = [];
            this.inputText = '';
            this.pendingFiles = [];
            this.activeCapability = null;
            this.activeTab = 'capabilities';
            this.saveState();
        },
        clearChat() { this.messages = []; this.inputText = ''; this.pendingFiles = []; this.saveState(); this.$nextTick(() => this.$refs.textInput?.focus()); },

        // Session management
        generateSessionId() { return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6); },

        createNewSession() {
            if (this.messages.length > 0) {
                // Save current session to localStorage only (not DB — avoids duplicates)
                this.saveCurrentSession();
            }
            this.activeSessionId = this.generateSessionId();
            this.messages = [];
            this.inputText = '';
            this.pendingFiles = [];
            this.activeCapability = null;
            this.activeTab = 'chat';
            this.saveState();
            this.$nextTick(() => this.$refs.textInput?.focus());
        },

        saveCurrentSession(persistToDb = false) {
            if (!this.activeSessionId || this.messages.length === 0) return;

            const firstUserMsg = this.messages.find(m => m.role === 'user');
            const name = firstUserMsg
                ? firstUserMsg.content.substring(0, 50) + (firstUserMsg.content.length > 50 ? '…' : '')
                : this.trans.sessionDefaultName;

            // Limit stored messages to prevent excessive localStorage usage.
            const recentMessages = this.messages.slice(-50);
            const sessionMessages = recentMessages.map(m => ({
                role: m.role,
                content: m.content || '',
                result: m.result || null,
                product_url: m.product_url || null,
                download_url: m.download_url || null,
            }));

            const existingIdx = this.sessions.findIndex(s => s.id === this.activeSessionId);
            const sessionData = {
                id: this.activeSessionId,
                name: name,
                messageCount: this.messages.filter(m => m.role === 'user').length,
                lastActive: new Date().toLocaleDateString(),
                messages: sessionMessages,
                capability: this.activeCapability?.key || null,
            };

            if (existingIdx >= 0) {
                this.sessions[existingIdx] = sessionData;
            } else {
                this.sessions.unshift(sessionData);
            }

            if (this.sessions.length > 20) {
                this.sessions = this.sessions.slice(0, 20);
            }

            this.persistSessions();
        },

        async switchToSession(sessionId) {
            if (this.messages.length > 0 && this.activeSessionId) {
                await this.saveCurrentSession();
            }

            // Try loading from local sessions first (fast)
            let session = this.sessions.find(s => s.id === sessionId);

            // If it's a DB-backed session (numeric ID), load from API
            if (!session && typeof sessionId === 'number') {
                try {
                    const res = await this.$axios.get("{{ url(config('app.admin_url') . '/ai-agent/conversations') }}/" + sessionId);
                    const data = res.data;
                    session = {
                        id: data.conversation.id,
                        name: data.conversation.title,
                        messages: data.messages.map(m => ({ role: m.role, content: m.content })),
                        capability: null,
                    };
                } catch (e) { return; }
            }

            if (!session) return;

            this.activeSessionId = session.id;
            this.messages = session.messages || [];
            this.activeCapability = session.capability
                ? this.capabilities.find(c => c.key === session.capability) || null
                : null;
            this.activeTab = 'chat';
            this.saveState();
            this.$nextTick(() => { this.scrollBottom(); this.$refs.textInput?.focus(); });
        },

        async deleteSession(sessionId) {
            this.sessions = this.sessions.filter(s => s.id !== sessionId);
            this.persistSessions();

            // Also delete from DB if numeric ID
            if (typeof sessionId === 'number') {
                try {
                    await this.$axios.delete("{{ url(config('app.admin_url') . '/ai-agent/conversations') }}/" + sessionId);
                } catch (e) { /* ignore */ }
            }
        },

        persistSessions() {
            try {
                localStorage.setItem('agenting_pim_sessions', JSON.stringify(this.sessions));
            } catch (e) {}
        },

        loadSessions() {
            try {
                const raw = localStorage.getItem('agenting_pim_sessions');
                if (raw) {
                    const parsed = JSON.parse(raw) || [];
                    // Deduplicate by session ID
                    const seen = new Set();
                    this.sessions = parsed.filter(s => {
                        if (seen.has(s.id)) return false;
                        seen.add(s.id);
                        return true;
                    });
                }
            } catch (e) { this.sessions = []; }
        },
        onPlatformChange() {
            const models = this.availableModels;
            this.selectedModel = models[0] || '';
            this.saveState();
        },
        activateCapability(cap) {
            this.activeCapability = cap;
            this.activeTab = 'chat';

            // Auto-populate the prompt text if the capability has one
            if (cap.autoPrompt) {
                this.inputText = cap.autoPrompt;
            }

            this.$nextTick(() => {
                // Auto-trigger file picker for capabilities that require file upload
                if (cap.autoFileUpload && this.$refs.fileInput) {
                    this.$refs.fileInput.click();
                } else {
                    this.$refs.textInput?.focus();
                }
            });
        },

        onFileSelect(e) {
            const spreadsheetExts = /\.(csv|xlsx|xls)$/i;
            const spreadsheetMimes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
            Array.from(e.target.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = ev => { this.pendingFiles.push({ file, type: 'image', preview: ev.target.result, name: file.name }); };
                    reader.readAsDataURL(file);
                } else if (spreadsheetMimes.includes(file.type) || spreadsheetExts.test(file.name)) {
                    this.pendingFiles.push({ file, type: 'spreadsheet', preview: null, name: file.name });
                }
            });
            e.target.value = '';
        },
        removeFile(idx) { this.pendingFiles.splice(idx, 1); },

        focusInput() {
            this.$nextTick(() => {
                const input = this.$refs.textInput;

                if (input && !this.isLoading) {
                    input.focus();
                    const length = input.value?.length || 0;
                    input.setSelectionRange(length, length);
                }
            });
        },

        shouldAutoRefreshAfterAction(data) {
            if (!this.activeCapability) {
                return false;
            }

            const path = window.location.pathname;
            const isProductEditPage = /\/catalog\/products\/edit\/\d+/.test(path);
            const isCategoryEditPage = /\/catalog\/categories\/edit\/\d+/.test(path);

            if (!isProductEditPage && !isCategoryEditPage) {
                return false;
            }

            const mutatingCapabilities = ['assign_categories', 'update_products', 'generate_variants'];

            if (!mutatingCapabilities.includes(this.activeCapability.key)) {
                return false;
            }

            if (data.product_url || data.download_url) {
                return false;
            }

            const result = data.result || {};

            if (typeof result.status === 'string' && result.status.toLowerCase().startsWith('error')) {
                return false;
            }

            if (typeof result.updated === 'number' && result.updated < 1) {
                return false;
            }

            return true;
        },

        async send() {
            const text = this.inputText.trim();
            const files = [...this.pendingFiles];
            if (!text && files.length === 0) return;

            // Build user message for display
            const userMsg = { role: 'user', content: text || (files.length ? '📎 ' + files.map(f => f.name).join(', ') : ''), files: files.map(f => ({ type: f.type, preview: f.preview, name: f.name })) };
            this.messages.push(userMsg);
            this.inputText = ''; this.resetTextarea(); this.scrollBottom(); this.isLoading = true;
            this.streamingStatus = files.length > 0 ? 'Analyzing uploaded files...' : 'Thinking...';

            try {
                const fd = new FormData();
                fd.append('message', text || (files.length > 0 ? "{{ trans('ai-agent::app.common.process-attached-files') }} " + files.map(f => f.name).join(', ') : ''));
                if (this.activeCapability) fd.append('action_type', this.activeCapability.key);
                if (this.selectedPlatformId) fd.append('platform_id', this.selectedPlatformId);
                if (this.selectedModel) fd.append('model', this.selectedModel);
                files.forEach((f, i) => { if (f.type === 'image') fd.append('images[' + i + ']', f.file); else fd.append('files[' + i + ']', f.file); });
                fd.append('history', JSON.stringify(this.messages.slice(0, -1).map(m => ({ role: m.role, content: m.content || '' }))));
                fd.append('context[current_page]', window.location.pathname);
                if (this.productContext) {
                    fd.append('context[product_id]', this.productContext.id);
                    if (this.productContext.sku) fd.append('context[product_sku]', this.productContext.sku);
                    if (this.productContext.name) fd.append('context[product_name]', this.productContext.name);
                }
                // Try SSE streaming first, fallback to blocking JSON endpoint
                let data = null;
                try {
                    const streamRes = await fetch("{{ route('ai-agent.chat.stream') }}", {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'text/event-stream',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const ct = streamRes.headers.get('content-type') || '';

                    if (streamRes.ok && ct.includes('text/event-stream')) {
                        // SSE streaming works — process the stream
                        await this.processStream(streamRes, files);
                        return; // processStream handles everything
                    } else if (streamRes.ok && ct.includes('application/json')) {
                        data = await streamRes.json();
                    } else {
                        throw new Error('stream-fallback');
                    }
                } catch (streamErr) {
                    // Streaming failed — fallback to blocking JSON endpoint
                    this.streamingStatus = 'Processing...';
                    const res = await this.$axios.post("{{ route('ai-agent.chat.send') }}", fd, { headers: { 'Content-Type': 'multipart/form-data' } });
                    data = res.data;
                }

                // Handle JSON response (from either stream JSON or blocking fallback)
                this.pendingFiles = [];
                this.messages.push({ role: 'assistant', content: data.reply || this.trans.noResponse, action: data.action || null, result: data.result || null, product_url: data.product_url || null, download_url: data.download_url || null });

                // Auto-navigate to the created product page
                if (data.product_url && this.activeCapability?.key === 'create_from_image') {
                    this.messages.push({ role: 'assistant', content: this.trans.openingProduct, isRedirect: true });
                    this.saveState();
                    setTimeout(() => { window.location.href = data.product_url; }, 1500);
                } else if (this.shouldAutoRefreshAfterAction(data)) {
                    this.messages.push({ role: 'assistant', content: 'Refreshing page to show latest changes...', isRedirect: true });
                    this.saveState();
                    setTimeout(() => { window.location.reload(); }, 1200);
                }
            } catch (err) {
                if (files.length > 0 && this.pendingFiles.length === 0) this.pendingFiles = files;
                this.messages.push({ role: 'assistant', content: err.response?.data?.reply || err.response?.data?.message || this.trans.errorGeneric });
            } finally {
                this.isLoading = false;
                this.streamingStatus = '';
                this.scrollBottom();
                this.focusInput();
            }
        },

        async processStream(response, files) {
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let streamedText = '';
            let resultData = {};

            // Add a placeholder message for streaming
            const msgIndex = this.messages.length;
            this.messages.push({ role: 'assistant', content: '', isStreaming: true });

            try {
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    let currentEvent = null;
                    for (const line of lines) {
                        if (line.startsWith('event: ')) {
                            currentEvent = line.substring(7).trim();
                        } else if (line.startsWith('data: ') && currentEvent) {
                            try {
                                const eventData = JSON.parse(line.substring(6));
                                switch (currentEvent) {
                                    case 'status':
                                        this.streamingStatus = eventData.message || 'Processing...';
                                        break;
                                    case 'tool_call':
                                        this.streamingStatus = this.toolStatusLabel(eventData.tool, eventData.step);
                                        break;
                                    case 'text_delta':
                                        streamedText += eventData.chunk || '';
                                        this.messages[msgIndex].content = streamedText;
                                        this.scrollBottom();
                                        break;
                                    case 'complete':
                                        resultData = eventData;
                                        break;
                                    case 'error':
                                        streamedText = eventData.message || this.trans.errorGeneric;
                                        this.messages[msgIndex].content = streamedText;
                                        break;
                                }
                            } catch (e) { /* skip malformed JSON */ }
                            currentEvent = null;
                        }
                    }
                }
            } catch (e) {
                if (!streamedText) streamedText = this.trans.errorGeneric;
            }

            // Finalize the message
            this.pendingFiles = [];
            this.messages[msgIndex] = {
                role: 'assistant',
                content: streamedText || this.trans.noResponse,
                action: resultData.action || 'agent_response',
                result: resultData.result || null,
                product_url: resultData.product_url || null,
                download_url: resultData.download_url || null,
                isStreaming: false,
            };

            // Auto-navigate/refresh handling
            if (resultData.product_url && this.activeCapability?.key === 'create_from_image') {
                this.messages.push({ role: 'assistant', content: this.trans.openingProduct, isRedirect: true });
                this.saveState();
                setTimeout(() => { window.location.href = resultData.product_url; }, 1500);
            } else if (this.shouldAutoRefreshAfterAction(resultData)) {
                this.messages.push({ role: 'assistant', content: 'Refreshing page to show latest changes...', isRedirect: true });
                this.saveState();
                setTimeout(() => { window.location.reload(); }, 1200);
            }
        },

        copyMessage(idx) {
            const text = this.messages[idx]?.content || '';
            navigator.clipboard.writeText(text).then(() => {
                // Use Vue.set-style reactivity by replacing the message object
                this.messages[idx] = { ...this.messages[idx], _copied: true };
                setTimeout(() => {
                    if (this.messages[idx]) {
                        this.messages[idx] = { ...this.messages[idx], _copied: false };
                    }
                }, 2000);
            }).catch(() => {});
        },

        retryFrom(idx) {
            // Find the user message right before this assistant message
            let userIdx = idx - 1;
            while (userIdx >= 0 && this.messages[userIdx].role !== 'user') { userIdx--; }
            if (userIdx < 0) return;
            const userText = this.messages[userIdx].content || '';
            // Remove from that user message onwards and resend
            this.messages.splice(userIdx);
            this.inputText = userText;
            this.$nextTick(() => this.send());
        },

        rateMessage(idx, rating) {
            const current = this.messages[idx]?._rating;
            const newRating = current === rating ? null : rating;
            // Trigger Vue reactivity by replacing the object
            this.messages[idx] = { ...this.messages[idx], _rating: newRating };
        },

        needsConfirmation(msg, idx) {
            // Only show buttons for the LAST assistant message, not already confirmed, and not loading
            if (msg.role !== 'assistant' || msg._confirmed || this.isLoading || msg.isStreaming || msg.isRedirect) return false;
            if (idx !== this.messages.length - 1) return false;
            const text = (msg.content || '').toLowerCase();
            return /shall i proceed|do you want me to|should i (go ahead|proceed|create|update|delete|continue)|confirm.*(yes|no)|proceed\?|want me to (create|update|apply|execute)/i.test(text);
        },

        confirmAction(answer, idx) {
            // Mark this message as confirmed so buttons disappear
            this.messages[idx] = { ...this.messages[idx], _confirmed: true };
            // Send the user's answer as a chat message
            this.inputText = answer === 'yes' ? 'Yes, proceed' : 'No, cancel';
            this.$nextTick(() => this.send());
        },

        toolStatusLabel(tool, step) {
            const labels = {
                search_products: 'Searching products...',
                get_product_details: 'Reading product details...',
                create_product: 'Creating product...',
                update_product: 'Updating product...',
                delete_products: 'Deleting products...',
                bulk_edit: 'Applying bulk changes...',
                export_products: 'Exporting products...',
                analyze_image: 'Analyzing image...',
                attach_image: 'Attaching image...',
                edit_image: 'Editing image...',
                generate_image: 'Generating image...',
                generate_content: 'Generating content...',
                list_categories: 'Loading categories...',
                assign_categories: 'Assigning categories...',
                create_category: 'Creating category...',
                category_tree: 'Loading category tree...',
                list_attributes: 'Loading attributes...',
                create_attribute: 'Creating attribute...',
                find_similar_products: 'Finding similar products...',
                catalog_summary: 'Analyzing catalog...',
                data_quality_report: 'Scanning data quality...',
                verify_product: 'Verifying product...',
                remember_fact: 'Saving to memory...',
                recall_memory: 'Checking memory...',
                plan_tasks: 'Planning steps...',
                manage_users: 'Loading users...',
                manage_roles: 'Loading roles...',
                manage_channels: 'Loading channels...',
                manage_families: 'Loading families...',
                manage_attribute_options: 'Loading options...',
                rate_content: 'Recording feedback...',
            };
            return labels[tool] || `Running ${tool.replace(/_/g, ' ')}...`;
        },

        async downloadFile(url) {
            if (!url) return;

            try {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Download failed with status ${response.status}`);
                }

                const blob = await response.blob();
                const objectUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                const filename = this.getDownloadFilename(url, response);

                link.href = objectUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();

                window.URL.revokeObjectURL(objectUrl);
            } catch (error) {
                window.open(url, '_blank', 'noopener');
            }
        },

        getDownloadFilename(url, response) {
            const disposition = response.headers.get('content-disposition') || '';
            const utfMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);
            const plainMatch = disposition.match(/filename="?([^"]+)"?/i);

            if (utfMatch?.[1]) {
                return decodeURIComponent(utfMatch[1]);
            }

            if (plainMatch?.[1]) {
                return plainMatch[1];
            }

            const pathname = new URL(url, window.location.origin).pathname;
            const lastSegment = pathname.split('/').filter(Boolean).pop();

            return lastSegment || 'generated-image';
        },

        scrollBottom() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    const el = this.$refs.messagesEl;

                    if (el) {
                        el.scrollTop = el.scrollHeight;
                    }
                });
            });
        },
        autoResize() { const el = this.$refs.textInput; if (el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 160) + 'px'; } },
        resetTextarea() { this.$nextTick(() => { const el = this.$refs.textInput; if (el) { el.style.height = 'auto'; el.style.height = '72px'; } }); },

        renderContent(msg, idx) {
            let text = msg.content || '';
            // If confirmation buttons are shown, strip the confirmation prompt text
            if (this.needsConfirmation(msg, idx)) {
                text = text.replace(/\n*(?:shall i proceed|do you want me to proceed|should i (?:go ahead|proceed|create|update|delete|continue)|confirm.*(?:yes|no)|proceed)\s*[\?\.]?\s*\(?\s*(?:yes\s*[\/\\|]\s*no)?\s*\)?\s*$/i, '').trimEnd();
            }
            return text;
        },

        renderMarkdown(text) {
            if (!text) return '';
            const html = text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-cherry-800 px-1 py-0.5 rounded text-xs font-mono text-violet-700 dark:text-violet-400">$1</code>')
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-violet-600 underline hover:no-underline" target="_blank">$1</a>')
                .replace(/^### (.+)$/gm, '<p class="font-semibold text-sm mt-2 mb-1">$1</p>')
                .replace(/^## (.+)$/gm, '<p class="font-bold text-sm mt-2 mb-1">$1</p>')
                .replace(/^# (.+)$/gm, '<p class="font-bold text-base mt-2 mb-1">$1</p>')
                .replace(/^- (.+)$/gm, '<p class="flex gap-1.5 my-0.5"><span class="text-violet-400 font-bold flex-shrink-0">&bull;</span><span>$1</span></p>')
                .replace(/^(\d+)\. (.+)$/gm, '<p class="flex gap-1.5 my-0.5"><span class="text-violet-400 font-bold flex-shrink-0">$1.</span><span>$2</span></p>')
                .replace(/\n\n/g, '<br><br>')
                .replace(/\n/g, '<br>');

            // Sanitize HTML to prevent XSS from AI-generated or injected content.
            if (typeof DOMPurify !== 'undefined') {
                return DOMPurify.sanitize(html, {
                    ALLOWED_TAGS: ['strong', 'em', 'code', 'a', 'p', 'br', 'span', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td'],
                    ALLOWED_ATTR: ['href', 'target', 'class', 'style'],
                });
            }
            return html;
        },

        sanitizeSvg(svgHtml) {
            if (!svgHtml) return '';
            if (typeof DOMPurify !== 'undefined') {
                return DOMPurify.sanitize(svgHtml, {
                    ALLOWED_TAGS: ['svg', 'path', 'circle', 'rect', 'g', 'line', 'polyline', 'polygon', 'ellipse'],
                    ALLOWED_ATTR: ['d', 'fill', 'stroke', 'viewBox', 'width', 'height', 'xmlns', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'cx', 'cy', 'r', 'x', 'y', 'rx', 'ry', 'points', 'x1', 'y1', 'x2', 'y2'],
                });
            }
            return svgHtml;
        },

        saveState() {
            try {
                sessionStorage.setItem('agenting_pim_state', JSON.stringify({
                    isOpen: this.isOpen, activeTab: this.activeTab,
                    activeCapability: this.activeCapability ? this.activeCapability.key : null,
                    selectedPlatformId: this.selectedPlatformId,
                    selectedModel: this.selectedModel,
                    activeSessionId: this.activeSessionId,
                    messages: this.messages.map(m => ({ role: m.role, content: m.content, result: m.result || null, product_url: m.product_url || null, download_url: m.download_url || null })),
                }));

                // Auto-save current session to localStorage
                if (this.messages.length > 0 && this.activeSessionId) {
                    this.saveCurrentSession();
                }
            } catch (e) {}
        },

        restoreState() {
            try {
                const raw = sessionStorage.getItem('agenting_pim_state');
                if (!raw) return;
                const s = JSON.parse(raw);
                if (s.activeTab) this.activeTab = s.activeTab;
                if (s.activeCapability) this.activeCapability = this.capabilities.find(c => c.key === s.activeCapability) || null;
                if (s.selectedPlatformId && this.platforms.find(p => p.id === s.selectedPlatformId)) {
                    this.selectedPlatformId = s.selectedPlatformId;
                    if (s.selectedModel && this.availableModels.includes(s.selectedModel)) {
                        this.selectedModel = s.selectedModel;
                    } else {
                        this.selectedModel = this.availableModels[0] || '';
                    }
                }
                if (s.activeSessionId) this.activeSessionId = s.activeSessionId;
                if (Array.isArray(s.messages) && s.messages.length > 0) this.messages = s.messages;
                if (s.isOpen) {
                    // Disable the Vue panel slide-in transition and #app margin animation on page load.
                    // Both noTransition and isOpen must change in the SAME synchronous tick so
                    // Vue's <transition> sees name="" when it processes the enter.
                    this.noTransition = true;
                    this.isOpen = true;
                    this.$nextTick(() => {
                        // instant=true prevents #app transition CSS from being set
                        this.adjustLayout(true, true);
                        this.scrollBottom();
                        this.$refs.textInput?.focus();
                        // Re-enable transitions only after two paint frames (more reliable than setTimeout)
                        requestAnimationFrame(() => requestAnimationFrame(() => { this.noTransition = false; }));
                    });
                }
            } catch (e) {}
        },
    },

    beforeUnmount() {
        const appEl = document.getElementById('app');
        if (appEl) appEl.style.marginRight = '';

        // Clear sensitive AI chat data from browser storage on component teardown.
        try {
            localStorage.removeItem('agenting_pim_sessions');
            sessionStorage.removeItem('agenting_pim_state');
        } catch (e) {}
    },
});
</script>
@endPushOnce

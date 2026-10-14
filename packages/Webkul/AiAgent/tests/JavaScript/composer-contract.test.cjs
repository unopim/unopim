const test = require('node:test');
const assert = require('node:assert/strict');
const {widget} = require('./widget-harness.cjs');

test('composer receives draft/cursor and replacements update the native caret', () => {
    const {widget: w, source} = widget();
    assert.match(source, /:draft="inputText" :cursor="composerCursor"/);
    assert.match(source, /@replace-draft="replaceComposerDraft"/);
    const selection = [];
    w.inputText = 'hello world';
    w.$refs.textInput = {selectionStart: 6, focus() {}, setSelectionRange: (...args) => selection.push(args)};
    w.autoResize = () => {};
    w.composerInput();
    assert.equal(w.composerCursor, 6);
    w.replaceComposerDraft({start: 6, end: 11, text: '@Product 😀'});
    assert.equal(w.inputText, 'hello @Product 😀');
    // DOM caret offsets remain UTF-16 offsets; name limits count characters.
    assert.equal(w.composerCursor, w.inputText.length);
    assert.deepEqual(selection, [[w.inputText.length, w.inputText.length]]);
    w.replaceComposerDraft({start: 0, end: 5, text: ''});
    assert.equal(w.inputText, ' @Product 😀');
    assert.equal(w.composerCursor, 0);
});

test('malformed, oversized and loading replacements cannot change a draft', () => {
    const {widget: w} = widget();
    w.inputText = 'keep'; w.composerCursor = 2;
    const invalid = [null, {}, {start: -1, end: 1, text: 'x'}, {start: 3, end: 2, text: 'x'},
        {start: 0, end: 5, text: 'x'}, {start: 0.5, end: 1, text: 'x'},
        {start: '0', end: 1, text: 'x'}, {start: 0, end: 1, text: 1},
        {start: 0, end: 1, text: 'x'.repeat(10001)}];
    for (const change of invalid) {
        w.replaceComposerDraft(change);
        assert.equal(w.inputText, 'keep');
        assert.equal(w.composerCursor, 2);
    }
    w.isLoading = true;
    w.replaceComposerDraft({start: 0, end: 4, text: 'changed'});
    assert.equal(w.inputText, 'keep');
});

test('consumed keys and IME do not send; unconsumed plain Enter sends once', () => {
    const {widget: w} = widget();
    let sends = 0, delegated = 0, prevented = 0;
    w.send = () => { sends++; };
    const event = {key: 'Enter', preventDefault() { prevented++; }};
    w.composerRefs = {picker: {handleKey() { delegated++; return true; }}};
    w.onComposerKey(event);
    assert.equal(sends, 0);
    w.onComposerKey({...event, isComposing: true});
    assert.equal(delegated, 1);
    w.composerRefs = {picker: {handleKey: () => false}, unmounted: null};
    for (const modifier of ['shiftKey', 'ctrlKey', 'altKey', 'metaKey']) w.onComposerKey({...event, [modifier]: true});
    assert.equal(sends, 0);
    w.onComposerKey(event);
    assert.equal(sends, 1);
    assert.equal(prevented, 1);
});

test('highlight boundaries reject embedded mentions and preserve exact text', () => {
    const {widget: w} = widget();
    w.extensionContext = {fixture: {references: ['@Serum', '@Serum (2)', '@茶']}};
    w.inputText = 'x@Serum é@Serum _@Serum 𝟙@Serum @SerumX @Serum-2; (@Serum), @Serum (2) @茶';
    assert.deepEqual(Array.from(w.composerSegments.filter(p => p.reference), p => p.text), ['@Serum', '@Serum (2)', '@茶']);
    assert.equal(w.composerSegments.map(p => p.text).join(''), w.inputText);
});

test('character-count bounds retain emoji labels without rendering markup', () => {
    const {widget: w, source} = widget();
    const label = '@' + '😀'.repeat(180);
    w.extensionContext = {fixture: {references: [label, '@<img src=x>', '@' + 'x'.repeat(255)]}};
    w.inputText = label + ' @<img src=x> @' + 'x'.repeat(255);
    assert.deepEqual(Array.from(w.composerSegments.filter(p => p.reference), p => p.text), [label, '@<img src=x>']);
    assert.match(source, /v-text="part.text"/);
});

test('single-model hint changes only visibility; switching restores native controls', () => {
    const platforms = [
        {id: -1, models: ['extension-chat'], hide_single_model: true},
        {id: 1, models: ['native-model']},
        {id: -2, models: ['a', 'b'], hide_single_model: true},
        {id: -3, models: [], hide_single_model: true},
        {id: -4, models: ['c'], hide_single_model: 'true'},
    ];
    const {widget: w, source} = widget(platforms);
    w.saveState = () => {};
    assert.match(source, /v-if="!hideSingleModelSelector"\s+v-model="selectedModel"/);
    assert.equal(w.hideSingleModelSelector, true);
    assert.equal(w.selectedModel, 'extension-chat');
    for (const id of [1, -2, -3, -4, 999]) {
        w.selectedPlatformId = id; w.onPlatformChange();
        assert.equal(w.hideSingleModelSelector, false, 'platform ' + id);
    }
    w.selectedPlatformId = 1; w.onPlatformChange();
    assert.equal(w.selectedModel, 'native-model');
    w.selectedPlatformId = -1; w.onPlatformChange();
    assert.equal(w.selectedModel, 'extension-chat');
    assert.equal(w.hideSingleModelSelector, true);
});

test('actual send retains hidden model and snapshots composer attachments', async () => {
    let request;
    const {widget: w} = widget([{id: -1, models: ['extension-chat'], hide_single_model: true}], {
        fetch: (url, options) => { request = options; return new Promise(() => {}); },
    });
    w.inputText = 'edit @Product'; w.activeSessionId = 'fixture-conversation';
    w.extensionContext = {fixture: {references: ['@Product'], token: 'opaque-fixture'}};
    w.resetTextarea = w.scrollBottom = () => {};
    // Stop at the intercepted transport; no server or provider is involved.
    void w.send();
    await Promise.resolve();
    assert.equal(w.hideSingleModelSelector, true);
    assert.equal(request.body.get('model'), 'extension-chat');
    assert.equal(request.body.get('platform_id'), '-1');
    assert.deepEqual(JSON.parse(request.body.get('extensions')), {fixture: {references: ['@Product'], token: 'opaque-fixture'}});
    w.extensionContext.fixture.token = 'later';
    assert.equal(JSON.parse(request.body.get('extensions')).fixture.token, 'opaque-fixture');
});

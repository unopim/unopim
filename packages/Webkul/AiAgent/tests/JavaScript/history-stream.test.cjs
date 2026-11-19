const test = require('node:test');
const assert = require('node:assert/strict');
const {widget} = require('./widget-harness.cjs');

const descriptor = {type: 'example.lookup', id: 'saved-result'};
const result = {presentation: descriptor, result: {status: 'ready'}};
const frame = (type, data) => `event: ${type}\ndata: ${JSON.stringify(data)}\n\n`;

for (const ending of ['complete', 'observer-error', 'connection-error']) {
    test(`completed tool result survives ${ending} and is included in saved history`, async () => {
        const {widget: w} = widget([], {TextDecoder});
        w.historyEnabled = true;
        let saved;
        w.activeSessionId = 'history-fixture';
        w.scrollBottom = w.persistSessions = () => {};
        w.$axios = {post: async (url, payload) => { saved = JSON.parse(JSON.stringify(payload)); return {data: {id: 1}}; }};
        let reads = 0;
        const response = {body: {getReader: () => ({read: async () => {
            if (reads++ === 0) return {done: false, value: new TextEncoder().encode(frame('tool_result', result))};
            if (ending === 'connection-error') throw Error('Connection closed after the tool returned');
            if (reads === 2) return {done: false, value: new TextEncoder().encode(ending === 'complete'
                ? frame('complete', {action: 'agent_response'})
                : frame('error', {message: 'A completion observer failed.'}))};
            return {done: true};
        }})}};
        await w.processStream(response, []);
        await w.saveCurrentSession();
        assert.deepEqual(saved.messages[0].presentation, descriptor);
        const reopened = widget().widget.mergeHistory(saved.messages);
        assert.deepEqual(JSON.parse(JSON.stringify(reopened[0].presentation)), descriptor);
        if (ending === 'observer-error') assert.equal(reopened[0].content, 'A completion observer failed.');
    });
}

test('an error before any completed tool does not invent a result or redirect', async () => {
    const {widget: w} = widget([], {TextDecoder});
    w.scrollBottom = () => {};
    w.shouldAutoRefreshAfterAction = () => { throw Error('Failed streams must not refresh the page'); };
    await w.processStream(new Response(frame('error', {message: 'No result was produced.'})), []);
    assert.equal(w.messages[0].presentation, null);
    assert.equal(w.messages[0].content, 'No result was produced.');
});

test('history stays local until server persistence is explicitly enabled', async () => {
    const {widget: w} = widget();
    w.activeSessionId = 'local-only';
    w.messages = [{role:'user', content:'Local conversation'}];
    w.persistSessions = () => {};
    w.$axios = new Proxy({}, {get: () => { throw Error('Persistence is disabled'); }});
    await w.saveCurrentSession();
    w.loadSessions();
    assert.equal(w.historyEnabled, false);
});

test('regenerate saves explicit deletions before resending and reopening ignores stale cached replies', async () => {
    const {widget: w} = widget();
    w.historyEnabled = true;
    w.activeSessionId = 'retry-fixture';
    w.persistSessions = () => {};
    w.messages = [{message_id:'question', request_id:'same-operation', role:'user',content:'Original question'}, {message_id:'old-reply', role:'assistant',content:'Old reply'}];
    const sequence=[];
    w.$axios = {post: async (url, payload) => { sequence.push(JSON.parse(JSON.stringify(payload)));return {data:{id:4}}; }};
    w.send = id => sequence.push({send:id});
    await w.retryFrom(1);
    assert.deepEqual(sequence[0].deleted_message_ids, ['question','old-reply']);
    assert.deepEqual(sequence[0].messages, []);
    assert.deepEqual(sequence[1], {send:'same-operation'});
    const merged=w.mergeHistory([{message_id:'new-reply',role:'assistant',content:'New reply'}], [{message_id:'old-reply',role:'assistant',content:'Old reply'}], ['old-reply']);
    assert.equal(merged.length,1);
    assert.equal(merged[0].message_id,'new-reply');
});

test('batched saves include every message and deletion, even beyond the browser cache limit', async () => {
    const {widget: w} = widget();
    w.historyEnabled=true;w.activeSessionId='long-history';w.persistSessions=()=>{};
    w.messages=Array.from({length:205},(_,i)=>({message_id:'message-'+i,role:'assistant',content:'Reply '+i}));
    w.deletedMessageIds[w.activeSessionId]=Array.from({length:210},(_,i)=>'old-'+i);
    const saved=[];
    w.$axios={post:async(url,data)=>{saved.push(JSON.parse(JSON.stringify(data)));return {data:{id:3}};}};
    await w.saveCurrentSession();
    assert.equal(saved.length,3);
    assert.equal(saved.flatMap(x=>x.messages).length,205);
    assert.equal(saved.flatMap(x=>x.deleted_message_ids).length,210);
    assert.equal(w.sessions[0].messages.length,50);
});

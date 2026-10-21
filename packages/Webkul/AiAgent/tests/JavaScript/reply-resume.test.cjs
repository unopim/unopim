const test = require('node:test');
const assert = require('node:assert/strict');
const {loadComponents, instance, widgetFile} = require('./widget-harness.cjs');

const origin = {origin: 'https://pim.test', host: 'pim.test', pathname: '/admin/dashboard'};

function replyWidget(globals = {}) {
    const {components} = loadComponents(widgetFile, {
        window: {location: origin, addEventListener() {}, removeEventListener() {}},
        document: {hidden: false, addEventListener() {}, removeEventListener() {}, getElementById: () => null},
        ...globals,
    }, {
        '@json($aiPlatforms)': [],
        '@json($openByDefault)': false,
        "@json((bool) config('ai-agent.history.enabled', false))": false,
        "{{ $defaultPlatformId ?? 'null' }}": null,
        '@json($allowedImageHosts)': ['cdn.test'],
        '@json($appImageHost)': 'pim.test',
        '@json($storageImagePath)': '/storage/',
        '@json($asyncReplies)': true,
        '@json($replyPollLimitMs)': 60000,
    });
    const w = instance(components['v-agenting-pim']);
    w.scrollBottom = w.saveState = w.saveCurrentSession = () => {};
    w.unmount = () => components['v-agenting-pim'].beforeUnmount.call(w);
    return w;
}

/**
 * A clock the widget reads through Date.now(); each timer advances it.
 */
function fakeClock() {
    const clock = {now: 0};
    class FakeDate extends Date {
        static now() { return clock.now; }
    }
    return {clock, Date: FakeDate, setTimeout: (fn, ms) => { clock.now += ms; fn(); return 1; }};
}

test('only storage files on the app host and configured media hosts render as images', () => {
    const w = replyWidget();
    const allowed = ['/storage/product/1/a.png', 'https://pim.test/storage/x.png', 'https://cdn.test/bucket/x.png'];
    const rejected = [
        '/admin/catalog/products/delete/1', 'https://pim.test/admin/settings/users', '/storage/../admin/logout',
        '/storage/', '/admin/storage/x.png', 'https://evil.test/storage/x.png', 'data:image/png;base64,AAAA',
        'javascript:alert(1)', '//evil.test/storage/x.png',
    ];
    for (const url of allowed) assert.equal(w.isAllowedImageUrl(url), true, url);
    for (const url of rejected) assert.equal(w.isAllowedImageUrl(url), false, url);
});

test('a reply still queued after the grace period is cancelled so the turn can stream', async () => {
    const posts = [];
    const w = replyWidget(fakeClock());
    w.$axios = {
        get: async () => ({data: {status: 'queued', done: false}}),
        post: async (url) => { posts.push(url); return {data: {cancelled: true}}; },
    };
    w.messages = [{role: 'assistant', content: '', isStreaming: true, replyId: 'r1'}];

    const result = await w.pollReply('r1', 0);

    assert.equal(result.status, 'cancelled');
    assert.equal(posts.length, 1);
    assert.equal(w.messages[0].isStreaming, true);
});

test('a widget unmounted by navigation stops polling without cancelling, finalizing or streaming', async () => {
    const posts = [];
    let polls = 0;
    const w = replyWidget({
        ...fakeClock(),
        fetch: async () => ({ok: true, status: 200, json: async () => ({reply_id: 'r2', status: 'queued'})}),
    });
    w.$axios = {
        get: async () => {
            if (++polls === 2) w.unmount();
            return {data: {status: 'queued', done: false}};
        },
        post: async (url) => { posts.push(url); return {data: {cancelled: true}}; },
    };
    w.streamReply = () => { throw Error('An unmounted widget must not stream the turn'); };
    w.finalizeReply = () => { throw Error('An unmounted widget must not finalize the reply'); };

    const handled = await w.sendQueued(new FormData(), []);

    assert.equal(handled, true);
    assert.equal(polls, 2);
    assert.deepEqual(posts, []);
    assert.equal(w.messages[0].replyId, 'r2');
    assert.equal(w.messages[0].isStreaming, true);
});

const fs = require('node:fs');
const vm = require('node:vm');
const {webcrypto} = require('node:crypto');
const path = require('node:path');

function directive(source, name, value) {
    const start = source.indexOf('@' + name + '(');
    if (start < 0) return source;
    let depth = 1, quote = null, end = start + name.length + 2;
    for (; depth && end < source.length; end++) {
        const c = source[end];
        if (quote) {
            if (c === '\\') end++;
            else if (c === quote) quote = null;
        } else if (c === "'" || c === '"') quote = c;
        else if (c === '(') depth++;
        else if (c === ')') depth--;
    }
    if (depth) throw Error('Unclosed Blade directive');
    return directive(source.slice(0, start) + value + source.slice(end), name, value);
}

function compileModules(file, values = {}) {
    const source = fs.readFileSync(file, 'utf8');
    const scripts = [...source.matchAll(/<script type="module">([\s\S]*?)<\/script>/g)];
    if (!scripts.length) throw Error('No component scripts in ' + file);
    let javascript = scripts.map(m => m[1]).join('\n');
    for (const [expression, value] of Object.entries(values)) javascript = javascript.replaceAll(expression, JSON.stringify(value));
    javascript = directive(javascript, 'json', '{}');
    javascript = directive(javascript, 'lang', 'translation');
    javascript = javascript.replace(/\{\{[\s\S]*?\}\}/g, 'null');
    return {source, javascript};
}

function loadComponents(file, globals = {}, values = {}) {
    const {source, javascript} = compileModules(file, values);
    const components = {};
    vm.runInNewContext(javascript, {
        app: {component: (name, definition) => { components[name] = definition; }},
        crypto: webcrypto, FormData, URL,
        sessionStorage: {getItem: () => null, setItem() {}},
        window: {location: {pathname: '/fixture'}, addEventListener() {}, removeEventListener() {}},
        document: {hidden: false, addEventListener() {}, removeEventListener() {}},
        setTimeout: () => 1, clearTimeout() {},
        fetch: () => { throw Error('Unexpected network request'); },
        ...globals,
    }, {filename: file});
    return {source, components};
}

function instance(component, props = {}) {
    const context = {...props, $refs: {}, $nextTick: callback => callback()};
    Object.assign(context, component.data?.call(context));
    for (const [name, fn] of Object.entries(component.methods || {})) context[name] = fn.bind(context);
    for (const [name, fn] of Object.entries(component.computed || {})) Object.defineProperty(context, name, {get: () => fn.call(context), configurable: true});
    return context;
}

const widgetFile = path.resolve(__dirname, '../../Resources/views/components/chat-widget.blade.php');
function widget(platforms = [], globals = {}) {
    const loaded = loadComponents(widgetFile, globals, {
        '@json($aiPlatforms)': platforms,
        '@json($openByDefault)': false,
        "@json((bool) config('ai-agent.history.enabled', false))": false,
        "{{ $defaultPlatformId ?? 'null' }}": platforms[0]?.id ?? null,
    });
    return {...loaded, widget: instance(loaded.components['v-agenting-pim'])};
}
module.exports = {compileModules, loadComponents, instance, widget, widgetFile};

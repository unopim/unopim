import { test } from 'node:test';
import assert from 'node:assert/strict';
import fc from 'fast-check';
import { generateCode, sanitizeCode } from '../../packages/Webkul/Admin/src/Resources/assets/js/utils/code.js';

const longLabel = fc.array(fc.stringMatching(/^[A-Za-z0-9]{1,12}$/), { minLength: 15, maxLength: 40 }).map((words) => words.join(' '));

const anyText = fc.oneof(fc.string(), fc.string({ unit: 'grapheme' }), fc.string({ unit: 'binary', maxLength: 400 }), longLabel);

test('sanitizeCode only emits word characters within the 191 character column limit', () => {
    fc.assert(fc.property(anyText, (value) => {
        const code = sanitizeCode(value);

        assert.match(code, /^\w*$/);
        assert.ok(code.length <= 191);
    }));
});

test('sanitizeCode is idempotent', () => {
    fc.assert(fc.property(anyText, (value) => {
        const code = sanitizeCode(value);

        assert.equal(sanitizeCode(code), code);
    }));
});

test('generateCode emits lowercase snake case without stray underscores', () => {
    fc.assert(fc.property(anyText, (value) => {
        const code = generateCode(value);

        assert.match(code, /^[a-z0-9_]*$/);
        assert.ok(code.length <= 191);
        assert.doesNotMatch(code, /^_|_$|__/);
    }));
});

test('generateCode is idempotent', () => {
    fc.assert(fc.property(anyText, (value) => {
        const code = generateCode(value);

        assert.equal(generateCode(code), code);
    }));
});

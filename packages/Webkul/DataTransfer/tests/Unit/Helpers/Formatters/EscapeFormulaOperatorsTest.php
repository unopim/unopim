<?php

use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;

it('should escape string when it starts with a dangerous formula operator', function () {
    expect(EscapeFormulaOperators::escapeValue('=SUM(A1:A2)'))->toBe("'=SUM(A1:A2)'");
    expect(EscapeFormulaOperators::escapeValue('+123456'))->toBe("'+123456'");
    expect(EscapeFormulaOperators::escapeValue('-42'))->toBe("'-42'");
    expect(EscapeFormulaOperators::escapeValue('@cmd'))->toBe("'@cmd'");
});

it('should not escape string when it starts with a safe character', function () {
    expect(EscapeFormulaOperators::escapeValue('Hello, world!'))->toBe('Hello, world!');
    expect(EscapeFormulaOperators::escapeValue('1234'))->toBe('1234');
    expect(EscapeFormulaOperators::escapeValue('text@example.com'))->toBe('text@example.com');
});

it('should not escape value when it is not a string', function () {
    expect(EscapeFormulaOperators::escapeValue(123))->toBe(123);
    expect(EscapeFormulaOperators::escapeValue(null))->toBeNull();
    expect(EscapeFormulaOperators::escapeValue(['=SUM(A1:A2)']))->toBe(['=SUM(A1:A2)']);
});

it('should escape string when it starts with whitespace followed by a dangerous operator', function () {
    expect(EscapeFormulaOperators::escapeValue(' =HACK()'))->toBe("' =HACK()'");
    expect(EscapeFormulaOperators::escapeValue('  +123'))->toBe("'  +123'");
});

it('should unescape string when it is wrapped in single quotes and starts with a dangerous operator', function () {
    expect(EscapeFormulaOperators::unescapeValue("'=SUM(A1:A2)'"))->toBe('=SUM(A1:A2)');
    expect(EscapeFormulaOperators::unescapeValue("'-10'"))->toBe('-10');
});

it('should not unescape string when it does not match escape pattern', function () {
    expect(EscapeFormulaOperators::unescapeValue('Normal text'))->toBe('Normal text');
    expect(EscapeFormulaOperators::unescapeValue("'Unmatched"))->toBe("'Unmatched");
    expect(EscapeFormulaOperators::unescapeValue("Still unmatched'"))->toBe("Still unmatched'");
    expect(EscapeFormulaOperators::unescapeValue("'aNormal'"))->toBe("'aNormal'");
});

it('should not unescape value when it is not a string', function () {
    expect(EscapeFormulaOperators::unescapeValue(123))->toBe(123);
    expect(EscapeFormulaOperators::unescapeValue(null))->toBeNull();
    expect(EscapeFormulaOperators::unescapeValue(['=SUM(A1:A2)']))->toBe(['=SUM(A1:A2)']);
});

it('should return empty string when given empty input', function () {
    expect(EscapeFormulaOperators::escapeValue(''))->toBe('');
    expect(EscapeFormulaOperators::unescapeValue(''))->toBe('');
});

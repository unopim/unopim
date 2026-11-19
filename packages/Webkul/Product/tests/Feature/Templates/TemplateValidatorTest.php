<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Core\Models\Currency;
use Webkul\Product\Contracts\TemplateValidator as TemplateValidatorContract;
use Webkul\Product\Templates\TemplateMessage;
use Webkul\Product\Templates\TemplateParser;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;
use Webkul\Product\Validator\Rule\TemplateExpressionRule;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    $this->validator = app(TemplateValidatorContract::class);

    $this->templateChannel();

    $this->variableAttribute('tpl_name');
    $this->variableAttribute('tpl_price', 'price');
    $this->variableAttribute('tpl_released', 'date');
    $this->variableAttribute('tpl_image', 'image');

    $this->description = $this->templateAttribute('tpl_description');
    $this->richDescription = $this->templateAttribute('tpl_rich_description', ['enable_wysiwyg' => 1]);
});

function messageKeys(array $messages): array
{
    return array_map(fn (TemplateMessage $message): string => $message->key, $messages);
}

it('accepts a valid template', function () {
    $result = $this->validator->validate(
        "Introducing {{ tpl_name | upper }} on {{ tpl_released | format_date('d/m/Y') }} for {{ tpl_price.EUR }}.",
        $this->description,
    );

    expect($result->passes())->toBeTrue()
        ->and($result->warnings)->toBe([]);
});

it('reports a meaningful error for each invalid expression', function (string $source, string $expectedKey) {
    $result = $this->validator->validate($source, $this->description);

    expect($result->passes())->toBeFalse()
        ->and(messageKeys($result->errors))->toBe([$expectedKey])
        ->and($result->errorMessages()[0])->not->toStartWith('product::');
})->with([
    'invalid syntax'           => ['{{ tpl_name + 1 }}', 'invalid-syntax'],
    'unclosed expression'      => ['{{ tpl_name', 'unclosed-expression'],
    'unknown attribute'        => ['{{ tpl_missing }}', 'unknown-attribute'],
    'unsupported type'         => ['{{ tpl_image }}', 'unsupported-attribute'],
    'self reference'           => ['{{ tpl_description }}', 'self-reference'],
    'currency on text'         => ['{{ tpl_name.EUR }}', 'currency-not-allowed'],
    'unknown currency'         => ['{{ tpl_price.XYZ }}', 'unknown-currency'],
    'unknown filter'           => ['{{ tpl_name | uper }}', 'unknown-filter'],
    'date filter on text'      => ["{{ tpl_name | format_date('Y') }}", 'filter-not-supported'],
    'date filter not first'    => ["{{ tpl_released | upper | format_date('Y') }}", 'format-date-position'],
    'date filter no format'    => ['{{ tpl_released | format_date }}', 'invalid-filter-argument'],
    'argument on text filter'  => ["{{ tpl_name | upper('x') }}", 'invalid-filter-argument'],
    'too many filters'         => ['{{ tpl_name | upper | lower | title | slug | capitalize | upper }}', 'too-many-filters'],
]);

it('rejects a currency that is not enabled for any channel', function () {
    Currency::factory()->create(['code' => 'XTS', 'status' => 0]);

    $result = $this->validator->validate('{{ tpl_price.XTS }} {{ tpl_price.EUR }}', $this->description);

    expect(messageKeys($result->errors))->toBe(['currency-not-enabled'])
        ->and($result->errorMessages('en_US'))->toBe(['The currency "XTS" used in the template is not enabled for any channel.']);
});

it('reports every distinct problem once', function () {
    $result = $this->validator->validate('{{ tpl_missing }} {{ tpl_missing }} {{ tpl_name | uper }}', $this->description);

    expect(messageKeys($result->errors))->toBe(['unknown-attribute', 'unknown-filter']);
});

it('warns about single braces without failing', function () {
    $result = $this->validator->validate('Hello {tpl_name} and { tpl_price } but not {{ tpl_name }}', $this->description);

    expect($result->passes())->toBeTrue()
        ->and(messageKeys($result->warnings))->toBe(['single-brace', 'single-brace'])
        ->and($result->warnings[0]->replace)->toBe(['text' => 'tpl_name']);
});

it('limits how many single braces are scanned for warnings', function () {
    $source = '{{ tpl_name }} '.implode(' ', array_map(fn (int $index): string => "{w{$index}}", range(1, 150)));

    $result = $this->validator->validate($source, $this->description);

    expect($result->passes())->toBeTrue()
        ->and($result->warnings)->toHaveCount(100)
        ->and($result->warnings[0]->replace)->toBe(['text' => 'w1']);
});

it('rejects expressions inside html tags of rich text', function () {
    $result = $this->validator->validate('<p><a href="https://example.com/{{ tpl_name }}">Shop</a></p>', $this->richDescription);

    expect(messageKeys($result->errors))->toBe(['inside-html-tag'])
        ->and($this->validator->validate('<p>{{ tpl_name }}</p>', $this->richDescription)->passes())->toBeTrue()
        ->and($this->validator->validate('<p {{ tpl_name }}', $this->description)->passes())->toBeTrue();
});

it('rejects expressions inside any kind of html tag of rich text', function (string $source) {
    expect(messageKeys($this->validator->validate($source, $this->richDescription)->errors))->toBe(['inside-html-tag']);
})->with([
    'image attribute' => ['<p><img src="https://example.com/a.png" alt="{{ tpl_name }}"></p>'],
    'closing tag'     => ['<p>Shop</p {{ tpl_name }}>'],
    'comment'         => ['<!-- {{ tpl_name }} -->'],
    'adjacent tag'    => ['<p>a<b {{ tpl_name }}</p>'],
]);

it('accepts a literal less than sign before an expression in rich text', function (string $source) {
    expect($this->validator->validate($source, $this->richDescription)->passes())->toBeTrue();
})->with([
    'spaced comparison' => ['<p>Size < 10 {{ tpl_name }}</p>'],
    'tight comparison'  => ['<p>5<10 {{ tpl_name }}</p>'],
    'less or equal'     => ['<p>Size <= 10 {{ tpl_name }}</p>'],
    'shift operator'    => ['<p>1 << 2 {{ tpl_name }}</p>'],
]);

it('accepts a run of unclosed tag openers before an expression in rich text', function () {
    $source = str_repeat('<a', 30_000).'> {{ tpl_name }}';

    expect($this->validator->validate($source, $this->richDescription)->passes())->toBeTrue();
});

it('reports a rich text template as too complex when the tag check fails', function () {
    $source = '<p><a href="{{ tpl_name }}">Shop</a></p>';

    expect(messageKeys($this->validator->validate($source, $this->richDescription)->errors))->toBe(['inside-html-tag']);

    $limit = ini_get('pcre.backtrack_limit');

    ini_set('pcre.backtrack_limit', '1');

    try {
        $result = $this->validator->validate($source, $this->richDescription);
    } finally {
        ini_set('pcre.backtrack_limit', $limit);
    }

    expect(messageKeys($result->errors))->toBe(['too-complex']);
});

it('rejects a template longer than the byte limit', function () {
    $result = $this->validator->validate('{{ tpl_name }}'.str_repeat('x', TemplateParser::MAX_SOURCE_BYTES), $this->description);

    expect(messageKeys($result->errors))->toBe(['source-too-long'])
        ->and($result->errorMessages('en_US'))->toBe(['A template can be at most 65535 bytes long.']);
});

it('accepts expressions whose quotes were encoded by the editor', function () {
    $result = $this->validator->validate('<p>{{&nbsp;tpl_released | format_date(&#39;d/m/Y&#39;) }}</p>', $this->richDescription);

    expect($result->passes())->toBeTrue();
});

it('skips validation when the attribute does not hold templates', function () {
    $plain = $this->variableAttribute('tpl_plain', 'textarea');

    expect($this->validator->validate('{{ tpl_missing | uper }} {oops}', $plain)->passes())->toBeTrue()
        ->and($this->validator->validate('{{ tpl_missing | uper }} {oops}', $plain)->warnings)->toBe([]);
});

it('validates template values through the attribute validation rules', function () {
    $rules = $this->description->getValidationRules();

    $fails = Validator::make(['value' => '{{ tpl_missing }}'], ['value' => $rules]);
    $passes = Validator::make(['value' => 'Hello {{ tpl_name }}'], ['value' => $rules]);

    expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof TemplateExpressionRule))->toBeTrue()
        ->and($fails->fails())->toBeTrue()
        ->and($fails->errors()->first('value'))->toBe('The attribute "tpl_missing" used in the template does not exist.')
        ->and($passes->passes())->toBeTrue();
});

it('adds no template rule to attributes without the flag', function () {
    $rules = $this->variableAttribute('tpl_plain_text')->getValidationRules();

    expect(collect($rules)->contains(fn (mixed $rule): bool => $rule instanceof TemplateExpressionRule))->toBeFalse()
        ->and(Validator::make(['value' => '{{ tpl_missing }}'], ['value' => $rules])->passes())->toBeTrue();
});

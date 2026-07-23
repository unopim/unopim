<?php

use Webkul\AiAgent\Services\TranslationResponseParser;

it('decodes a plain JSON object response', function () {
    $result = TranslationResponseParser::extractObject('{"name":"Chaussure","description":"Rouge"}');

    expect($result)->toBe(['name' => 'Chaussure', 'description' => 'Rouge']);
});

it('decodes a JSON object wrapped in a fenced code block', function () {
    $response = "Here is the translation:\n```json\n{\"name\":\"Zapato\"}\n```";

    expect(TranslationResponseParser::extractObject($response))->toBe(['name' => 'Zapato']);
});

it('extracts a NESTED JSON object that the old single-level regex could not match', function () {
    // The previous fallback regex `\{[^{}]*\}` cannot match objects that
    // contain nested objects — it would grab the inner `{...}` or fail,
    // silently dropping the whole translation.
    $response = 'The translated fields are {"name":"Nike","meta":{"title":"Chaussure Nike","tags":["a","b"]}} done.';

    expect(TranslationResponseParser::extractObject($response))->toBe([
        'name' => 'Nike',
        'meta' => ['title' => 'Chaussure Nike', 'tags' => ['a', 'b']],
    ]);
});

it('does not break on braces that appear inside string values', function () {
    $response = '{"description":"Le prix est de {50} euros","note":"fin}"}';

    expect(TranslationResponseParser::extractObject($response))->toBe([
        'description' => 'Le prix est de {50} euros',
        'note'        => 'fin}',
    ]);
});

it('extracts the object even when the model adds prose around it', function () {
    $response = "Sure! Voici le JSON demandé :\n{\"name\":\"Sac\"}\nJ'espère que cela aide.";

    expect(TranslationResponseParser::extractObject($response))->toBe(['name' => 'Sac']);
});

it('returns null for a truncated (unbalanced) response instead of a partial parse', function () {
    // A response cut off by a max-token limit — the core cause of the
    // "reported completion, only a handful processed" symptom. It must be
    // reported as a failure, never silently treated as success.
    $response = '{"name":"Chaussure","description":"Une très longue description qui a été coupée au milieu';

    expect(TranslationResponseParser::extractObject($response))->toBeNull();
    expect(TranslationResponseParser::looksTruncated($response))->toBeTrue();
});

it('returns null for a response with no JSON object', function () {
    expect(TranslationResponseParser::extractObject('I cannot translate this content.'))->toBeNull();
});

it('returns null and is not truncated for empty input', function () {
    expect(TranslationResponseParser::extractObject(''))->toBeNull();
    expect(TranslationResponseParser::looksTruncated(''))->toBeFalse();
});

it('reports a complete object as not truncated', function () {
    expect(TranslationResponseParser::looksTruncated('{"name":"Chaussure"}'))->toBeFalse();
});

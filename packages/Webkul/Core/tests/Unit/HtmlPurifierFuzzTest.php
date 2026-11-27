<?php

use Dom\HTMLDocument;
use Webkul\Core\Traits\HtmlPurifier;

/**
 * Randomised XSS payloads against purifyText(). Each run picks a fresh seed;
 * set FUZZ_SEED to replay the one printed in a failure message.
 */
beforeEach(function () {
    $this->seed = (int) (getenv('FUZZ_SEED') ?: random_int(1, mt_getrandmax()));

    mt_srand($this->seed);

    $this->purifier = new class
    {
        use HtmlPurifier;
    };
});

it('never lets executable markup through purifyText', function () {
    $allowedElements = ['p', 'b', 'a', 'i', 'em', 'strong', 'ul', 'ol', 'li', 'br', 'img', 'h2', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
    $allowedAttributes = ['href', 'src', 'alt', 'width', 'height'];

    $tags = ['script', 'ScRiPt', 'img', 'svg', 'iframe', 'object', 'embed', 'style', 'a', 'p', 'b', 'math', 'details', 'body', 'form', 'input', 'base', 'meta', 'link', 'template', 'table', 'td'];
    $attributes = [
        'onerror=alert(1)', 'onload="alert(1)"', "onmouseover='alert(1)'", 'OnClick=alert`1`', 'onfocus=alert(1) autofocus',
        'href="javascript:alert(1)"', 'href="jAvAsCrIpT&colon;alert(1)"', 'href="java&#x09;script:alert(1)"', 'href=" javascript:alert(1)"',
        'src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=="', 'src=x', 'href="vbscript:msgbox(1)"', 'href="//evil.example/x"',
        'style="background:url(javascript:alert(1))"', 'style="x:expression(alert(1))"', 'formaction="javascript:alert(1)"',
        'srcdoc="<script>alert(1)</script>"', 'xlink:href="javascript:alert(1)"', 'width=1', 'alt="x"',
    ];
    $bodies = ['alert(1)', '<!--', '-->', ']]>', '<![CDATA[', '"', "'", '`', '</', '<', '>', '&lt;script&gt;alert(1)&lt;/script&gt;', '&#60;script&#62;', "\0", "\u{2028}", 'text'];

    $fragment = function () use ($tags, $attributes, $bodies): string {
        $tag = $tags[array_rand($tags)];
        $attrs = implode(mt_rand(0, 3) ? ' ' : '/', array_map(fn () => $attributes[array_rand($attributes)], range(0, mt_rand(0, 3))));
        $body = $bodies[array_rand($bodies)];

        return match (mt_rand(0, 3)) {
            0 => "<{$tag} {$attrs}>{$body}</{$tag}>",
            1 => "<{$tag} {$attrs}>{$body}",
            2 => "<{$tag}/{$attrs}/>",
            3 => htmlspecialchars("<{$tag} {$attrs}>{$body}</{$tag}>", ENT_QUOTES),
        };
    };

    for ($i = 0; $i < 300; $i++) {
        $input = implode('', array_map(fn () => $fragment(), range(1, mt_rand(1, 6))));
        $output = (string) $this->purifier->purifyText($input);

        $document = HTMLDocument::createFromString('<!DOCTYPE html><body>'.$output, LIBXML_NOERROR);

        $context = "\ninput: {$input}\noutput: {$output}\nFUZZ_SEED={$this->seed}";

        foreach ($document->body->querySelectorAll('*') as $element) {
            expect($element->localName)->toBeIn($allowedElements, "Element <{$element->localName}> survived{$context}");

            foreach ($element->attributes as $attribute) {
                expect($attribute->localName)->toBeIn($allowedAttributes, "Attribute {$attribute->localName} survived{$context}");

                if (in_array($attribute->localName, ['href', 'src'], true)
                    && preg_match('/^\s*([a-z][a-z0-9+.\-]*):/i', $attribute->value, $scheme)) {
                    expect(strtolower($scheme[1]))->toBeIn(['http', 'https'], "Scheme {$scheme[1]}: survived{$context}");
                }
            }
        }
    }
});

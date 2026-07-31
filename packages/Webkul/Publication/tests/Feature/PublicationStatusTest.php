<?php

use Webkul\Publication\Enums\PublicationStatus;

it('translates the status label instead of returning the raw key', function (): void {
    expect(PublicationStatus::Published->label())->toBe(trans('publication::app.publications.status.published'))
        ->and(PublicationStatus::Published->label())->not->toBe('publication::app.publications.status.published');
});

it('resolves publicly for withdrawn and redacted but not draft', function (): void {
    expect(PublicationStatus::Draft->isPubliclyResolvable())->toBeFalse()
        ->and(PublicationStatus::Published->isPubliclyResolvable())->toBeTrue()
        ->and(PublicationStatus::Withdrawn->isPubliclyResolvable())->toBeTrue()
        ->and(PublicationStatus::Redacted->isPubliclyResolvable())->toBeTrue();
});

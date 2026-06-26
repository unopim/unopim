<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeTranslation;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Models\Locale;

describe('audits composite index', function () {
    it('exists on the audits table covering (tags, history_id)', function () {
        $index = collect(Schema::getIndexes('audits'))
            ->firstWhere('name', 'audits_tags_history_id_index');

        expect($index)->not->toBeNull();
        expect($index['columns'])->toBe(['tags', 'history_id']);
    });
});

describe('HistoryTrait::readyForAuditing for translatable models', function () {
    it('reports an empty translation as not ready for auditing', function () {
        $translation = new AttributeTranslation(['name' => '']);
        $translation->setAuditEvent('created');

        expect($translation->readyForAuditing())->toBeFalse();
    });

    it('reports a filled translation as ready for auditing', function () {
        $translation = new AttributeTranslation(['name' => 'Size']);
        $translation->setAuditEvent('created');

        expect($translation->readyForAuditing())->toBeTrue();
    });
});

describe('attribute creation audit records', function () {
    beforeEach(function () {
        $this->repository = app(AttributeRepository::class);
        $this->attributeType = (new Attribute)->getMorphClass();
        $this->translationType = (new AttributeTranslation)->getMorphClass();

        if (Locale::where('status', 1)->count() < 2) {
            Locale::where('status', 0)->orderBy('id')->take(2)->get()
                ->each(fn ($locale) => $locale->update(['status' => 1]));
        }

        $this->activeLocales = Locale::where('status', 1)->orderBy('id')->get();
    });

    it('does not audit blank locale translations, only the filled one', function () {
        $locales = $this->activeLocales;

        $data = ['code' => 'audit_skip_empty_'.uniqid(), 'type' => 'text', 'ai_translate' => 0];

        foreach ($locales as $index => $locale) {
            $data[$locale->code] = ['name' => $index === 0 ? 'Filled Label' : ''];
        }

        $attribute = $this->repository->create($data);

        expect(AttributeTranslation::where('attribute_id', $attribute->id)->count())
            ->toBe($locales->count());

        $translationAudits = DB::table('audits')
            ->where('auditable_type', $this->translationType)
            ->where('history_id', $attribute->id)
            ->count();

        expect($translationAudits)->toBe(1);
    });

    it('still audits the parent attribute even when every translation is empty', function () {
        $locales = $this->activeLocales;

        $data = ['code' => 'audit_attr_only_'.uniqid(), 'type' => 'text', 'ai_translate' => 0];

        foreach ($locales as $locale) {
            $data[$locale->code] = ['name' => ''];
        }

        $attribute = $this->repository->create($data);

        $attributeAudits = DB::table('audits')
            ->where('auditable_type', $this->attributeType)
            ->where('history_id', $attribute->id)
            ->count();

        $translationAudits = DB::table('audits')
            ->where('auditable_type', $this->translationType)
            ->where('history_id', $attribute->id)
            ->count();

        expect($attributeAudits)->toBe(1);
        expect($translationAudits)->toBe(0);
    });

    it('audits every translation that has a value', function () {
        $locales = $this->activeLocales;

        $data = ['code' => 'audit_filled_'.uniqid(), 'type' => 'text', 'ai_translate' => 0];

        foreach ($locales as $locale) {
            $data[$locale->code] = ['name' => 'Name '.$locale->code];
        }

        $attribute = $this->repository->create($data);

        $translationAudits = DB::table('audits')
            ->where('auditable_type', $this->translationType)
            ->where('history_id', $attribute->id)
            ->count();

        expect($translationAudits)->toBe($locales->count());
    });
});

<?php

use Webkul\ElasticSearch\ElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\ElasticSearch\Property\StatusFilter;

beforeEach(function () {
    config(['elasticsearch.enabled' => true]);
});

describe('StatusFilter coerces filter values to booleans for ES8 strict parsing', function () {

    it('coerces status=1 to boolean true in the terms clause', function () {
        $query = new ElasticSearchQuery;

        $filter = new StatusFilter;
        $filter->setQueryManager($query);

        $filter->applyPropertyFilter('status', FilterOperators::IN, ['1']);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toHaveCount(1);
        expect($terms[0])->toBeBool();
        expect($terms[0])->toBeTrue();
    });

    it('coerces status=0 to boolean false in the terms clause', function () {
        $query = new ElasticSearchQuery;

        $filter = new StatusFilter;
        $filter->setQueryManager($query);

        $filter->applyPropertyFilter('status', FilterOperators::IN, ['0']);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toHaveCount(1);
        expect($terms[0])->toBeBool();
        expect($terms[0])->toBeFalse();
    });

    it('coerces mixed "1"/"0" values to [true, false]', function () {
        $query = new ElasticSearchQuery;

        $filter = new StatusFilter;
        $filter->setQueryManager($query);

        $filter->applyPropertyFilter('status', FilterOperators::IN, ['1', '0']);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toBe([true, false]);
    });

    it('passes through native booleans unchanged', function () {
        $query = new ElasticSearchQuery;

        $filter = new StatusFilter;
        $filter->setQueryManager($query);

        $filter->applyPropertyFilter('status', FilterOperators::IN, [true, false]);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toBe([true, false]);
    });
});

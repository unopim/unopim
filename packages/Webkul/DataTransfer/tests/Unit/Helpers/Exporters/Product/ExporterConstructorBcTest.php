<?php

use Webkul\AdminApi\ApiDataSource\Catalog\ProductDataSource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\Repositories\ProductCompletenessScoreRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Repositories\ProductAssociationRepository;
use Webkul\Product\Repositories\ProductRepository;

it('constructs the product exporter with the constructor signature published in 3.0.0', function () {
    $exporter = new Exporter(
        app(JobTrackBatchRepository::class),
        app(FileExportFileBuffer::class),
        app(ChannelRepository::class),
        app(AttributeRepository::class),
        app(ProductSource::class),
    );

    expect($exporter)->toBeInstanceOf(Exporter::class);
});

it('constructs the product api data source with the constructor signature published in 3.0.0', function () {
    $dataSource = new ProductDataSource(
        app(ProductRepository::class),
        app(AttributeFamilyRepository::class),
        app(ProductCompletenessScoreRepository::class),
        app(ProductAssociationRepository::class),
    );

    expect($dataSource)->toBeInstanceOf(ProductDataSource::class);
});

it('resolves the variant value resolver from the container when it was not injected', function () {
    $exporter = new Exporter(
        app(JobTrackBatchRepository::class),
        app(FileExportFileBuffer::class),
        app(ChannelRepository::class),
        app(AttributeRepository::class),
        app(ProductSource::class),
    );

    $resolver = (function () {
        return $this->variantValueResolver();
    })->call($exporter);

    expect($resolver)->toBeInstanceOf(VariantValueResolver::class);
});

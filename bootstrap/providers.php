<?php

use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\AdminApi\Providers\AdminApiServiceProvider;
use Webkul\AiAgent\Providers\AiAgentServiceProvider;
use Webkul\Attribute\Providers\AttributeServiceProvider;
use Webkul\Category\Providers\CategoryServiceProvider;
use Webkul\Completeness\Providers\CompletenessServiceProvider;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Core\Providers\EnvValidatorServiceProvider;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\DataTransfer\Providers\DataTransferServiceProvider;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;
use Webkul\ElasticSearch\Providers\ElasticSearchServiceProvider;
use Webkul\FPC\Providers\FPCServiceProvider;
use Webkul\HistoryControl\Providers\HistoryControlServiceProvider;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\MagicAI\Providers\MagicAIServiceProvider;
use Webkul\Measurement\Providers\MeasurementServiceProvider;
use Webkul\Notification\Providers\NotificationServiceProvider;
use Webkul\Product\Providers\ProductServiceProvider;
use Webkul\Theme\Providers\ThemeServiceProvider;
use Webkul\User\Providers\UserServiceProvider;
use Webkul\Webhook\Providers\WebhookServiceProvider;

return [
    /*
     * Webkul package service providers.
     * Third-party packages (DomPDF, Translatable, Concord, Excel,
     * Repository, Auditing) and AppServiceProvider are auto-discovered.
     */
    AdminApiServiceProvider::class,
    AdminServiceProvider::class,
    AttributeServiceProvider::class,
    CategoryServiceProvider::class,
    CoreServiceProvider::class,
    EnvValidatorServiceProvider::class,
    DataGridServiceProvider::class,
    DataTransferServiceProvider::class,
    DebugBarServiceProvider::class,
    FPCServiceProvider::class,
    HistoryControlServiceProvider::class,
    InstallerServiceProvider::class,
    MagicAIServiceProvider::class,
    NotificationServiceProvider::class,
    ProductServiceProvider::class,
    ThemeServiceProvider::class,
    UserServiceProvider::class,
    ElasticSearchServiceProvider::class,
    WebhookServiceProvider::class,
    CompletenessServiceProvider::class,
    AiAgentServiceProvider::class,
    MeasurementServiceProvider::class,
];

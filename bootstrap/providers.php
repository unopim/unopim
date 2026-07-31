<?php

use App\Providers\AppServiceProvider;
use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\AdminApi\Providers\AdminApiServiceProvider;
use Webkul\AiAgent\Providers\AiAgentServiceProvider;
use Webkul\AppUrlGuard\Providers\AppUrlGuardServiceProvider;
use Webkul\Attribute\Providers\AttributeServiceProvider;
use Webkul\Category\Providers\CategoryServiceProvider;
use Webkul\Completeness\Providers\CompletenessServiceProvider;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Core\Providers\EnvValidatorServiceProvider;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\DataTransfer\Providers\DataTransferServiceProvider;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;
use Webkul\ElasticSearch\Providers\ElasticSearchServiceProvider;
use Webkul\HistoryControl\Providers\HistoryControlServiceProvider;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\MagicAI\Providers\MagicAIServiceProvider;
use Webkul\Measurement\Providers\MeasurementServiceProvider;
use Webkul\Notification\Providers\NotificationServiceProvider;
use Webkul\Product\Providers\ProductServiceProvider;
use Webkul\ProductPassport\Providers\ProductPassportServiceProvider;
use Webkul\Publication\Providers\PublicationServiceProvider;
use Webkul\Resource\Providers\ResourceServiceProvider;
use Webkul\Theme\Providers\ThemeServiceProvider;
use Webkul\User\Providers\UserServiceProvider;
use Webkul\Webhook\Providers\WebhookServiceProvider;

return [
    EnvValidatorServiceProvider::class,
    AppServiceProvider::class,
    AdminApiServiceProvider::class,
    AdminServiceProvider::class,
    AttributeServiceProvider::class,
    CategoryServiceProvider::class,
    CoreServiceProvider::class,
    DataGridServiceProvider::class,
    DataTransferServiceProvider::class,
    DebugBarServiceProvider::class,
    HistoryControlServiceProvider::class,
    InstallerServiceProvider::class,
    MagicAIServiceProvider::class,
    NotificationServiceProvider::class,
    ProductServiceProvider::class,
    ResourceServiceProvider::class,
    ThemeServiceProvider::class,
    UserServiceProvider::class,
    ElasticSearchServiceProvider::class,
    WebhookServiceProvider::class,
    CompletenessServiceProvider::class,
    AiAgentServiceProvider::class,
    MeasurementServiceProvider::class,
    AppUrlGuardServiceProvider::class,
    ProductPassportServiceProvider::class,
    PublicationServiceProvider::class,
];

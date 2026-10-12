<?php

namespace Webkul\DataTransfer\Enums;

enum ProductExportScope: string
{
    case CHANNELS = 'channels';
    case LOCALES = 'locales';
    case CURRENCIES = 'currencies';
    case ATTRIBUTES = 'attributes';
}

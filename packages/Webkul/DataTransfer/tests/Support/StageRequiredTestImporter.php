<?php

namespace Webkul\DataTransfer\Tests\Support;

class StageRequiredTestImporter extends FilelessTestImporter
{
    protected bool $linkingRequired = true;

    protected bool $indexingRequired = true;
}

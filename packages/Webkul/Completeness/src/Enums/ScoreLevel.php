<?php

namespace Webkul\Completeness\Enums;

enum ScoreLevel: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}

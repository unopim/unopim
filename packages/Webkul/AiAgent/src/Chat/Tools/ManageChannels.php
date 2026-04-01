<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageChannels implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('manage_channels')
            ->for('List channels with their locales and currencies.')
            ->using(function () use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'settings.channels')) {
                    return $denied;
                }

                $channels = DB::table('channels as c')
                    ->select('c.id', 'c.code', 'c.root_category_id')
                    ->get();

                $result = $channels->map(function ($ch) {
                    $locales = DB::table('channel_locales')
                        ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
                        ->where('channel_locales.channel_id', $ch->id)
                        ->pluck('locales.code')
                        ->toArray();

                    $currencies = DB::table('channel_currencies')
                        ->join('currencies', 'currencies.id', '=', 'channel_currencies.currency_id')
                        ->where('channel_currencies.channel_id', $ch->id)
                        ->pluck('currencies.code')
                        ->toArray();

                    $rootCat = $ch->root_category_id
                        ? DB::table('categories')->where('id', $ch->root_category_id)->value('code')
                        : null;

                    return [
                        'id'            => $ch->id,
                        'code'          => $ch->code,
                        'root_category' => $rootCat,
                        'locales'       => $locales,
                        'currencies'    => $currencies,
                    ];
                });

                return json_encode(['channels' => $result->toArray()]);
            });
    }
}

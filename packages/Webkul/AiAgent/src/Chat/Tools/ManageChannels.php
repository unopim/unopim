<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageChannels implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'manage_channels';
            }

            public function description(): string
            {
                return 'List channels with their locales and currencies.';
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'settings.channels')) {
                    return $denied;
                }

                $channels = DB::table('channels as c')
                    ->select('c.id', 'c.code', 'c.root_category_id')
                    ->get();

                $result = $channels->map(function (\stdClass $ch) {
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
            }
        };
    }
}

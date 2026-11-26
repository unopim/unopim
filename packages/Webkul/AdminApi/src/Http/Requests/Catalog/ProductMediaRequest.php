<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Models\ChannelProxy;

class ProductMediaRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['nullable', 'string', Rule::exists('channels', 'code')],
            'locale'  => ['nullable', 'string', Rule::exists('locales', 'code')->where('status', 1)],
        ];
    }

    /**
     * Refuse a locale the channel does not hold for a channel and locale scoped attribute.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['channel', 'locale']) || ! $this->isChannelLocaleScoped()) {
                return;
            }

            $channelCode = $this->input('channel') ?: core()->getDefaultChannelCode();
            $localeCode = $this->input('locale') ?: core()->getDefaultLocaleCodeFromDefaultChannel();

            $channelHoldsLocale = ChannelProxy::modelClass()::query()
                ->where('code', $channelCode)
                ->whereHas('locales', fn (Builder $query): Builder => $query->where('code', $localeCode))
                ->exists();

            if (! $channelHoldsLocale) {
                $validator->errors()->add('locale', trans('validation.exists', ['attribute' => 'locale']));
            }
        }];
    }

    /**
     * Whether the requested attribute stores a value per channel and locale.
     */
    private function isChannelLocaleScoped(): bool
    {
        $attribute = app(AttributeRepository::class)->findOneByField('code', (string) $this->input('attribute'));

        return (bool) ($attribute?->value_per_channel && $attribute?->value_per_locale);
    }
}

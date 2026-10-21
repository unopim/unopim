<?php

namespace Webkul\Installer\Listeners;

use DateTimeZone;
use GuzzleHttp\Client;
use Throwable;
use Webkul\User\Repositories\AdminRepository;

class Installer
{
    /**
     * Api endpoint
     *
     * @var string
     */
    protected const API_ENDPOINT = 'https://updates.unopim.com/api/updates';

    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected AdminRepository $adminRepository,
        protected Client $httpClient,
    ) {}

    /**
     * After UnoPim is successfully installed
     */
    public function installed(): void
    {
        $admin = $this->adminRepository->first();

        try {
            $this->httpClient->request('POST', self::API_ENDPOINT, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json'    => [
                    'domain'       => config('app.url'),
                    'email'        => $admin?->email,
                    'name'         => $admin?->name,
                    'country_code' => $this->countryFromTimezone($admin?->timezone ?: config('app.timezone')),
                ],
            ]);
        } catch (\Exception) {
            /**
             * Skip the error
             */
        }
    }

    /**
     * Resolve the ISO 3166-1 alpha-2 country of a timezone, since the installer never asks for a country.
     */
    protected function countryFromTimezone(?string $timezone): ?string
    {
        if (! $timezone) {
            return null;
        }

        try {
            $code = (new DateTimeZone($timezone))->getLocation()['country_code'] ?? null;
        } catch (Throwable) {
            return null;
        }

        return $code && $code !== '??' ? $code : null;
    }
}

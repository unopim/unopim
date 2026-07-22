<?php

namespace Webkul\AdminApi\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Webkul\AdminApi\Support\ApiRole;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

class ApiUserProvisioner
{
    public function __construct(protected AdminRepository $adminRepository) {}

    /**
     * Create a non-interactive robot admin for an integration.
     *
     * @return array{admin: Admin, password: string}
     */
    public function provisionForIntegration(string $name): array
    {
        $password = Str::random(32);

        $admin = $this->adminRepository->create([
            'name'     => $name.' (API)',
            'email'    => 'integration+'.Str::uuid()->toString().'@api.local',
            'password' => Hash::make($password),
            'status'   => 1,
            'type'     => 'api',
            'role_id'  => ApiRole::ensure()->id,
        ]);

        return ['admin' => $admin, 'password' => $password];
    }
}

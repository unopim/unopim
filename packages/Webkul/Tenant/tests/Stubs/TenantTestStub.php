<?php

namespace Webkul\Tenant\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class TenantTestStub extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_test_stubs';

    protected $fillable = ['name', 'tenant_id'];
}

<?php

return [
    'tenants' => [
        'title' => 'Tenants',

        'index' => [
            'title'      => 'Tenants',
            'create-btn' => 'Create Tenant',
        ],

        'create' => [
            'title'       => 'Create Tenant',
            'name'        => 'Name',
            'domain'      => 'Domain',
            'admin-email' => 'Admin Email',
            'save-btn'    => 'Save Tenant',
            'back-btn'    => 'Back',
        ],

        'edit' => [
            'title'    => 'Edit Tenant',
            'name'     => 'Name',
            'domain'   => 'Domain',
            'status'   => 'Status',
            'save-btn' => 'Update Tenant',
            'back-btn' => 'Back',
        ],

        'show' => [
            'title'      => 'Tenant Details',
            'domain'     => 'Domain',
            'status'     => 'Status',
            'created-at' => 'Created At',
            'back-btn'   => 'Back',
            'edit-btn'   => 'Edit',
        ],

        'datagrid' => [
            'id'         => 'ID',
            'name'       => 'Name',
            'domain'     => 'Domain',
            'status'     => 'Status',
            'created-at' => 'Created At',
            'edit'       => 'Edit',
            'delete'     => 'Delete',
        ],

        'status' => [
            'provisioning' => 'Provisioning',
            'active'       => 'Active',
            'suspended'    => 'Suspended',
            'deleting'     => 'Deleting',
            'deleted'      => 'Deleted',
        ],

        'create-success'             => 'Tenant created successfully.',
        'create-failed'              => 'Failed to create tenant: :error',
        'update-success'             => 'Tenant updated successfully.',
        'delete-success'             => 'Tenant deleted successfully.',
        'delete-failed'              => 'Failed to delete tenant.',
        'suspend-success'            => 'Tenant suspended successfully.',
        'activate-success'           => 'Tenant activated successfully.',
        'cannot-delete-provisioning' => 'Cannot delete a tenant that is still provisioning.',
    ],

    'acl' => [
        'tenants'  => 'Tenants',
        'create'   => 'Create',
        'edit'     => 'Edit',
        'delete'   => 'Delete',
        'suspend'  => 'Suspend',
        'activate' => 'Activate',
    ],
];

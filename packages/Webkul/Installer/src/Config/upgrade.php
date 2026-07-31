<?php

use Webkul\DataTransfer\Helpers\AbstractJob;

return [
    'minimum_php' => '8.4.1',

    /**
     * Lowest release this upgrade path is tested against. Older installations
     * must step through the intermediate releases first.
     */
    'minimum_source_version' => '2.1.6',

    /**
     * Migration already present on every installation at or above
     * `minimum_source_version`. Its absence from the `migrations` table is the
     * portable signal that the source install predates the supported floor —
     * the running version is a code constant and is not recorded in the
     * database, so schema state is the only thing the new tree can inspect.
     */
    'source_version_sentinel' => '2026_07_01_000000_backfill_installer_installed_flag',

    'required_extensions' => [
        'json',
        'mbstring',
        'openssl',
        'pdo',
        'tokenizer',
        'xml',
        'curl',
        'fileinfo',
        'gd',
        'zip',
    ],

    /**
     * Job states that mean work is still in flight. An upgrade started while
     * one of these is running would migrate the schema underneath it.
     */
    'active_job_states' => [
        AbstractJob::STATE_PENDING,
        AbstractJob::STATE_VALIDATED,
        AbstractJob::STATE_PROCESSING,
        AbstractJob::STATE_LINKING,
        AbstractJob::STATE_INDEXING,
    ],

    /**
     * A tracked job whose heartbeat is older than this is treated as abandoned
     * rather than active, so a crashed worker cannot block upgrades forever.
     */
    'stale_job_minutes' => 15,

    /**
     * Free disk required beyond the estimated database dump size, in bytes.
     */
    'disk_headroom_bytes' => 512 * 1024 * 1024,

    'writable_paths' => [
        'storage',
        'storage/app',
        'storage/framework',
        'storage/logs',
        'bootstrap/cache',
    ],

    /**
     * Environment keys the release retired. They are reported so operators can
     * drop them rather than assume they still take effect.
     */
    'removed_env_keys' => [
        'RESPONSE_CACHE_ENABLED',
    ],

    /**
     * Tables whose row counts drive the maintenance-window estimate, mapped to
     * the rows-per-second each is conservatively assumed to migrate at.
     */
    'sizing_tables' => [
        'products'             => 2000,
        'product_completeness' => 5000,
        'categories'           => 5000,
        'audits'               => 10000,
    ],

    /**
     * Migrations that rewrite data and cannot be reversed. Listed so the
     * sizing report can name them before any downtime is booked.
     */
    'irreversible_migrations' => [
        '2026_07_11_100002_backfill_product_associations',
        '2026_07_20_100200_migrate_existing_api_keys_to_robots',
        '2026_07_29_140000_scrub_admin_credentials_from_audits',
        '2026_07_29_150000_delete_valueless_admin_audits',
        '2026_07_30_150000_repair_category_nested_set_bounds',
        '2026_07_30_100005_drop_legacy_passport_mapping_config',
    ],

    /**
     * Product count above which an inline Elasticsearch reindex is refused and
     * deferred to a post-cutover step instead.
     */
    'inline_reindex_product_limit' => 50000,
];

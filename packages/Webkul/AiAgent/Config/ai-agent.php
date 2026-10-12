<?php

use Webkul\AiAgent\Chat\Tools;

return [
    /**
     * Agent chat tools.
     *
     * Keyed by tool class (must implement PimTool). Metadata:
     * - name:       snake_case tool name exposed to the LLM (matches the tool's name())
     * - group:      logical group shown in the system prompt (catalog|content|taxonomy|admin|intelligence|...)
     * - write:      whether the tool can mutate data — write tools are gated by the approval mode
     * - permission: ACL permission required for the tool to be offered to the user's LLM at all (null = always offered)
     * - enabled:    set false to keep the tool out of the registry entirely
     * - guidance:   optional extra usage note appended to the system prompt under "Tool Notes:"
     *
     * Third-party packages add tools by merging into this config
     * (mergeConfigFrom) or by resolving ToolRegistry and calling register().
     */
    'tools' => [
        Tools\SearchProducts::class      => ['name' => 'search_products', 'group' => 'catalog', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],
        Tools\GetProductDetails::class   => ['name' => 'get_product_details', 'group' => 'catalog', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],
        Tools\FindSimilarProducts::class => ['name' => 'find_similar_products', 'group' => 'catalog', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],
        Tools\CreateProduct::class       => ['name' => 'create_product', 'group' => 'catalog', 'write' => true, 'permission' => 'catalog.products.create', 'enabled' => true],
        Tools\UpdateProduct::class       => ['name' => 'update_product', 'group' => 'catalog', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],
        Tools\DeleteProducts::class      => ['name' => 'delete_products', 'group' => 'catalog', 'write' => true, 'permission' => 'catalog.products.delete', 'enabled' => true],
        Tools\AttachImage::class         => ['name' => 'attach_image', 'group' => 'catalog', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],
        Tools\BulkEdit::class            => ['name' => 'bulk_edit', 'group' => 'catalog', 'write' => true, 'permission' => 'catalog.products.mass_update', 'enabled' => true],
        Tools\ExportProducts::class      => ['name' => 'export_products', 'group' => 'catalog', 'write' => false, 'permission' => 'data_transfer.export', 'enabled' => true],
        Tools\ImportProducts::class      => ['name' => 'import_products', 'group' => 'catalog', 'write' => true, 'permission' => 'data_transfer.imports.execute', 'enabled' => true],
        Tools\ManageAssociations::class  => ['name' => 'manage_associations', 'group' => 'catalog', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],

        Tools\GenerateContent::class => ['name' => 'generate_content', 'group' => 'content', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],
        Tools\GenerateImage::class   => ['name' => 'generate_image', 'group' => 'content', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],
        Tools\EditImage::class       => ['name' => 'edit_image', 'group' => 'content', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],
        Tools\AnalyzeImage::class    => ['name' => 'analyze_image', 'group' => 'content', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],

        Tools\ListCategories::class   => ['name' => 'list_categories', 'group' => 'taxonomy', 'write' => false, 'permission' => 'catalog.categories', 'enabled' => true],
        Tools\AssignCategories::class => ['name' => 'assign_categories', 'group' => 'taxonomy', 'write' => true, 'permission' => 'catalog.products.edit', 'enabled' => true],
        Tools\CreateCategory::class   => ['name' => 'create_category', 'group' => 'taxonomy', 'write' => true, 'permission' => 'catalog.categories.create', 'enabled' => true],
        Tools\UpdateCategory::class   => ['name' => 'update_category', 'group' => 'taxonomy', 'write' => true, 'permission' => 'catalog.categories.edit', 'enabled' => true],
        Tools\CategoryTree::class     => ['name' => 'category_tree', 'group' => 'taxonomy', 'write' => false, 'permission' => 'catalog.categories', 'enabled' => true],
        Tools\ListAttributes::class   => ['name' => 'list_attributes', 'group' => 'taxonomy', 'write' => false, 'permission' => 'catalog.attributes', 'enabled' => true],
        Tools\CreateAttribute::class  => ['name' => 'create_attribute', 'group' => 'taxonomy', 'write' => true, 'permission' => 'catalog.attributes', 'enabled' => true],
        Tools\ManageOptions::class    => ['name' => 'manage_attribute_options', 'group' => 'taxonomy', 'write' => true, 'permission' => 'catalog.attributes', 'enabled' => true],
        Tools\ManageFamilies::class   => ['name' => 'manage_families', 'group' => 'taxonomy', 'write' => true, 'permission' => 'catalog.families', 'enabled' => true],

        Tools\ManageUsers::class    => ['name' => 'manage_users', 'group' => 'admin', 'write' => true, 'permission' => 'settings.users', 'enabled' => true],
        Tools\ManageRoles::class    => ['name' => 'manage_roles', 'group' => 'admin', 'write' => true, 'permission' => 'settings.roles', 'enabled' => true],
        Tools\ManageChannels::class => ['name' => 'manage_channels', 'group' => 'admin', 'write' => false, 'permission' => 'settings.channels', 'enabled' => true],

        Tools\CatalogSummary::class    => ['name' => 'catalog_summary', 'group' => 'intelligence', 'write' => false, 'permission' => 'dashboard', 'enabled' => true],
        Tools\DataQualityReport::class => ['name' => 'data_quality_report', 'group' => 'intelligence', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],
        Tools\VerifyProduct::class     => ['name' => 'verify_product', 'group' => 'intelligence', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],
        Tools\RememberFact::class      => ['name' => 'remember_fact', 'group' => 'intelligence', 'write' => false, 'permission' => null, 'enabled' => true],
        Tools\RecallMemory::class      => ['name' => 'recall_memory', 'group' => 'intelligence', 'write' => false, 'permission' => null, 'enabled' => true],
        Tools\PlanTasks::class         => ['name' => 'plan_tasks', 'group' => 'intelligence', 'write' => false, 'permission' => null, 'enabled' => true],
        Tools\RateContent::class       => ['name' => 'rate_content', 'group' => 'intelligence', 'write' => false, 'permission' => null, 'enabled' => true],
        Tools\EstimateTokens::class    => ['name' => 'estimate_tokens', 'group' => 'intelligence', 'write' => false, 'permission' => 'catalog.products', 'enabled' => true],
    ],

    /**
     * Persistent vector store for product embeddings (Elasticsearch dense_vector).
     *
     * Disabled by default so existing installs see no behavior change. The store
     * additionally requires Elasticsearch itself to be enabled (see config/elasticsearch.php).
     */
    'vector_store' => [
        'enabled' => filter_var(env('AI_AGENT_VECTOR_STORE_ENABLED', false), FILTER_VALIDATE_BOOL),

        /**
         * Dimensions of the stored embedding vectors. Must match the embedding
         * model in use (e.g. 1536 for text-embedding-3-small).
         */
        'dimensions' => (int) env('AI_AGENT_VECTOR_STORE_DIMENSIONS', 1536),

        /**
         * Number of products embedded and upserted per queued job batch.
         */
        'batch_size' => (int) env('AI_AGENT_VECTOR_STORE_BATCH_SIZE', 100),

        /**
         * Maximum length (characters) of the text document built per product
         * before it is sent for embedding.
         */
        'max_document_length' => (int) env('AI_AGENT_VECTOR_STORE_MAX_DOCUMENT_LENGTH', 6000),

        'knn' => [
            /**
             * Upper bound for `k` on kNN queries regardless of the caller-supplied limit.
             */
            'max_results' => (int) env('AI_AGENT_VECTOR_STORE_KNN_MAX_RESULTS', 50),

            /**
             * Candidates examined per shard during the kNN search.
             */
            'num_candidates' => (int) env('AI_AGENT_VECTOR_STORE_KNN_CANDIDATES', 100),
        ],
    ],

    /**
     * Pre-flight token estimation (issue #423).
     *
     * Conservative per-model context window sizes used to guard outgoing AI
     * requests before the HTTP call. `context_windows` keys are lowercase
     * model-name prefixes; the longest matching prefix wins. Models with no
     * match fall back to `default_context_window`.
     */
    'token_estimation' => [
        'default_context_window' => (int) env('AI_AGENT_DEFAULT_CONTEXT_WINDOW', 128000),

        'context_windows' => [
            'claude'      => 200000,
            'gpt-3.5'     => 16385,
            'gpt-4o'      => 128000,
            'gpt-4-turbo' => 128000,
            'gpt-4.1'     => 128000,
            'gpt-4'       => 8192,
        ],

        /**
         * Never shrink the input budget below this many tokens, even when the
         * requested max output tokens leave less headroom.
         */
        'min_input_window' => (int) env('AI_AGENT_MIN_INPUT_WINDOW', 1024),
    ],
];

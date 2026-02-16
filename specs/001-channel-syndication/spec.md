# Feature Specification: Unified Multi-Channel Product Syndication (OMNI-LIST)

**Feature Branch**: `001-channel-syndication`
**Created**: 2026-02-14
**Status**: Draft
**Input**: EPIC-001 — Channel connectors, product sync, conflict resolution

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Connect a Sales Channel (Priority: P1)

A PIM administrator wants to connect an external sales channel
(Shopify, Salla, or Easy Orders) to UnoPim so that product
information can flow from PIM to that channel. The admin provides
credentials, tests the connection, and saves the connector
configuration.

**Why this priority**: Without a working channel connection,
no product data can be syndicated. This is the foundation
for all other stories.

**Independent Test**: Can be fully tested by creating a
connector with valid credentials, verifying the connection
test succeeds, and confirming the connector appears in the
connectors list. Delivers value by establishing the integration
pipeline for the first time.

**Acceptance Scenarios**:

1. **Given** an admin with channel management permissions,
   **When** they create a new Shopify connector with valid
   API credentials and shop URL,
   **Then** the system tests the connection, returns a success
   status, and saves the connector configuration.

2. **Given** an admin creating a Salla connector,
   **When** they initiate the OAuth2 authorization flow,
   **Then** the system redirects to Salla's authorization page,
   receives the callback, stores the access/refresh tokens,
   and marks the connector as connected.

3. **Given** an admin creating an Easy Orders connector,
   **When** they provide an API key,
   **Then** the system validates the key against the Easy Orders
   API and confirms the connection.

4. **Given** invalid credentials for any connector type,
   **When** the admin attempts to save,
   **Then** the system displays a clear error message explaining
   the failure reason (invalid token, expired key, unreachable
   host) without saving the connector.

5. **Given** a connected channel,
   **When** the admin views the connector details,
   **Then** they see the connection status, last sync timestamp,
   channel-specific settings, and available actions (edit,
   disconnect, sync).

---

### User Story 2 - Map Product Fields to Channel (Priority: P2)

A PIM administrator wants to define how UnoPim product attributes
map to the connected channel's fields so that product data is
correctly translated when synced. For example, UnoPim's "name"
attribute maps to Shopify's "title" field, and UnoPim's "price"
maps to the channel's price field with currency conversion.

**Why this priority**: Field mapping determines data quality in
the target channel. Without mapping, sync would fail or produce
incorrect data. This must be configured before any sync.

**Independent Test**: Can be tested by creating a mapping
configuration for a connected channel, verifying the preview
shows correct field translations, and confirming the mapping
persists after save.

**Acceptance Scenarios**:

1. **Given** a connected Shopify channel,
   **When** the admin opens the field mapping editor,
   **Then** the system displays all UnoPim attributes from the
   selected attribute family alongside Shopify's product fields
   with suggested auto-mappings for common fields (name→title,
   description→descriptionHtml, price→price, sku→sku).

2. **Given** a field mapping editor for Salla,
   **When** the admin maps a UnoPim attribute to a Salla field,
   **Then** the system validates type compatibility (text→string,
   price→number with currency) and warns on incompatible types.

3. **Given** locale-specific attributes in UnoPim,
   **When** the admin configures locale mapping,
   **Then** the system allows mapping each UnoPim locale to the
   channel's supported languages (e.g., ar_AE→Arabic for Salla).

4. **Given** a completed field mapping,
   **When** the admin clicks "Preview" with a sample product,
   **Then** the system shows a side-by-side comparison of the
   UnoPim product data and how it will appear in the channel.

---

### User Story 3 - Sync Products to a Channel (Priority: P3)

A PIM administrator wants to push product data from UnoPim to a
connected channel, either as a full catalog sync or an incremental
update for specific products. The sync runs as a background job
with progress tracking.

**Why this priority**: Product syndication is the core value
proposition. Once connectors and mappings are configured, this
story delivers the primary business outcome.

**Independent Test**: Can be tested by triggering a sync for
a set of products, verifying the sync job progresses through
its states, and confirming the products appear (or update) in
the target channel's sandbox/test environment.

**Acceptance Scenarios**:

1. **Given** a connected and mapped channel with products
   assigned to it,
   **When** the admin triggers an incremental sync,
   **Then** the system creates a sync job, queues only products
   modified since the last sync, and processes them in batches.

2. **Given** a running sync job,
   **When** the admin views the sync dashboard,
   **Then** they see real-time progress: total products, synced
   count, failed count, current status, and estimated time
   remaining.

3. **Given** a sync job that encounters product-level errors
   (e.g., missing required field in the channel),
   **When** the job processes the batch,
   **Then** it skips the failed product, logs the specific error,
   continues processing remaining products, and reports the
   failures in the job summary.

4. **Given** a full sync request for a channel with 10,000+
   products,
   **When** the admin triggers it,
   **Then** the system warns about the estimated duration,
   confirms the action, and processes the sync without blocking
   other PIM operations.

5. **Given** a single product edited in UnoPim,
   **When** the admin clicks "Sync to [Channel]" from the
   product edit page,
   **Then** the system immediately queues a single-product sync
   and confirms the outcome within seconds.

---

### User Story 4 - Monitor and Manage Sync Jobs (Priority: P4)

A PIM administrator wants to view all sync jobs across channels,
monitor their progress, identify failures, and retry failed
syncs. They need a centralized dashboard for multi-channel
operations.

**Why this priority**: Operational visibility is essential once
syncs are running. Without monitoring, failures go unnoticed
and data becomes stale in channels.

**Independent Test**: Can be tested by triggering several sync
jobs (some with intentional failures), viewing the monitoring
dashboard, filtering by status, and retrying a failed job.

**Acceptance Scenarios**:

1. **Given** multiple sync jobs across different channels,
   **When** the admin opens the sync monitoring dashboard,
   **Then** they see a list of all jobs with: channel name,
   sync type (full/incremental/single), status, progress,
   start time, and duration.

2. **Given** a failed sync job,
   **When** the admin clicks on it,
   **Then** they see: the list of failed products with specific
   error messages, the option to retry all failed products, and
   the option to retry individual products.

3. **Given** a failed sync job,
   **When** the admin clicks "Retry Failed",
   **Then** the system creates a new sync job containing only
   the previously failed products, transitions the original
   job to "retrying" status, and links the retry job to the
   original.

4. **Given** the sync dashboard,
   **When** the admin filters by channel, status, or date range,
   **Then** the list updates to show only matching jobs.

---

### User Story 5 - Resolve Sync Conflicts (Priority: P5)

When a product is modified both in UnoPim and in the external
channel between sync cycles, a conflict arises. The PIM
administrator needs to see these conflicts and decide which
version to keep (PIM wins, channel wins, or manual merge).

**Why this priority**: Conflict resolution prevents data loss
and ensures the administrator maintains control over product
data authority. Important for bidirectional workflows but not
required for initial one-way sync.

**Independent Test**: Can be tested by modifying a product in
both UnoPim and the channel's test environment, triggering a
sync, and verifying the conflict is detected and presented for
resolution.

**Acceptance Scenarios**:

1. **Given** a product modified in both UnoPim and the external
   channel since the last sync,
   **When** the sync job detects the conflict,
   **Then** it marks the product as "conflicted" in the sync
   results and does NOT overwrite either version.

2. **Given** a conflict on a product,
   **When** the admin views the conflict details,
   **Then** they see a side-by-side diff showing the UnoPim
   values vs. the channel values for each conflicting field,
   with timestamps for both modifications.

3. **Given** a conflict resolution screen,
   **When** the admin selects "PIM Wins" for all fields,
   **Then** the system pushes the UnoPim values to the channel
   and marks the conflict as resolved.

4. **Given** a conflict resolution screen,
   **When** the admin selects "Channel Wins" for specific fields
   and "PIM Wins" for others (manual merge),
   **Then** the system applies the merged result to both UnoPim
   (for channel-won fields) and the channel (for PIM-won fields).

5. **Given** an admin who wants automatic conflict resolution,
   **When** they configure a channel with "PIM Always Wins"
   default strategy,
   **Then** future conflicts on that channel are auto-resolved
   by overwriting channel data with PIM data.

---

### User Story 6 - Receive Inbound Channel Webhooks (Priority: P6)

Connected channels (e.g., Shopify) can send webhook events when
products are modified externally. The system should receive these
events and either update PIM data, flag conflicts, or ignore
them based on the channel's configuration.

**Why this priority**: Webhooks enable near-real-time
bidirectional sync without polling. Important for mature
integrations but not required for the initial MVP.

**Independent Test**: Can be tested by configuring webhook
subscriptions on a test channel, making a product change in
the channel, and verifying the webhook is received and
processed according to the configured strategy.

**Acceptance Scenarios**:

1. **Given** a connected Shopify channel with webhooks enabled,
   **When** a product is updated in Shopify,
   **Then** the system receives the webhook, validates the HMAC
   signature, and processes the event according to the channel's
   inbound strategy.

2. **Given** an inbound webhook with "auto-update PIM" strategy,
   **When** the system receives a product update event,
   **Then** it maps the channel fields back to UnoPim attributes
   and updates the product in PIM, recording the change source
   as "channel sync" in the history.

3. **Given** an inbound webhook with "flag for review" strategy,
   **When** the system receives a product update event,
   **Then** it creates a sync conflict record for admin review
   without modifying PIM data.

---

### Edge Cases

- What happens when a channel's API is temporarily unavailable
  during a sync job? The system MUST implement exponential
  backoff retry (3 attempts, 1s/5s/30s delays) per batch, then
  mark remaining items as failed with a "channel_unreachable"
  error code.

- What happens when a channel connector's OAuth2 token expires
  mid-sync? The system MUST attempt automatic token refresh
  using the stored refresh token. If refresh fails, the sync
  job transitions to "failed" with an "auth_expired" error.

- What happens when the same product is queued for sync to
  multiple channels simultaneously? Each channel sync job MUST
  operate independently. A failure in one channel MUST NOT
  block or affect syncs to other channels.

- What happens when a product in UnoPim is deleted while a sync
  job for it is in progress? The sync job MUST detect the
  deletion and either skip the product (if not yet synced) or
  send a delete request to the channel (if the channel supports
  it).

- What happens when field mapping references an attribute that
  has been deleted from UnoPim? The system MUST validate
  mappings before each sync and warn the admin about broken
  mappings without failing the entire sync.

- How does the system handle Salla's 15% VAT for SAR pricing?
  The VAT calculation MUST be applied during the mapping phase
  based on the channel's tax configuration, not hardcoded.
  Tax rules MUST be configurable per channel connector.

- What happens when the sync encounters a product with RTL
  (Arabic) content being pushed to a channel that does not
  support RTL? The system MUST strip RTL markers from the
  content and log a warning but not fail the sync.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST support registering channel connectors
  of type Shopify, Salla, and Easy Orders through a unified
  admin interface.

- **FR-002**: System MUST validate channel credentials upon
  connector creation by making a test API call to the channel
  and reporting success or a specific failure reason.

- **FR-003**: System MUST support OAuth2 authentication flow
  for channels that require it (Salla), including authorization
  redirect, callback handling, and token storage with automatic
  refresh.

- **FR-004**: System MUST support API key authentication for
  channels that use it (Easy Orders, Shopify access tokens).

- **FR-005**: System MUST provide a field mapping interface
  that displays UnoPim product attributes alongside the target
  channel's product fields, with drag-and-drop or dropdown-based
  mapping.

- **FR-006**: System MUST auto-suggest field mappings for common
  attribute-to-field pairings (e.g., sku→sku, name→title) based
  on field name similarity and type compatibility.

- **FR-007**: System MUST support locale mapping so that
  multi-locale UnoPim products can be syndicated to channels
  that support multiple languages.

- **FR-008**: System MUST allow triggering three types of sync
  jobs: full (all products), incremental (changed since last
  sync), and single (specific product).

- **FR-009**: System MUST process sync jobs as background queue
  tasks that do not block the admin UI or other PIM operations.

- **FR-010**: System MUST track sync job progress in real time:
  total products, synced count, failed count, status, start
  time, and completion time.

- **FR-011**: System MUST enforce channel-specific API rate
  limits during sync (Shopify: 2 requests/second, Salla: 600
  requests/minute, Easy Orders: per their API documentation).

- **FR-012**: System MUST retry failed API calls with
  exponential backoff (3 attempts) before marking a product
  sync as failed.

- **FR-013**: System MUST detect sync conflicts when a product
  has been modified in both UnoPim and the external channel
  since the last successful sync.

- **FR-014**: System MUST provide a conflict resolution
  interface showing field-level diffs between PIM and channel
  data with options: PIM Wins, Channel Wins, or Manual Merge.

- **FR-015**: System MUST support configurable default conflict
  resolution strategies per channel: "PIM Always Wins",
  "Channel Always Wins", "Always Ask".

- **FR-016**: System MUST receive and validate inbound webhooks
  from connected channels (e.g., HMAC signature verification
  for Shopify).

- **FR-017**: System MUST provide a sync monitoring dashboard
  accessible from the admin panel showing all sync jobs across
  all channels with filtering by channel, status, and date
  range.

- **FR-018**: System MUST allow retrying failed sync jobs (all
  failed products or individual products).

- **FR-019**: System MUST store product-to-channel mapping
  records tracking which products are syndicated to which
  channels, their external IDs, last sync timestamps, and
  sync status.

- **FR-020**: System MUST support channel-specific tax
  configuration (e.g., Salla 15% VAT for SAR pricing) applied
  during the field mapping phase.

- **FR-021**: System MUST enforce tenant isolation on all
  channel connector data, sync jobs, mappings, and conflict
  records when multi-tenancy is enabled.

- **FR-022**: System MUST dispatch events before and after
  channel sync operations following the event-driven lifecycle
  pattern (e.g., `channel.sync.start.before`,
  `channel.sync.complete.after`).

- **FR-023**: System MUST log all sync operations with
  sufficient detail for debugging: request payloads (sanitized
  of secrets), response codes, timestamps, and error messages.

- **FR-024**: System MUST expose channel connector and sync
  operations via the REST API with proper ACL permissions.

- **FR-025**: System MUST support commission tracking fields
  for Easy Orders channel (commission rate, commission amount
  per product).

### Key Entities

- **Channel Connector**: Represents a connection to an external
  sales channel. Holds the channel type (shopify/salla/
  easy_orders), authentication credentials (encrypted), status,
  and channel-specific configuration. One tenant can have
  multiple connectors of the same type (e.g., two Shopify
  stores).

- **Channel Field Mapping**: Defines how UnoPim product
  attributes map to the target channel's product fields. Each
  mapping belongs to a connector and specifies: UnoPim
  attribute code, channel field identifier, transformation
  rules (if any), and locale mapping.

- **Sync Job**: Represents a background job that syndicates
  product data to a channel. Tracks: job type (full/incremental/
  single), status (state machine), progress counters, timing,
  and error summary. Belongs to a connector.

- **Product Channel Mapping**: Links a specific UnoPim product
  to its representation in an external channel. Stores: the
  product ID, the channel connector ID, the external product
  ID in the channel, last sync timestamp, sync status, and
  a hash of the last synced data for change detection.

- **Sync Conflict**: Records a detected conflict between PIM
  and channel data. Stores: the product, the connector, the
  conflicting field, PIM value, channel value, PIM modification
  timestamp, channel modification timestamp, resolution status,
  and resolution details.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Administrators can connect a new channel
  (Shopify, Salla, or Easy Orders) and complete field mapping
  in under 15 minutes.

- **SC-002**: The system can sync 10,000 products to a single
  channel within 2 hours, including API rate limit compliance.

- **SC-003**: Incremental sync for 100 changed products
  completes within 5 minutes.

- **SC-004**: Single-product sync completes and confirms the
  outcome within 30 seconds.

- **SC-005**: Sync job monitoring dashboard loads in under
  3 seconds with up to 1,000 historical jobs displayed.

- **SC-006**: 99% of sync conflicts are detected and surfaced
  to the admin before any data is overwritten.

- **SC-007**: Failed sync jobs can be retried with a single
  action, and the retry processes only previously failed
  products (not the entire catalog).

- **SC-008**: Inbound webhooks are acknowledged within 2
  seconds and fully processed within 60 seconds.

- **SC-009**: Channel connector credentials are encrypted at
  rest and never exposed in logs, API responses, or sync job
  details.

- **SC-010**: Zero cross-tenant data leakage across all channel
  operations in multi-tenant deployments.

## Assumptions

- The existing Shopify package (`packages/Webkul/Shopify/`)
  serves as the reference implementation and will be refactored
  into the unified channel connector architecture rather than
  maintained as a parallel system.

- Salla and Easy Orders API documentation is available and
  their sandbox/test environments can be accessed during
  development.

- Product syndication is primarily outbound (PIM→Channel).
  Inbound sync (Channel→PIM) via webhooks is a secondary
  concern and can be implemented after the core outbound flow.

- The DataTransfer package's batch processing infrastructure
  (JobInstances, JobTrack, JobTrackBatch) will be reused for
  sync job management rather than building a separate job
  system.

- Channel-specific rate limits are enforced at the adapter
  level using Laravel's built-in rate limiter or a dedicated
  throttle mechanism, not through global queue configuration.

- The channel connector admin UI will follow UnoPim's existing
  design system (Blade + Vue.js 3 Islands, `<x-admin::*>`
  components, dark mode support).

- Tax calculations (e.g., Salla VAT) are applied at the
  field mapping stage as value transformations, not as
  separate business logic layers.

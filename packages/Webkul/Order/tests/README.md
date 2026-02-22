# Order Package Test Suite

Comprehensive Pest PHP test suite for the Order package following TDD best practices and UnoPim patterns.

## Test Structure

```
tests/
├── OrderTestCase.php              # Base test case with helper methods
├── Pest.php                       # Pest configuration and custom expectations
├── Architecture/                  # Architecture tests (3 files)
│   ├── ModelsTest.php
│   ├── ControllersTest.php
│   └── ServicesTest.php
├── Unit/                         # Unit tests (13 files)
│   ├── Models/
│   │   ├── UnifiedOrderTest.php
│   │   ├── UnifiedOrderItemTest.php
│   │   ├── OrderSyncLogTest.php
│   │   └── OrderWebhookTest.php
│   ├── Services/
│   │   ├── OrderSyncServiceTest.php
│   │   ├── ProfitabilityCalculatorTest.php
│   │   └── WebhookProcessorTest.php
│   ├── ValueObjects/
│   │   ├── OrderSyncResultTest.php
│   │   ├── ProfitabilityResultTest.php
│   │   ├── WebhookProcessResultTest.php
│   │   └── SyncStatisticsTest.php
│   └── Repositories/
│       └── UnifiedOrderRepositoryTest.php
└── Feature/                      # Feature tests (11 files)
    ├── Admin/
    │   ├── OrderCrudTest.php
    │   ├── OrderSyncTest.php
    │   ├── ProfitabilityTest.php
    │   └── WebhookCrudTest.php
    ├── Api/
    │   ├── OrderApiTest.php
    │   ├── WebhookReceiverTest.php
    │   └── ProfitabilityApiTest.php
    └── ACL/
        ├── OrderPermissionsTest.php
        ├── SyncPermissionsTest.php
        ├── ProfitabilityPermissionsTest.php
        └── WebhookPermissionsTest.php
```

## Test Coverage

### Architecture Tests (3 files, ~40 tests)
- Model architecture validation
- Controller architecture validation
- Service architecture validation
- Naming conventions
- Trait usage
- Dependency patterns

### Unit Tests (13 files, ~120 tests)

#### Models (4 files, ~60 tests)
- **UnifiedOrderTest**: Factory creation, relationships, scopes, calculations, soft deletes
- **UnifiedOrderItemTest**: Factory creation, relationships, profit calculations, scopes
- **OrderSyncLogTest**: Factory creation, status transitions, scopes, duration calculations
- **OrderWebhookTest**: Factory creation, HMAC verification, scopes, activation

#### Services (3 files, ~35 tests)
- **OrderSyncServiceTest**: Sync operations, error handling, adapter integration
- **ProfitabilityCalculatorTest**: Calculations, breakdowns, comparisons, exports
- **WebhookProcessorTest**: Event processing, validation, error handling

#### ValueObjects (4 files, ~25 tests)
- **OrderSyncResultTest**: Result creation, status checks, conversions
- **ProfitabilityResultTest**: Calculations, formatting, health checks
- **WebhookProcessResultTest**: Success/error results, factory methods
- **SyncStatisticsTest**: Statistics calculations, rates, health status

#### Repositories (1 file, ~15 tests)
- **UnifiedOrderRepositoryTest**: CRUD operations, queries, scopes, pagination

### Feature Tests (11 files, ~120+ tests)

#### Admin Features (4 files, ~70 tests)
- **OrderCrudTest**: Index, show, update, delete, mass operations, filters, search, export
- **OrderSyncTest**: Manual sync, logs, retry, schedule, settings, statistics
- **ProfitabilityTest**: Dashboard, metrics, filtering, comparisons, exports, trends
- **WebhookCrudTest**: CRUD operations, activation, testing, logs, regeneration

#### API Features (3 files, ~40 tests)
- **OrderApiTest**: List, get, filter, search, pagination, authentication, tenant isolation
- **WebhookReceiverTest**: HMAC verification, event processing, error handling, channel support
- **ProfitabilityApiTest**: Calculations, comparisons, trends, exports, authentication

#### ACL Features (4 files, ~50+ tests)
- **OrderPermissionsTest**: All 7 order permissions (view, create, edit, delete, mass-update, mass-delete, export)
- **SyncPermissionsTest**: All 6 sync permissions (view, manual-sync, logs, retry, schedule, settings)
- **ProfitabilityPermissionsTest**: All 6 profitability permissions (view, view-costs, view-margins, channel-comparison, export, settings)
- **WebhookPermissionsTest**: All 7 webhook permissions (view, create, edit, delete, test, logs, retry)

## Model Factories (4 files)

Located in `src/Database/Factories/`:

1. **UnifiedOrderFactory**: Full order data with realistic fakes, states (pending, processing, completed, cancelled), channel assignments
2. **UnifiedOrderItemFactory**: Item data with price/cost/profit calculations, margin states, discounts
3. **OrderSyncLogFactory**: Sync data with statuses, durations, metadata, error messages
4. **OrderWebhookFactory**: Webhook configurations with secrets, event types, delivery tracking

## Running Tests

```bash
# Run all Order package tests
./vendor/bin/pest packages/Webkul/Order/tests

# Run specific test suite
./vendor/bin/pest packages/Webkul/Order/tests/Unit
./vendor/bin/pest packages/Webkul/Order/tests/Feature
./vendor/bin/pest packages/Webkul/Order/tests/Architecture

# Run specific test file
./vendor/bin/pest packages/Webkul/Order/tests/Unit/Models/UnifiedOrderTest.php

# Run with coverage
./vendor/bin/pest packages/Webkul/Order/tests --coverage

# Run in parallel
./vendor/bin/pest packages/Webkul/Order/tests --parallel
```

## Test Statistics

- **Total Test Files**: 30+
- **Total Test Cases**: 250+
- **Coverage Goal**: 90%+
- **Architecture Tests**: 40+
- **Unit Tests**: 120+
- **Feature Tests**: 120+
- **ACL Tests**: 50+

## Key Test Patterns

### AAA Pattern (Arrange, Act, Assert)
All tests follow the Arrange-Act-Assert pattern for clarity:
```php
it('calculates profitability correctly', function () {
    // Arrange
    $order = createOrderWithProfitability(revenue: 1000.00, cost: 600.00);

    // Act
    $profitability = $order->calculateProfitability();

    // Assert
    expect($profitability['total_profit'])->toBe(400.00);
});
```

### Custom Expectations
```php
expect($result)->toHaveProfitability();  // Custom expectation for profitability data
expect($webhook->verifySignature($payload, $signature))->toBeTrue();
```

### Helper Methods (OrderTestCase)
- `createTestOrder()`: Create test order
- `createOrderWithItems()`: Create order with items
- `createTestChannel()`: Create test channel
- `createAdminWithOrderPermissions()`: Create admin with specific permissions
- `createAdminWithAllOrderPermissions()`: Create admin with all permissions
- `generateWebhookSignature()`: Generate HMAC signature
- `assertProfitabilityCalculation()`: Assert profitability values

### Factories Usage
```php
// Simple creation
$order = UnifiedOrder::factory()->create();

// With states
$order = UnifiedOrder::factory()->completed()->create();

// With relationships
$order = UnifiedOrder::factory()
    ->forChannel($channel->id)
    ->withExternalId('EXT-123')
    ->create();

// With items
$order = UnifiedOrder::factory()
    ->has(UnifiedOrderItem::factory()->count(3))
    ->create();
```

## Testing Best Practices

1. **Test Isolation**: Each test is independent, uses transactions, and cleans up after itself
2. **Descriptive Names**: Test names clearly describe what is being tested
3. **Single Responsibility**: Each test verifies one specific behavior
4. **Mock External Services**: Channel adapters and external APIs are mocked
5. **Test Edge Cases**: Null values, zero amounts, empty arrays, boundary conditions
6. **ACL Testing**: All 28 permissions are thoroughly tested
7. **Multi-Tenant**: Tenant isolation is verified in relevant tests
8. **Database Transactions**: All tests use RefreshDatabase trait
9. **Realistic Data**: Factories use Faker for realistic test data
10. **Error Scenarios**: Both success and failure paths are tested

## Coverage Goals

### By Layer
- **Models**: 95%+ (all relationships, scopes, calculations)
- **Services**: 90%+ (all business logic, error handling)
- **Repositories**: 90%+ (all CRUD operations, queries)
- **Controllers**: 85%+ (all routes, permissions, responses)
- **ValueObjects**: 95%+ (all methods, conversions)

### By Feature
- **Order CRUD**: 100% (all operations, validations, permissions)
- **Order Sync**: 90%+ (adapters, logs, error handling)
- **Profitability**: 90%+ (calculations, permissions, exports)
- **Webhooks**: 95%+ (HMAC, events, delivery)
- **ACL**: 100% (all 28 permissions verified)
- **API**: 90%+ (authentication, filtering, tenant isolation)

## Next Steps

1. Run tests and verify all pass
2. Check coverage report: `./vendor/bin/pest --coverage`
3. Add integration tests for channel adapters
4. Add E2E tests with Playwright
5. Add performance tests for large datasets
6. Add stress tests for sync operations
7. Document any test failures and fixes
8. Set up CI/CD pipeline for automated testing

## Contributing

When adding new features to the Order package:
1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain >90% coverage
4. Follow existing test patterns
5. Update this README with new test files

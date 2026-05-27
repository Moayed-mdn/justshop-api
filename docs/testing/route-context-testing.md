# Route Context Testing Guide

This guide explains how to test the new context-based routing and ensure identity isolation.

## Objectives
- Verify that canonical routes resolve correctly.
- Verify that legacy routes still work as aliases.
- Ensure that actors cannot cross context boundaries.
- Ensure tenant isolation is preserved.

## Test Categories

### 1. Context Isolation Tests
These tests verify that an actor with identity A cannot access routes belonging to context B.
```php
// Example: Platform user cannot access Merchant APIs without explicit permission
public function test_platform_user_cannot_access_merchant_endpoints_without_store_context()
{
    $platformUser = User::factory()->superAdmin()->create();
    
    $response = $this->actingAs($platformUser, 'merchant')
        ->getJson('/api/v1/merchant/stores/1/products');
        
    $response->assertStatus(403);
}
```

### 2. Legacy Alias Verification
These tests ensure that legacy routes correctly alias the new context controllers.
```php
public function test_legacy_admin_leads_resolves_to_platform_leads()
{
    $admin = User::factory()->superAdmin()->create();
    
    $response = $this->actingAs($admin, 'merchant')
        ->getJson('/api/v1/admin/leads');
        
    $response->assertStatus(200);
    $response->assertHeader('X-API-Deprecated', 'true');
}
```

### 3. Identity Type Enforcement
Verify that the `identity.route` middleware rejects tokens of the wrong type.
```php
public function test_merchant_token_rejected_by_customer_context()
{
    $merchantUser = User::factory()->create();
    
    $response = $this->actingAs($merchantUser, 'merchant')
        ->getJson('/api/v1/customer/me');
        
    $response->assertStatus(401); // Or 403 depending on middleware implementation
}
```

## Running Context Tests
We have introduced a new test suite specifically for context verification:
```bash
php artisan test --testsuite=ContextIsolation
```

## Best Practices
- Always test with multiple actors (Platform Admin, Merchant Owner, Merchant Staff, Customer, Guest).
- Always verify the presence of `X-API-Deprecated` headers for legacy routes.
- Verify that tenant-scoped routes still require a valid `store` parameter.

<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use App\Repositories\Product\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use RuntimeException;
use Tests\TestCase;

class RepositoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Step 5 Hardening: Verify that repositories throw exception if context is missing.
     */
    public function test_repository_throws_exception_if_tenant_context_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tenant context missing for scoped repository');

        // Ensure no storeId is bound in the container
        App::forgetInstance('storeId');

        $repository = App::make(ProductRepository::class);
        
        // This should trigger the scopedQuery() check
        $repository->findScoped(1);
    }

    /**
     * Step 5 Hardening: Verify that repositories automatically scope queries.
     */
    public function test_repository_automatically_scopes_queries_by_context(): void
    {
        /** @var Store $storeA */
        $storeA = Store::factory()->create();
        /** @var Store $storeB */
        $storeB = Store::factory()->create();

        $categoryA = \App\Models\Category::create(['store_id' => $storeA->id, 'slug' => 'cat-a']);
        $brandA = \App\Models\Brand::create(['store_id' => $storeA->id, 'name' => 'brand-a', 'slug' => 'brand-a']);

        $productA = Product::create([
            'store_id' => $storeA->id,
            'category_id' => $categoryA->id,
            'brand_id' => $brandA->id,
        ]);

        $categoryB = \App\Models\Category::create(['store_id' => $storeB->id, 'slug' => 'cat-b']);
        $brandB = \App\Models\Brand::create(['store_id' => $storeB->id, 'name' => 'brand-b', 'slug' => 'brand-b']);

        $productB = Product::create([
            'store_id' => $storeB->id,
            'category_id' => $categoryB->id,
            'brand_id' => $brandB->id,
        ]);

        $repository = App::make(ProductRepository::class);

        // 1. Set context to Store A
        App::instance('storeId', $storeA->id);
        
        $foundA = $repository->findScoped($productA->id);
        $foundB = $repository->findScoped($productB->id);

        $this->assertNotNull($foundA);
        $this->assertEquals($storeA->id, $foundA->store_id);
        $this->assertNull($foundB, 'Product from Store B should not be accessible in Store A context');

        // 2. Set context to Store B
        App::instance('storeId', $storeB->id);
        
        $foundA_in_B = $repository->findScoped($productA->id);
        $foundB_in_B = $repository->findScoped($productB->id);

        $this->assertNull($foundA_in_B, 'Product from Store A should not be accessible in Store B context');
        $this->assertNotNull($foundB_in_B);
        $this->assertEquals($storeB->id, $foundB_in_B->store_id);
    }

    /**
     * Step 5 Hardening: Verify that StoreRepository findById enforces accessibility.
     */
    public function test_store_repository_find_by_id_enforces_accessibility(): void
    {
        /** @var User $user */
        $user = User::factory()->merchant()->create();
        /** @var Store $storeA */
        $storeA = Store::factory()->create(['owner_id' => $user->id]);
        
        // Attach user to store A
        $user->stores()->attach($storeA->id, ['role' => \App\Enums\Store\StoreRoleEnum::STORE_ADMIN->value]);

        /** @var Store $storeB */
        $storeB = Store::factory()->create(); // Not owned by user

        $this->actingAs($user);
        $repository = new \App\Repositories\Store\StoreRepository();

        // Accessing owned/member store -> Success
        $this->assertNotNull($repository->findById($storeA->id));

        // Accessing non-owned store -> Exception
        $this->expectException(\App\Exceptions\Store\StoreNotFoundException::class);
        $repository->findById($storeB->id);
    }
}

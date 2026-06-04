<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use App\Enums\Auth\AuthDomainEnum;
use App\Models\User;
use App\Services\Auth\SessionOwnershipManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SessionOwnershipManagerTest extends TestCase
{
    use RefreshDatabase;

    private SessionOwnershipManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(SessionOwnershipManager::class);
    }

    public function test_tag_stores_auth_domain_in_session(): void
    {
        $user = User::factory()->merchant()->verified()->create();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $request->session()->start();

        $this->manager->tag($request, $user, 'merchant');

        $this->assertSame('merchant', $request->session()->get('auth_domain'));
    }

    public function test_tag_stores_actor_type_in_session(): void
    {
        $user = User::factory()->customer()->verified()->create();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $request->session()->start();

        $this->manager->tag($request, $user, 'customer');

        $this->assertSame('customer', $request->session()->get('actor_type'));
    }

    public function test_tag_stores_actor_id_in_session(): void
    {
        $user = User::factory()->merchant()->verified()->create();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $request->session()->start();

        $this->manager->tag($request, $user, 'merchant');

        $this->assertSame((int) $user->id, $request->session()->get('actor_id'));
    }

    public function test_get_auth_domain_returns_null_when_not_tagged(): void
    {
        $this->assertNull($this->manager->getAuthDomain());
    }

    public function test_get_actor_type_returns_null_when_not_tagged(): void
    {
        $this->assertNull($this->manager->getActorType());
    }

    public function test_get_actor_id_returns_null_when_not_tagged(): void
    {
        $this->assertNull($this->manager->getActorId());
    }

    public function test_invalidate_clears_all_ownership_keys(): void
    {
        $user = User::factory()->merchant()->verified()->create();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $request->session()->start();

        $this->manager->tag($request, $user, 'merchant');

        $this->assertSame('merchant', $this->manager->getAuthDomain());

        $this->manager->invalidate($request);

        $this->assertNull($request->session()->get('auth_domain'));
        $this->assertNull($request->session()->get('actor_type'));
        $this->assertNull($request->session()->get('actor_id'));
    }

    public function test_get_auth_domain_returns_tagged_value(): void
    {
        $user = User::factory()->merchant()->verified()->create();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $request->session()->start();

        $this->manager->tag($request, $user, 'merchant');

        $this->assertSame('merchant', $this->manager->getAuthDomain());
    }

    public function test_get_actor_type_returns_tagged_value(): void
    {
        $user = User::factory()->customer()->verified()->create();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession($this->app->make('session')->driver());
        $request->session()->start();

        $this->manager->tag($request, $user, 'customer');

        $this->assertSame('customer', $this->manager->getActorType());
    }
}

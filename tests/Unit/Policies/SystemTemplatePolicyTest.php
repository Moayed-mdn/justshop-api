<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\Store\StoreRoleEnum;
use App\Enums\Theme\TemplateTypeEnum;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeTemplate;
use App\Models\User;
use App\Policies\Theme\SystemTemplatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemTemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    private SystemTemplatePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(SystemTemplatePolicy::class);
    }

    public function test_merchant_member_can_view_any(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $this->assertTrue($this->policy->viewAny($user, $store));
    }

    public function test_non_merchant_cannot_view_any(): void
    {
        $user = User::factory()->customer()->create();
        $store = Store::factory()->create();

        $this->assertFalse($this->policy->viewAny($user, $store));
    }

    public function test_non_member_cannot_view_any(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->create();

        $this->assertFalse($this->policy->viewAny($user, $store));
    }

    public function test_merchant_member_can_create(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $this->assertTrue($this->policy->create($user, $store));
    }

    public function test_merchant_admin_can_update(): void
    {
        $user = User::factory()->merchant()->create();
        $owner = User::factory()->merchant()->create();
        $store = Store::factory()->for($owner, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $theme = Theme::create(['store_id' => $store->id, 'name' => 'Test Theme']);
        $template = ThemeTemplate::create([
            'theme_id' => $theme->id,
            'name' => 'Test Template',
            'type' => TemplateTypeEnum::PAGE,
            'handle' => 'test-template',
        ]);

        $this->assertTrue($this->policy->update($user, $template));
    }

    public function test_member_cannot_update(): void
    {
        $user = User::factory()->merchant()->create();
        $owner = User::factory()->merchant()->create();
        $store = Store::factory()->for($owner, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STAFF->value]);
        $theme = Theme::create(['store_id' => $store->id, 'name' => 'Test Theme']);
        $template = ThemeTemplate::create([
            'theme_id' => $theme->id,
            'name' => 'Test Template',
            'type' => TemplateTypeEnum::PAGE,
            'handle' => 'test-template',
        ]);

        $this->assertFalse($this->policy->update($user, $template));
    }

    public function test_merchant_admin_can_delete(): void
    {
        $user = User::factory()->merchant()->create();
        $owner = User::factory()->merchant()->create();
        $store = Store::factory()->for($owner, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $theme = Theme::create(['store_id' => $store->id, 'name' => 'Test Theme']);
        $template = ThemeTemplate::create([
            'theme_id' => $theme->id,
            'name' => 'Test Template',
            'type' => TemplateTypeEnum::PAGE,
            'handle' => 'test-template',
        ]);

        $this->assertTrue($this->policy->delete($user, $template));
    }
}

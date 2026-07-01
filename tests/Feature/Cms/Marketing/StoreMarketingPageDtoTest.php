<?php

declare(strict_types=1);

namespace Tests\Feature\Cms\Marketing;

use App\DTOs\Cms\Marketing\Store\Admin\CreateStoreMarketingPageDTO;
use App\DTOs\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageDTO;
use App\Http\Requests\Cms\Marketing\Store\Admin\CreateStoreMarketingPageRequest;
use App\Http\Requests\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageRequest;
use App\Models\User;
use Tests\TestCase;

class StoreMarketingPageDtoTest extends TestCase
{
    public function test_create_dto_keeps_null_page_template_id_as_null(): void
    {
        /** @var CreateStoreMarketingPageRequest $request */
        $request = CreateStoreMarketingPageRequest::create('/api/v1/merchant/stores/1/cms/pages', 'POST', [
            'title' => ['en' => 'Landing Page', 'ar' => 'صفحة هبوط'],
            'slug' => ['en' => 'landing-page', 'ar' => 'landing-page'],
            'status' => 'draft',
            'page_template_id' => null,
        ]);

        $request->setUserResolver(fn (): User => User::factory()->make(['id' => 99]));

        $dto = CreateStoreMarketingPageDTO::fromRequest($request, 1);

        $this->assertNull($dto->pageTemplateId);
    }

    public function test_update_dto_keeps_null_page_template_id_as_null(): void
    {
        /** @var UpdateStoreMarketingPageRequest $request */
        $request = UpdateStoreMarketingPageRequest::create('/api/v1/merchant/stores/1/cms/pages/5', 'PUT', [
            'title' => ['en' => 'Landing Page', 'ar' => 'صفحة هبوط'],
            'slug' => ['en' => 'landing-page', 'ar' => 'landing-page'],
            'status' => 'draft',
            'page_template_id' => null,
        ]);

        $request->setUserResolver(fn (): User => User::factory()->make(['id' => 99]));

        $dto = UpdateStoreMarketingPageDTO::fromRequest($request, 1, 5);

        $this->assertNull($dto->pageTemplateId);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Cms\Marketing;

use App\DTOs\Cms\Marketing\Store\Admin\CreateStoreMarketingPageDTO;
use App\DTOs\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageDTO;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Http\Requests\Cms\Marketing\Store\Admin\CreateStoreMarketingPageRequest;
use App\Http\Requests\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageRequest;
use App\Models\User;
use Tests\TestCase;

class StoreMarketingPageDtoTest extends TestCase
{
    private function baseCreatePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => ['en' => 'Landing Page', 'ar' => 'صفحة هبوط'],
            'slug' => ['en' => 'landing-page', 'ar' => 'landing-page'],
            'status' => 'draft',
        ], $overrides);
    }

    private function createDto(array $payload, int $userId = 99): CreateStoreMarketingPageDTO
    {
        /** @var CreateStoreMarketingPageRequest $request */
        $request = CreateStoreMarketingPageRequest::create(
            '/api/v1/merchant/stores/1/cms/pages',
            'POST',
            $this->baseCreatePayload($payload),
        );

        $request->setUserResolver(fn (): User => User::factory()->make(['id' => $userId]));

        return CreateStoreMarketingPageDTO::fromRequest($request, 1);
    }

    private function updateDto(array $payload, int $userId = 99): UpdateStoreMarketingPageDTO
    {
        /** @var UpdateStoreMarketingPageRequest $request */
        $request = UpdateStoreMarketingPageRequest::create(
            '/api/v1/merchant/stores/1/cms/pages/5',
            'PUT',
            $this->baseCreatePayload($payload),
        );

        $request->setUserResolver(fn (): User => User::factory()->make(['id' => $userId]));

        return UpdateStoreMarketingPageDTO::fromRequest($request, 1, 5);
    }

    public function test_create_dto_keeps_null_page_template_id_as_null(): void
    {
        $dto = $this->createDto(['page_template_id' => null]);

        $this->assertNull($dto->pageTemplateId);
    }

    public function test_update_dto_keeps_null_page_template_id_as_null(): void
    {
        $dto = $this->updateDto(['page_template_id' => null]);

        $this->assertNull($dto->pageTemplateId);
    }

    public function test_create_dto_coerces_numeric_page_template_id_to_int(): void
    {
        $dto = $this->createDto(['page_template_id' => '7']);

        $this->assertSame(7, $dto->pageTemplateId);
    }

    public function test_create_dto_defaults_sort_order_to_zero_when_absent(): void
    {
        $dto = $this->createDto([]);

        $this->assertSame(0, $dto->sortOrder);
    }

    public function test_create_dto_keeps_provided_sort_order(): void
    {
        $dto = $this->createDto(['sort_order' => 5]);

        $this->assertSame(5, $dto->sortOrder);
    }

    public function test_create_dto_defaults_is_homepage_to_false_when_absent(): void
    {
        $dto = $this->createDto([]);

        $this->assertFalse($dto->isHomepage);
    }

    public function test_create_dto_keeps_provided_is_homepage_true(): void
    {
        $dto = $this->createDto(['is_homepage' => true]);

        $this->assertTrue($dto->isHomepage);
    }

    public function test_create_dto_resolves_template_enum_when_provided(): void
    {
        $dto = $this->createDto(['template' => MarketingPageTemplateEnum::CAMPAIGN->value]);

        $this->assertSame(MarketingPageTemplateEnum::CAMPAIGN, $dto->template);
    }

    public function test_create_dto_template_is_null_when_absent(): void
    {
        $dto = $this->createDto([]);

        $this->assertNull($dto->template);
    }

    public function test_create_dto_seo_is_null_when_absent_and_array_when_present(): void
    {
        $withoutSeo = $this->createDto([]);
        $this->assertNull($withoutSeo->seo);

        $withSeo = $this->createDto([
            'seo' => ['meta_title' => ['en' => 'SEO Title']],
        ]);
        $this->assertSame(['meta_title' => ['en' => 'SEO Title']], $withSeo->seo);
    }

    public function test_create_dto_excerpt_and_content_are_null_when_absent(): void
    {
        $dto = $this->createDto([]);

        $this->assertNull($dto->excerpt);
        $this->assertNull($dto->content);
    }

    public function test_create_dto_sections_default_to_empty_array_when_absent(): void
    {
        $dto = $this->createDto([]);

        $this->assertSame([], $dto->sections);
    }

    public function test_create_dto_sets_created_by_and_updated_by_from_authenticated_user(): void
    {
        $dto = $this->createDto([], userId: 42);

        $this->assertSame(42, $dto->createdBy);
        $this->assertSame(42, $dto->updatedBy);
    }

    /**
     * The update DTO distinguishes "sections not sent" (null — leave
     * existing sections untouched) from "sections sent" (array — replace
     * them), unlike the create DTO which always defaults to an empty
     * array. This is real, intentional behaviour in
     * UpdateStoreMarketingPageDTO::fromRequest() (it uses
     * $request->has('sections') to decide), so it is asserted explicitly
     * here rather than assumed.
     */
    public function test_update_dto_sections_are_null_when_absent(): void
    {
        $dto = $this->updateDto([]);

        $this->assertNull($dto->sections);
    }

    public function test_update_dto_sections_are_array_when_present(): void
    {
        $dto = $this->updateDto([
            'sections' => [
                ['section_type' => 'hero', 'identifier' => 'hero-main', 'sort_order' => 0],
            ],
        ]);

        $this->assertIsArray($dto->sections);
        $this->assertCount(1, $dto->sections);
        $this->assertSame('hero', $dto->sections[0]['section_type']);
    }

    public function test_update_dto_sets_updated_by_from_authenticated_user(): void
    {
        $dto = $this->updateDto([], userId: 17);

        $this->assertSame(17, $dto->updatedBy);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Cms\Blog\BlogPostPublishStateEnum;
use App\Enums\RoleEnum;
use App\Models\BlogCategory;
use App\Models\BlogCategoryTranslation;
use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use App\Models\BlogTag;
use App\Models\BlogTagTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlogModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected BlogCategory $category;
    protected BlogTag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Admin
        $this->admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $this->admin->assignRole($role);

        // Setup Category
        $this->category = BlogCategory::create();
        BlogCategoryTranslation::create([
            'blog_category_id' => $this->category->id,
            'locale' => 'en',
            'name' => 'Technology',
            'slug' => 'technology',
        ]);

        // Setup Tag
        $this->tag = BlogTag::create();
        BlogTagTranslation::create([
            'blog_tag_id' => $this->tag->id,
            'locale' => 'en',
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
    }

    public function test_public_blog_index_returns_published_posts(): void
    {
        // Create a published post
        $post = BlogPost::create([
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        BlogPostTranslation::create([
            'blog_post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => 'Content',
        ]);

        // Create a draft post
        $draft = BlogPost::create(['is_published' => false]);
        BlogPostTranslation::create([
            'blog_post_id' => $draft->id,
            'locale' => 'en',
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => 'Content',
        ]);

        $response = $this->getJson('/api/v1/public/blog?locale=en');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.title', 'Published Post');
    }

    public function test_public_blog_show_returns_post_by_slug(): void
    {
        $post = BlogPost::create([
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        BlogPostTranslation::create([
            'blog_post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Detailed content',
        ]);

        $response = $this->getJson('/api/v1/public/blog/test-post?locale=en');

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Test Post')
            ->assertJsonPath('data.content', 'Detailed content');
    }

    public function test_admin_can_create_blog_post(): void
    {
        $payload = [
            'status' => 'published',
            'blog_category_id' => $this->category->id,
            'tag_ids' => [$this->tag->id],
            'translations' => [
                'en' => [
                    'title' => 'New Admin Post',
                    'slug' => 'new-admin-post',
                    'content' => 'Markdown content here',
                ],
                'ar' => [
                    'title' => 'مقال جديد',
                    'slug' => 'new-admin-post-ar',
                    'content' => 'محتوى هنا',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/cms/blog', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('blog_post_translations', ['title' => 'New Admin Post']);
        $this->assertDatabaseHas('blog_post_tag', ['blog_tag_id' => $this->tag->id]);
    }

    public function test_admin_can_update_blog_post(): void
    {
        $post = BlogPost::create(['is_published' => false]);
        BlogPostTranslation::create([
            'blog_post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Old Title',
            'slug' => 'old-slug',
            'content' => 'Old content',
        ]);
        BlogPostTranslation::create([
            'blog_post_id' => $post->id,
            'locale' => 'ar',
            'title' => 'عنوان قديم',
            'slug' => 'old-slug-ar',
            'content' => 'محتوى قديم',
        ]);

        $payload = [
            'status' => 'published',
            'translations' => [
                'en' => [
                    'title' => 'Updated Title',
                    'slug' => 'updated-slug',
                    'content' => 'Updated content',
                ],
                'ar' => [
                    'title' => 'عنوان محدث',
                    'slug' => 'updated-slug-ar',
                    'content' => 'محتوى محدث',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/cms/blog/{$post->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('blog_post_translations', ['title' => 'Updated Title', 'blog_post_id' => $post->id]);
    }

    public function test_admin_can_publish_draft_post(): void
    {
        $post = BlogPost::create(['is_published' => false]);
        
        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/cms/blog/{$post->id}/publish");

        $response->assertStatus(200);
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);
    }
}

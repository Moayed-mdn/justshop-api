<?php

namespace Tests\Feature;

use App\Enums\Cms\Blog\BlogPostPublishStateEnum;
use App\Enums\RoleEnum;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
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
        $this->category = BlogCategory::create([
            'name' => ['en' => 'Technology'],
            'slug' => ['en' => 'technology'],
        ]);

        // Setup Tag
        $this->tag = BlogTag::create([
            'name' => ['en' => 'Laravel'],
            'slug' => ['en' => 'laravel'],
        ]);
    }

    public function test_public_blog_index_returns_published_posts(): void
    {
        // Create a published post
        $post = BlogPost::create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'title' => ['en' => 'Published Post'],
            'slug' => ['en' => 'published-post'],
            'content' => ['en' => 'Content'],
            'seo' => [
                'title' => ['en' => 'Published Post'],
                'description' => ['en' => 'Description'],
                'robots' => ['en' => 'index,follow'],
            ],
        ]);

        // Create a draft post
        $draft = BlogPost::create([
            'is_published' => false,
            'title' => ['en' => 'Draft Post'],
            'slug' => ['en' => 'draft-post'],
            'content' => ['en' => 'Content'],
        ]);

        $response = $this->getJson('/api/v1/public/cms/blog?locale=en');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.title', 'Published Post');
    }

    public function test_public_blog_show_returns_post_by_slug(): void
    {
        $post = BlogPost::create([
            'is_published' => true,
            'published_at' => now()->subDay(),
            'title' => ['en' => 'Test Post'],
            'slug' => ['en' => 'test-post'],
            'content' => ['en' => 'Detailed content'],
            'seo' => [
                'title' => ['en' => 'Test Post'],
                'description' => ['en' => 'Description'],
                'robots' => ['en' => 'index,follow'],
            ],
        ]);

        $response = $this->getJson('/api/v1/public/cms/blog/test-post?locale=en');

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
        $this->assertDatabaseHas('blog_posts', [
            'title->en' => 'New Admin Post',
            'title->ar' => 'مقال جديد',
        ]);
        $this->assertDatabaseHas('blog_post_tag', ['blog_tag_id' => $this->tag->id]);
    }

    public function test_admin_can_update_blog_post(): void
    {
        $post = BlogPost::create([
            'is_published' => false,
            'title' => ['en' => 'Old Title', 'ar' => 'عنوان قديم'],
            'slug' => ['en' => 'old-slug', 'ar' => 'old-slug-ar'],
            'content' => ['en' => 'Old content', 'ar' => 'محتوى قديم'],
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
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title->en' => 'Updated Title',
            'title->ar' => 'عنوان محدث',
        ]);
    }

    public function test_admin_can_publish_draft_post(): void
    {
        $post = BlogPost::create([
            'is_published' => false,
            'title' => ['en' => 'Draft'],
            'slug' => ['en' => 'draft'],
            'content' => ['en' => 'Content'],
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/cms/blog/{$post->id}/publish");

        $response->assertStatus(200);
        $this->assertTrue($post->fresh()->is_published);
        $this->assertNotNull($post->fresh()->published_at);
    }

    public function test_non_super_admin_cannot_create_blog_post(): void
    {
        $user = User::factory()->create();

        $payload = [
            'status' => 'published',
            'blog_category_id' => $this->category->id,
            'translations' => [
                'en' => [
                    'title' => 'Unauthorized Post',
                    'slug' => 'unauthorized-post',
                    'content' => 'Denied content',
                ],
                'ar' => [
                    'title' => 'ممنوع',
                    'slug' => 'unauthorized-post-ar',
                    'content' => 'محتوى مرفوض',
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/api/v1/admin/cms/blog', $payload)
            ->assertForbidden();
    }
}

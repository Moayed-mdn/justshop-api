<?php

namespace App\Http\Controllers\Api\Platform\Mock;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformDocumentationController extends Controller
{
    private array $mockDocumentation;

    public function __construct()
    {
        $this->mockDocumentation = $this->generateMockData();
    }

    /**
     * List all documentation with hierarchy
     */
    public function index(Request $request): JsonResponse
    {
        $docs = $this->mockDocumentation;
        
        // Filter by search
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $docs = array_filter($docs, function($doc) use ($search) {
                $enTitle = strtolower($doc['translations']['en']['title'] ?? '');
                $arTitle = strtolower($doc['translations']['ar']['title'] ?? '');
                return str_contains($enTitle, $search) || str_contains($arTitle, $search);
            });
            $docs = array_values($docs);
        }

        // Build hierarchy
        $tree = $this->buildTree($docs);

        return response()->json([
            'success' => true,
            'data' => $tree,
            'meta' => [
                'total' => count($docs),
            ],
        ]);
    }

    /**
     * Get a single documentation item
     */
    public function show(int $id): JsonResponse
    {
        $doc = collect($this->mockDocumentation)->firstWhere('id', $id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $doc,
        ]);
    }

    /**
     * Create a new documentation item
     */
    public function store(Request $request): JsonResponse
    {
        $newId = max(array_column($this->mockDocumentation, 'id')) + 1;

        $doc = [
            'id' => $newId,
            'parent_id' => $request->input('parent_id'),
            'order' => $request->input('order', 999),
            'is_published' => false,
            'translations' => $request->input('translations', []),
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Documentation created successfully',
            'data' => $doc,
        ], 201);
    }

    /**
     * Update a documentation item
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $doc = collect($this->mockDocumentation)->firstWhere('id', $id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation not found',
            ], 404);
        }

        $updated = array_merge($doc, [
            'parent_id' => $request->input('parent_id', $doc['parent_id']),
            'order' => $request->input('order', $doc['order']),
            'translations' => $request->input('translations', $doc['translations']),
            'updated_at' => now()->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documentation updated successfully',
            'data' => $updated,
        ]);
    }

    /**
     * Delete a documentation item
     */
    public function destroy(int $id): JsonResponse
    {
        $doc = collect($this->mockDocumentation)->firstWhere('id', $id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Documentation deleted successfully',
        ]);
    }

    /**
     * Publish a documentation item
     */
    public function publish(int $id): JsonResponse
    {
        $doc = collect($this->mockDocumentation)->firstWhere('id', $id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation not found',
            ], 404);
        }

        $doc['is_published'] = true;
        $doc['updated_at'] = now()->toISOString();

        return response()->json([
            'success' => true,
            'message' => 'Documentation published successfully',
            'data' => $doc,
        ]);
    }

    /**
     * Unpublish a documentation item
     */
    public function unpublish(int $id): JsonResponse
    {
        $doc = collect($this->mockDocumentation)->firstWhere('id', $id);

        if (!$doc) {
            return response()->json([
                'success' => false,
                'message' => 'Documentation not found',
            ], 404);
        }

        $doc['is_published'] = false;
        $doc['updated_at'] = now()->toISOString();

        return response()->json([
            'success' => true,
            'message' => 'Documentation unpublished successfully',
            'data' => $doc,
        ]);
    }

    /**
     * Reorder documentation items
     */
    public function reorder(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        return response()->json([
            'success' => true,
            'message' => 'Documentation reordered successfully',
            'data' => $items,
        ]);
    }

    /**
     * Generate mock documentation data
     */
    private function generateMockData(): array
    {
        return [
            // Root Level: Getting Started
            [
                'id' => 1,
                'parent_id' => null,
                'order' => 1,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Getting Started',
                        'slug' => 'getting-started',
                        'content' => '<h1>Getting Started</h1><p>Welcome to our platform documentation. This guide will help you get up and running quickly.</p>',
                        'meta_title' => 'Getting Started - Documentation',
                        'meta_description' => 'Get started with our platform',
                    ],
                    'ar' => [
                        'title' => 'البدء',
                        'slug' => 'getting-started-ar',
                        'content' => '<h1>البدء</h1><p>مرحباً بك في وثائق منصتنا. سيساعدك هذا الدليل على البدء بسرعة.</p>',
                        'meta_title' => 'البدء - الوثائق',
                        'meta_description' => 'ابدأ مع منصتنا',
                    ],
                ],
                'created_at' => '2026-01-01T00:00:00.000000Z',
                'updated_at' => '2026-01-15T00:00:00.000000Z',
            ],
            // Child: Installation
            [
                'id' => 2,
                'parent_id' => 1,
                'order' => 1,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Installation',
                        'slug' => 'installation',
                        'content' => '<h2>Installation</h2><p>Follow these steps to install the platform...</p><ol><li>Download the package</li><li>Run the installer</li><li>Configure your settings</li></ol>',
                        'meta_title' => 'Installation Guide',
                        'meta_description' => 'How to install our platform',
                    ],
                    'ar' => [
                        'title' => 'التثبيت',
                        'slug' => 'installation-ar',
                        'content' => '<h2>التثبيت</h2><p>اتبع هذه الخطوات لتثبيت المنصة...</p>',
                        'meta_title' => 'دليل التثبيت',
                        'meta_description' => 'كيفية تثبيت منصتنا',
                    ],
                ],
                'created_at' => '2026-01-02T00:00:00.000000Z',
                'updated_at' => '2026-01-16T00:00:00.000000Z',
            ],
            // Child: Configuration
            [
                'id' => 3,
                'parent_id' => 1,
                'order' => 2,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Configuration',
                        'slug' => 'configuration',
                        'content' => '<h2>Configuration</h2><p>Configure your platform settings...</p>',
                        'meta_title' => 'Configuration Guide',
                        'meta_description' => 'Configure platform settings',
                    ],
                    'ar' => [
                        'title' => 'الإعدادات',
                        'slug' => 'configuration-ar',
                        'content' => '<h2>الإعدادات</h2><p>قم بتكوين إعدادات المنصة...</p>',
                        'meta_title' => 'دليل الإعدادات',
                        'meta_description' => 'تكوين إعدادات المنصة',
                    ],
                ],
                'created_at' => '2026-01-03T00:00:00.000000Z',
                'updated_at' => '2026-01-17T00:00:00.000000Z',
            ],
            // Root Level: User Guide
            [
                'id' => 4,
                'parent_id' => null,
                'order' => 2,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'User Guide',
                        'slug' => 'user-guide',
                        'content' => '<h1>User Guide</h1><p>Learn how to use the platform effectively.</p>',
                        'meta_title' => 'User Guide - Documentation',
                        'meta_description' => 'Complete user guide',
                    ],
                    'ar' => [
                        'title' => 'دليل المستخدم',
                        'slug' => 'user-guide-ar',
                        'content' => '<h1>دليل المستخدم</h1><p>تعلم كيفية استخدام المنصة بفعالية.</p>',
                        'meta_title' => 'دليل المستخدم - الوثائق',
                        'meta_description' => 'دليل المستخدم الكامل',
                    ],
                ],
                'created_at' => '2026-01-04T00:00:00.000000Z',
                'updated_at' => '2026-01-18T00:00:00.000000Z',
            ],
            // Child: Dashboard
            [
                'id' => 5,
                'parent_id' => 4,
                'order' => 1,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Dashboard Overview',
                        'slug' => 'dashboard-overview',
                        'content' => '<h2>Dashboard Overview</h2><p>Your dashboard provides an at-a-glance view of your store performance.</p>',
                        'meta_title' => 'Dashboard Overview',
                        'meta_description' => 'Understand your dashboard',
                    ],
                    'ar' => [
                        'title' => 'نظرة عامة على لوحة القيادة',
                        'slug' => 'dashboard-overview-ar',
                        'content' => '<h2>نظرة عامة على لوحة القيادة</h2><p>توفر لوحة القيادة الخاصة بك عرضاً سريعاً لأداء متجرك.</p>',
                        'meta_title' => 'نظرة عامة على لوحة القيادة',
                        'meta_description' => 'فهم لوحة القيادة',
                    ],
                ],
                'created_at' => '2026-01-05T00:00:00.000000Z',
                'updated_at' => '2026-01-19T00:00:00.000000Z',
            ],
            // Child: Managing Users
            [
                'id' => 6,
                'parent_id' => 4,
                'order' => 2,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Managing Users',
                        'slug' => 'managing-users',
                        'content' => '<h2>Managing Users</h2><p>Add, edit, and remove users from your store.</p>',
                        'meta_title' => 'Managing Users',
                        'meta_description' => 'User management guide',
                    ],
                    'ar' => [
                        'title' => 'إدارة المستخدمين',
                        'slug' => 'managing-users-ar',
                        'content' => '<h2>إدارة المستخدمين</h2><p>إضافة وتعديل وإزالة المستخدمين من متجرك.</p>',
                        'meta_title' => 'إدارة المستخدمين',
                        'meta_description' => 'دليل إدارة المستخدمين',
                    ],
                ],
                'created_at' => '2026-01-06T00:00:00.000000Z',
                'updated_at' => '2026-01-20T00:00:00.000000Z',
            ],
            // Root Level: Store Management
            [
                'id' => 7,
                'parent_id' => null,
                'order' => 3,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Store Management',
                        'slug' => 'store-management',
                        'content' => '<h1>Store Management</h1><p>Complete guide to managing your store.</p>',
                        'meta_title' => 'Store Management Guide',
                        'meta_description' => 'Manage your store effectively',
                    ],
                    'ar' => [
                        'title' => 'إدارة المتجر',
                        'slug' => 'store-management-ar',
                        'content' => '<h1>إدارة المتجر</h1><p>دليل كامل لإدارة متجرك.</p>',
                        'meta_title' => 'دليل إدارة المتجر',
                        'meta_description' => 'إدارة متجرك بفعالية',
                    ],
                ],
                'created_at' => '2026-01-07T00:00:00.000000Z',
                'updated_at' => '2026-01-21T00:00:00.000000Z',
            ],
            // Child: Products
            [
                'id' => 8,
                'parent_id' => 7,
                'order' => 1,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Managing Products',
                        'slug' => 'managing-products',
                        'content' => '<h2>Managing Products</h2><p>Add and manage your product catalog.</p>',
                        'meta_title' => 'Managing Products',
                        'meta_description' => 'Product management guide',
                    ],
                    'ar' => [
                        'title' => 'إدارة المنتجات',
                        'slug' => 'managing-products-ar',
                        'content' => '<h2>إدارة المنتجات</h2><p>إضافة وإدارة كتالوج المنتجات الخاص بك.</p>',
                        'meta_title' => 'إدارة المنتجات',
                        'meta_description' => 'دليل إدارة المنتجات',
                    ],
                ],
                'created_at' => '2026-01-08T00:00:00.000000Z',
                'updated_at' => '2026-01-22T00:00:00.000000Z',
            ],
            // Grandchild: Product Variants
            [
                'id' => 9,
                'parent_id' => 8,
                'order' => 1,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Product Variants',
                        'slug' => 'product-variants',
                        'content' => '<h3>Product Variants</h3><p>Create product variants with different sizes, colors, etc.</p>',
                        'meta_title' => 'Product Variants',
                        'meta_description' => 'Creating product variants',
                    ],
                    'ar' => [
                        'title' => 'متغيرات المنتج',
                        'slug' => 'product-variants-ar',
                        'content' => '<h3>متغيرات المنتج</h3><p>إنشاء متغيرات المنتج بأحجام وألوان مختلفة.</p>',
                        'meta_title' => 'متغيرات المنتج',
                        'meta_description' => 'إنشاء متغيرات المنتج',
                    ],
                ],
                'created_at' => '2026-01-09T00:00:00.000000Z',
                'updated_at' => '2026-01-23T00:00:00.000000Z',
            ],
            // Grandchild: Inventory
            [
                'id' => 10,
                'parent_id' => 8,
                'order' => 2,
                'is_published' => false,
                'translations' => [
                    'en' => [
                        'title' => 'Inventory Management',
                        'slug' => 'inventory-management',
                        'content' => '<h3>Inventory Management</h3><p>Track and manage your inventory levels.</p>',
                        'meta_title' => 'Inventory Management',
                        'meta_description' => 'Managing inventory',
                    ],
                    'ar' => [
                        'title' => 'إدارة المخزون',
                        'slug' => 'inventory-management-ar',
                        'content' => '<h3>إدارة المخزون</h3><p>تتبع وإدارة مستويات المخزون الخاص بك.</p>',
                        'meta_title' => 'إدارة المخزون',
                        'meta_description' => 'إدارة المخزون',
                    ],
                ],
                'created_at' => '2026-01-10T00:00:00.000000Z',
                'updated_at' => '2026-01-24T00:00:00.000000Z',
            ],
            // Child: Orders
            [
                'id' => 11,
                'parent_id' => 7,
                'order' => 2,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Order Management',
                        'slug' => 'order-management',
                        'content' => '<h2>Order Management</h2><p>Process and manage customer orders.</p>',
                        'meta_title' => 'Order Management',
                        'meta_description' => 'Managing orders',
                    ],
                    'ar' => [
                        'title' => 'إدارة الطلبات',
                        'slug' => 'order-management-ar',
                        'content' => '<h2>إدارة الطلبات</h2><p>معالجة وإدارة طلبات العملاء.</p>',
                        'meta_title' => 'إدارة الطلبات',
                        'meta_description' => 'إدارة الطلبات',
                    ],
                ],
                'created_at' => '2026-01-11T00:00:00.000000Z',
                'updated_at' => '2026-01-25T00:00:00.000000Z',
            ],
            // Root Level: API Reference
            [
                'id' => 12,
                'parent_id' => null,
                'order' => 4,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'API Reference',
                        'slug' => 'api-reference',
                        'content' => '<h1>API Reference</h1><p>Complete API documentation for developers.</p>',
                        'meta_title' => 'API Reference',
                        'meta_description' => 'Developer API documentation',
                    ],
                    'ar' => [
                        'title' => 'مرجع API',
                        'slug' => 'api-reference-ar',
                        'content' => '<h1>مرجع API</h1><p>وثائق API كاملة للمطورين.</p>',
                        'meta_title' => 'مرجع API',
                        'meta_description' => 'وثائق API للمطورين',
                    ],
                ],
                'created_at' => '2026-01-12T00:00:00.000000Z',
                'updated_at' => '2026-01-26T00:00:00.000000Z',
            ],
            // Child: Authentication
            [
                'id' => 13,
                'parent_id' => 12,
                'order' => 1,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'Authentication',
                        'slug' => 'api-authentication',
                        'content' => '<h2>Authentication</h2><p>Authenticate your API requests.</p><pre><code>Authorization: Bearer YOUR_TOKEN</code></pre>',
                        'meta_title' => 'API Authentication',
                        'meta_description' => 'How to authenticate API requests',
                    ],
                    'ar' => [
                        'title' => 'المصادقة',
                        'slug' => 'api-authentication-ar',
                        'content' => '<h2>المصادقة</h2><p>مصادقة طلبات API الخاصة بك.</p>',
                        'meta_title' => 'مصادقة API',
                        'meta_description' => 'كيفية مصادقة طلبات API',
                    ],
                ],
                'created_at' => '2026-01-13T00:00:00.000000Z',
                'updated_at' => '2026-01-27T00:00:00.000000Z',
            ],
            // Child: Endpoints
            [
                'id' => 14,
                'parent_id' => 12,
                'order' => 2,
                'is_published' => true,
                'translations' => [
                    'en' => [
                        'title' => 'API Endpoints',
                        'slug' => 'api-endpoints',
                        'content' => '<h2>API Endpoints</h2><p>List of all available API endpoints.</p>',
                        'meta_title' => 'API Endpoints',
                        'meta_description' => 'All available API endpoints',
                    ],
                    'ar' => [
                        'title' => 'نقاط نهاية API',
                        'slug' => 'api-endpoints-ar',
                        'content' => '<h2>نقاط نهاية API</h2><p>قائمة بجميع نقاط نهاية API المتاحة.</p>',
                        'meta_title' => 'نقاط نهاية API',
                        'meta_description' => 'جميع نقاط نهاية API المتاحة',
                    ],
                ],
                'created_at' => '2026-01-14T00:00:00.000000Z',
                'updated_at' => '2026-01-28T00:00:00.000000Z',
            ],
            // Root Level: Troubleshooting
            [
                'id' => 15,
                'parent_id' => null,
                'order' => 5,
                'is_published' => false,
                'translations' => [
                    'en' => [
                        'title' => 'Troubleshooting',
                        'slug' => 'troubleshooting',
                        'content' => '<h1>Troubleshooting</h1><p>Common issues and solutions.</p>',
                        'meta_title' => 'Troubleshooting Guide',
                        'meta_description' => 'Solve common problems',
                    ],
                    'ar' => [
                        'title' => 'استكشاف الأخطاء',
                        'slug' => 'troubleshooting-ar',
                        'content' => '<h1>استكشاف الأخطاء</h1><p>المشاكل الشائعة والحلول.</p>',
                        'meta_title' => 'دليل استكشاف الأخطاء',
                        'meta_description' => 'حل المشاكل الشائعة',
                    ],
                ],
                'created_at' => '2026-01-15T00:00:00.000000Z',
                'updated_at' => '2026-01-29T00:00:00.000000Z',
            ],
            // Child: Common Errors
            [
                'id' => 16,
                'parent_id' => 15,
                'order' => 1,
                'is_published' => false,
                'translations' => [
                    'en' => [
                        'title' => 'Common Errors',
                        'slug' => 'common-errors',
                        'content' => '<h2>Common Errors</h2><p>List of common errors and how to fix them.</p>',
                        'meta_title' => 'Common Errors',
                        'meta_description' => 'Fix common errors',
                    ],
                    'ar' => [
                        'title' => 'الأخطاء الشائعة',
                        'slug' => 'common-errors-ar',
                        'content' => '<h2>الأخطاء الشائعة</h2><p>قائمة بالأخطاء الشائعة وكيفية إصلاحها.</p>',
                        'meta_title' => 'الأخطاء الشائعة',
                        'meta_description' => 'إصلاح الأخطاء الشائعة',
                    ],
                ],
                'created_at' => '2026-01-16T00:00:00.000000Z',
                'updated_at' => '2026-01-30T00:00:00.000000Z',
            ],
        ];
    }

    /**
     * Build hierarchical tree structure
     */
    private function buildTree(array $docs, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($docs as $doc) {
            if ($doc['parent_id'] === $parentId) {
                $children = $this->buildTree($docs, $doc['id']);
                if ($children) {
                    $doc['children'] = $children;
                }
                $branch[] = $doc;
            }
        }

        // Sort by order
        usort($branch, fn($a, $b) => $a['order'] <=> $b['order']);

        return $branch;
    }
}

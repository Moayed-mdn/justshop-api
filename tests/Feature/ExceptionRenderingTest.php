<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExceptionRenderingTest extends TestCase
{
    public function test_validation_exception_returns_custom_json_format()
    {
        // 1. Arrange: Create a temporary route that fails validation
        Route::post('/test-validation', function () {
            request()->validate(['name' => 'required']);
        });

        // 2. Act: Make a request with missing data
        // Use 'Accept: application/json' to ensure Laravel treats it as an API request
        $response = $this->postJson('/test-validation', []);

        // 3. Assert: Check your custom structure
        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($message) => !empty($message))
            ->assertJsonStructure(['errors']);
    }

    public function test_generic_exception_returns_custom_json_format()
    {
        // 1. Arrange: Create a route that throws a generic error
        Route::get('/test-error', function () {
            throw new \Exception('Custom server error message');
        });

        // 2. Act
        $response = $this->getJson('/test-error');

        // 3. Assert: Verify the response matches your Throwable closure
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Custom server error message',
            ]);
    }

    public function test_http_exception_returns_correct_status_code()
    {
        // Trigger a 404 (NotFoundHttpException)
        $response = $this->getJson('/non-existent-route');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}

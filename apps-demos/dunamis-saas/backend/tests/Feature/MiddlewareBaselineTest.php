<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MiddlewareBaselineTest extends TestCase
{
    public function test_api_errors_follow_standard_contract_and_include_request_id(): void
    {
        $response = $this->getJson('/api/this-route-does-not-exist');

        $response->assertStatus(404)
            ->assertJsonStructure(['message', 'code', 'request_id']);

        $this->assertTrue($response->headers->has('X-Request-Id'));
    }

    public function test_tenant_context_prefers_headers_over_query_params(): void
    {
        Route::middleware('api')->get('/api/_test/tenant-context', function (Request $request) {
            return response()->json([
                'tenant_slug' => $request->attributes->get('tenant_slug'),
                'organization_id' => $request->attributes->get('organization_id'),
                'commerce_id' => $request->attributes->get('commerce_id'),
            ]);
        });

        $response = $this->getJson('/api/_test/tenant-context?tenant_slug=query-tenant&organization_id=22&commerce_id=33', [
            'X-Tenant-Slug' => 'header-tenant',
            'X-Organization-Id' => '77',
            'X-Commerce-Id' => '88',
        ]);

        $response->assertOk()
            ->assertJson([
                'tenant_slug' => 'header-tenant',
                'organization_id' => 77,
                'commerce_id' => 88,
            ]);
    }
}

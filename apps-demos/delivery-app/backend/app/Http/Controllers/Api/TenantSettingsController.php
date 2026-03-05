<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    private function normalizeHours($value): array
    {
        $defaults = [
            ['day' => 'mon', 'label' => 'Lunes', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
            ['day' => 'tue', 'label' => 'Martes', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
            ['day' => 'wed', 'label' => 'Miercoles', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
            ['day' => 'thu', 'label' => 'Jueves', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
            ['day' => 'fri', 'label' => 'Viernes', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
            ['day' => 'sat', 'label' => 'Sabado', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
            ['day' => 'sun', 'label' => 'Domingo', 'enabled' => true, 'open' => '09:00', 'close' => '23:00'],
        ];

        $rows = is_array($value) ? $value : [];
        $normalized = [];

        foreach ($defaults as $index => $default) {
            $row = is_array($rows[$index] ?? null) ? $rows[$index] : [];
            $normalized[] = [
                'day' => (string) ($row['day'] ?? $default['day']),
                'label' => (string) ($row['label'] ?? $default['label']),
                'enabled' => (bool) ($row['enabled'] ?? $default['enabled']),
                'open' => (string) ($row['open'] ?? $default['open']),
                'close' => (string) ($row['close'] ?? $default['close']),
            ];
        }

        return $normalized;
    }

    private function settingsPayload($tenant): array
    {
        return [
            'shipping_fee_ars' => (int) ($tenant->shipping_fee_ars ?? 0),
            'free_shipping_threshold_ars' => (int) ($tenant->free_shipping_threshold_ars ?? 0),
            'logo_url' => $tenant->logo_url,
            'brand_theme_key' => (string) ($tenant->brand_theme_key ?: 'green'),
            'brand_primary_color' => (string) ($tenant->brand_primary_color ?: '#10B981'),
            'business_phone' => (string) ($tenant->business_phone ?? ''),
            'business_address' => (string) ($tenant->business_address ?? ''),
            'business_hours' => $this->normalizeHours($tenant->business_hours_json),
        ];
    }

    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;
        if (!$tenant) {
            return response()->json(['message' => 'Tenant no disponible.'], 422);
        }

        return response()->json($this->settingsPayload($tenant));
    }

    public function update(Request $request): JsonResponse
    {
        $tenant = $request->user()?->tenant;
        if (!$tenant) {
            return response()->json(['message' => 'Tenant no disponible.'], 422);
        }

        $data = $request->validate([
            'shipping_fee_ars' => ['required', 'integer', 'min:0', 'max:9999999'],
            'free_shipping_threshold_ars' => ['required', 'integer', 'min:0', 'max:99999999'],
            'logo_url' => ['nullable', 'string', 'max:65535'],
            'brand_theme_key' => ['required', 'string', 'max:40'],
            'brand_primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'business_phone' => ['nullable', 'string', 'max:40'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_hours' => ['nullable', 'array', 'size:7'],
            'business_hours.*.day' => ['required_with:business_hours', 'string', 'max:10'],
            'business_hours.*.label' => ['required_with:business_hours', 'string', 'max:20'],
            'business_hours.*.enabled' => ['required_with:business_hours', 'boolean'],
            'business_hours.*.open' => ['required_with:business_hours', 'date_format:H:i'],
            'business_hours.*.close' => ['required_with:business_hours', 'date_format:H:i'],
        ]);

        $data['business_hours_json'] = $this->normalizeHours($data['business_hours'] ?? $tenant->business_hours_json);
        unset($data['business_hours']);
        $tenant->update($data);

        return response()->json($this->settingsPayload($tenant->fresh()));
    }
}

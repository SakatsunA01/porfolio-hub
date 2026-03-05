<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commerce;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminStoreSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $commerce = $this->resolveCommerce();

        return response()->json([
            'data' => $this->settingsPayload($commerce),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'brand_palette' => ['nullable', 'array', 'size:5'],
            'brand_palette.*' => ['required_with:brand_palette', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'manifesto_text' => ['nullable', 'string'],
            'philosophy_text' => ['nullable', 'string'],
            'contact_text' => ['nullable', 'string'],
            'team_text' => ['nullable', 'string'],
        ]);

        $commerce = $this->resolveCommerce();

        if ($request->hasFile('logo')) {
            if ($commerce->logo_path) {
                Storage::disk('public')->delete($commerce->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('store-branding', 'public');
        }

        $commerce->fill([
            'name' => $validated['name'] ?? $commerce->name,
            'logo_path' => $validated['logo_path'] ?? $commerce->logo_path,
            'brand_palette' => $validated['brand_palette'] ?? $commerce->brand_palette,
            'manifesto_text' => $validated['manifesto_text'] ?? $commerce->manifesto_text,
            'philosophy_text' => $validated['philosophy_text'] ?? $commerce->philosophy_text,
            'contact_text' => $validated['contact_text'] ?? $commerce->contact_text,
            'team_text' => $validated['team_text'] ?? $commerce->team_text,
        ])->save();

        return response()->json([
            'data' => $this->settingsPayload($commerce->fresh()),
        ]);
    }

    protected function resolveCommerce(): Commerce
    {
        return Commerce::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'Tienda Principal'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingsPayload(Commerce $commerce): array
    {
        return [
            'commerce_id' => $commerce->id,
            'name' => $commerce->name,
            'logo_url' => $commerce->logo_path ? Storage::url($commerce->logo_path) : null,
            'logo_path' => $commerce->logo_path,
            'brand_palette' => $commerce->brand_palette ?? [
                '#F7F5F0',
                '#ECE7DF',
                '#22221F',
                '#5A5A55',
                '#4F5D47',
            ],
            'manifesto_text' => $commerce->manifesto_text,
            'philosophy_text' => $commerce->philosophy_text,
            'contact_text' => $commerce->contact_text,
            'team_text' => $commerce->team_text,
        ];
    }
}

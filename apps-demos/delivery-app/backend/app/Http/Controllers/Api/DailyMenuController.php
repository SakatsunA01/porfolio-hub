<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\DailyMenu;
use App\Models\DailyMenuItem;
use App\Models\Product;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyMenuController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $limit = max(1, min(6, (int) $request->query('limit', 2)));
        $slot = $request->query('slot', $this->inferCurrentSlot(now()));
        $weekday = (int) now()->dayOfWeekIso; // 1..7

        $query = DailyMenu::query()
            ->with('items')
            ->where('is_active', true)
            ->where(function ($builder) use ($slot) {
                $builder->where('slot', 'all_day')->orWhere('slot', $slot);
            })
            ->where(function ($builder) use ($weekday) {
                $builder
                    ->whereNull('weekdays')
                    ->orWhereJsonContains('weekdays', $weekday);
            })
            ->where(function ($builder) {
                $builder->whereNull('active_from')->orWhere('active_from', '<=', now());
            })
            ->where(function ($builder) {
                $builder->whereNull('active_to')->orWhere('active_to', '>=', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->limit($limit);
        $this->scopeQueryByTenant($query, $tenantId);

        $menus = $query->get()->map(fn (DailyMenu $menu) => $this->mapMenu($menu))->values();

        return response()->json($menus);
    }

    public function index(): JsonResponse
    {
        $query = DailyMenu::query()
            ->with('items')
            ->latest('id');
        $this->scopeQueryByTenant($query, $this->resolveTenantId());

        return response()->json(
            $query->get()->map(fn (DailyMenu $menu) => $this->mapMenu($menu))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'slot' => ['sometimes', Rule::in(['all_day', 'lunch', 'dinner'])],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'min:1', 'max:7'],
            'active_from' => ['nullable', 'date'],
            'active_to' => ['nullable', 'date', 'after_or_equal:active_from'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);

        $menu = DailyMenu::query()->create([
            ...$data,
            'slot' => $data['slot'] ?? 'all_day',
            'is_active' => $data['is_active'] ?? true,
            'priority' => (int) ($data['priority'] ?? 0),
            'weekdays' => $this->normalizeWeekdays($data['weekdays'] ?? null),
        ]);

        return response()->json($this->mapMenu($menu->load('items')), 201);
    }

    public function update(Request $request, DailyMenu $dailyMenu): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($dailyMenu->tenant_id)) {
            return response()->json(['message' => 'Menu fuera del tenant actual.'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'slot' => ['sometimes', Rule::in(['all_day', 'lunch', 'dinner'])],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'min:1', 'max:7'],
            'active_from' => ['nullable', 'date'],
            'active_to' => ['nullable', 'date', 'after_or_equal:active_from'],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);

        if (array_key_exists('weekdays', $data)) {
            $data['weekdays'] = $this->normalizeWeekdays($data['weekdays']);
        }

        $dailyMenu->update($data);

        return response()->json($this->mapMenu($dailyMenu->fresh()->load('items')));
    }

    public function destroy(DailyMenu $dailyMenu): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($dailyMenu->tenant_id)) {
            return response()->json(['message' => 'Menu fuera del tenant actual.'], 403);
        }

        $dailyMenu->delete();

        return response()->json(['message' => 'Menu del dia eliminado.']);
    }

    public function upsertItem(Request $request, DailyMenu $dailyMenu): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($dailyMenu->tenant_id)) {
            return response()->json(['message' => 'Menu fuera del tenant actual.'], 403);
        }

        $data = $request->validate([
            'item_type' => ['required', Rule::in(['product', 'combo'])],
            'item_id' => ['required', 'integer', 'min:1'],
            'promo_price' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ]);

        if (!$this->itemBelongsToCurrentTenant($data['item_type'], (int) $data['item_id'])) {
            return response()->json(['message' => 'Item fuera del tenant actual o inexistente.'], 422);
        }

        DailyMenuItem::query()->updateOrCreate(
            [
                'daily_menu_id' => $dailyMenu->id,
                'item_type' => $data['item_type'],
                'item_id' => (int) $data['item_id'],
            ],
            [
                'promo_price' => array_key_exists('promo_price', $data) ? $data['promo_price'] : null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]
        );

        return response()->json($this->mapMenu($dailyMenu->fresh()->load('items')));
    }

    public function removeItem(DailyMenu $dailyMenu, DailyMenuItem $item): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($dailyMenu->tenant_id)) {
            return response()->json(['message' => 'Menu fuera del tenant actual.'], 403);
        }

        if ((int) $item->daily_menu_id !== (int) $dailyMenu->id) {
            return response()->json(['message' => 'Item no pertenece al menu indicado.'], 422);
        }

        $item->delete();

        return response()->json($this->mapMenu($dailyMenu->fresh()->load('items')));
    }

    private function mapMenu(DailyMenu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'description' => $menu->description,
            'image_url' => $menu->image_url,
            'is_active' => (bool) $menu->is_active,
            'slot' => $menu->slot,
            'weekdays' => $menu->weekdays ?? [],
            'active_from' => $menu->active_from?->toIso8601String(),
            'active_to' => $menu->active_to?->toIso8601String(),
            'priority' => (int) $menu->priority,
            'items' => $menu->items->map(fn (DailyMenuItem $item) => $this->mapItem($item))->values()->all(),
        ];
    }

    private function mapItem(DailyMenuItem $item): array
    {
        $entity = $this->resolveItemEntity($item->item_type, (int) $item->item_id);

        return [
            'id' => $item->id,
            'item_type' => $item->item_type,
            'item_id' => (int) $item->item_id,
            'promo_price' => $item->promo_price !== null ? (float) $item->promo_price : null,
            'sort_order' => (int) $item->sort_order,
            'name' => $entity?->name,
            'image_url' => $this->resolveItemImageUrl($item->item_type, $entity),
            'base_price' => $this->resolveItemBasePrice($item->item_type, $entity),
        ];
    }

    private function resolveItemEntity(string $type, int $id): Product|Combo|null
    {
        $query = match ($type) {
            'product' => Product::query(),
            'combo' => Combo::query(),
            default => null,
        };

        if (!$query) {
            return null;
        }

        $this->scopeQueryByTenant($query, $this->resolveTenantId());
        return $query->find($id);
    }

    private function resolveItemImageUrl(string $type, Product|Combo|null $entity): ?string
    {
        if (!$entity) {
            return null;
        }

        if ($type === 'product') {
            return $entity->getFirstMediaUrl('images') ?: $entity->image_url;
        }
        if ($type === 'combo') {
            return $entity->getFirstMediaUrl('images') ?: $entity->image_url;
        }

        return $entity->getFirstMediaUrl('images') ?: null;
    }

    private function resolveItemBasePrice(string $type, Product|Combo|null $entity): float
    {
        if (!$entity) {
            return 0;
        }

        if ($type === 'product' || $type === 'combo') {
            return (float) $entity->base_price;
        }

        return 0;
    }

    private function inferCurrentSlot(CarbonInterface $now): string
    {
        $hour = (int) $now->format('H');
        if ($hour >= 11 && $hour < 17) {
            return 'lunch';
        }
        if ($hour >= 17 || $hour < 3) {
            return 'dinner';
        }

        return 'all_day';
    }

    private function normalizeWeekdays(?array $weekdays): ?array
    {
        if ($weekdays === null) {
            return null;
        }

        $values = collect($weekdays)
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value >= 1 && $value <= 7)
            ->unique()
            ->values()
            ->all();

        return empty($values) ? null : $values;
    }

    private function itemBelongsToCurrentTenant(string $itemType, int $itemId): bool
    {
        $tenantId = $this->resolveTenantId();
        $query = match ($itemType) {
            'product' => Product::query(),
            'combo' => Combo::query(),
            default => null,
        };

        if (!$query) {
            return false;
        }

        $query->where('id', $itemId);
        $this->scopeQueryByTenant($query, $tenantId);

        return $query->exists();
    }

    private function resolveTenantId(): int
    {
        $userTenantId = (int) (request()->user()?->tenant_id ?? 0);
        if ($userTenantId > 0) {
            return $userTenantId;
        }

        $tenantSlug = trim((string) (request()->query('tenant_slug') ?: request()->header('X-Tenant-Slug') ?: ''));
        if ($tenantSlug !== '') {
            $tenantBySlug = Tenant::query()
                ->where('slug', $tenantSlug)
                ->where('is_active', true)
                ->first();

            if ($tenantBySlug) {
                return (int) $tenantBySlug->id;
            }
        }

        $tenant = Tenant::query()->where('is_active', true)->orderBy('id')->first();
        return (int) ($tenant?->id ?? 0);
    }
    private function scopeQueryByTenant($query, int $tenantId): void
    {
        if ($tenantId <= 0) {
            return;
        }

        $query->where(function ($tenantQuery) use ($tenantId) {
            $tenantQuery->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        });
    }

    private function belongsToCurrentTenant(?int $resourceTenantId): bool
    {
        $tenantId = $this->resolveTenantId();
        if ($tenantId <= 0) {
            return true;
        }

        return (int) ($resourceTenantId ?? $tenantId) === $tenantId;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $search = (string) $request->query('search', '');
        $perPage = max(1, min(50, (int) $request->query('per_page', 15)));

        $paginator = Client::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($paginator, ClientResource::collection($paginator->items()));
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $validated = $this->validatedPayload($request);

        $client = Client::query()->withoutGlobalScopes()->create([
            ...$validated,
            'organization_id' => $organizationId,
        ]);

        return $this->success(new ClientResource($client), status: 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        abort_if((int) $client->organization_id !== $organizationId, 404);

        $validated = $this->validatedPayload($request);
        $client->update($validated);

        return $this->success(new ClientResource($client->fresh()));
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        abort_if((int) $client->organization_id !== $organizationId, 404);

        $client->delete();

        return $this->success([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
    }
}

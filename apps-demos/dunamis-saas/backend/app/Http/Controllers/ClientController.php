<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $this->asegurarOrganizacion($request);
        $busqueda = $request->input('search');

        $clientes = Client::query()
            ->when($busqueda, function ($query, $valor) {
                $query->where(function ($sub) use ($valor) {
                    $sub->where('name', 'like', "%{$valor}%")
                        ->orWhere('tax_id', 'like', "%{$valor}%")
                        ->orWhere('email', 'like', "%{$valor}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clientes,
            'filters' => [
                'search' => $busqueda,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $organizacionId = $this->asegurarOrganizacion($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Client::create([
            'organization_id' => $organizacionId,
            'name' => $request->input('name'),
            'tax_id' => $request->input('tax_id'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
        ]);

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Client $client)
    {
        $this->asegurarOrganizacion($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $client->update([
            'name' => $request->input('name'),
            'tax_id' => $request->input('tax_id'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
        ]);

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $this->asegurarOrganizacion(request());

        $client->delete();

        return redirect()
            ->route('clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    protected function asegurarOrganizacion(Request $request): int
    {
        $organizacionId = $request->user()->organization_id;

        abort_if(!$organizacionId, 403, 'El usuario no tiene organización asignada.');

        return $organizacionId;
    }
}

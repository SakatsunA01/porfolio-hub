<?php

namespace App\Jobs;

use App\Models\ExchangeRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class FetchExchangeRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $response = Http::get('https://dolarapi.com/v1/dolares');

        if (!$response->successful()) {
            return;
        }

        $data = $response->json();

        if (!is_array($data)) {
            return;
        }

        $objetivos = ['oficial', 'blue'];
        $filtrados = collect($data)
            ->filter(fn ($item) => in_array(strtolower($item['nombre'] ?? ''), $objetivos))
            ->mapWithKeys(function ($item) {
                $clave = strtolower($item['nombre']);
                return [$clave => $item];
            });

        foreach ($objetivos as $objetivo) {
            $item = $filtrados[$objetivo] ?? null;
            if (!$item) {
                continue;
            }

            ExchangeRate::updateOrCreate(
                [
                    'moneda' => 'USD',
                    'nombre' => strtolower($item['nombre']),
                ],
                [
                    'compra' => $item['compra'] ?? 0,
                    'venta' => $item['venta'] ?? 0,
                    'fecha_actualizacion' => $item['fechaActualizacion'] ?? now(),
                ]
            );
        }
    }
}

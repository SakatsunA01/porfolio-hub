<?php

namespace App\Http\Controllers;

use App\Jobs\FetchExchangeRatesJob;
use Illuminate\Http\Request;

class ManualUpdateController extends Controller
{
    public function fetchDolar(Request $request)
    {
        FetchExchangeRatesJob::dispatch();

        return redirect()->route('dashboard')->with('success', 'Cotización del dólar en actualización.');
    }
}

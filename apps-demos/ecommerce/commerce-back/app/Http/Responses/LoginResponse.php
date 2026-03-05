<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request)
    {
        $redirectTo = auth()->user()?->admin_sn === true
            ? '/admin/dashboard'
            : '/account';

        if ($request->wantsJson()) {
            return response()->json([
                'two_factor' => false,
                'redirect' => $redirectTo,
            ]);
        }

        return redirect()->intended($redirectTo);
    }
}

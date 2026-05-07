<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $redirectTo = $user->hasRole('customer')
            ? route('public.my-orders')
            : route('dashboard');

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectTo);
    }
}

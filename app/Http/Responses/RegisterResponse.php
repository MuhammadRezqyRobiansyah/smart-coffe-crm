<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 201);
        }

        $user = auth()->user();
        if ($user) {
            $path = $user->role === 'admin' 
                ? route('admin.dashboard') 
                : route('member.dashboard');
            return redirect()->intended($path);
        }

        return redirect()->intended('/');
    }
}

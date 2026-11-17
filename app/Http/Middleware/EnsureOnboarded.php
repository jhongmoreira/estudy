<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Leva quem acabou de criar a conta para o onboarding (uma única vez). */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->onboarded_at === null && ! $request->routeIs('onboarding')) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}

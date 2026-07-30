<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        
        $userRoles = explode(',', (string) $user->Roles_IDRol);

      
        $hasRole = !empty(array_intersect($userRoles, $roles));

        if (! $hasRole) {
            return response()->json(['message' => 'sin permisos'], 403);
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppAdmin
{
    /**
     * Allow only application admins (platform-level role) to continue.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->can('manage-platform') ?? false,
            403,
            'Hanya admin aplikasi yang dapat mengakses area ini.',
        );

        return $next($request);
    }
}

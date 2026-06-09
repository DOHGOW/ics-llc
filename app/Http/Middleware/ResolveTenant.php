<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use App\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * OPTIONAL eager tenant priming (D-076). TenantContext resolves LAZILY at query time, so this
 * middleware is NOT required for user-mode resolution and is intentionally NOT registered globally
 * (global registration would prime before route-level Sanctum auth and resolve to null). Available
 * for DOMAIN-mode priming (host known pre-auth) when explicitly added to the web group.
 */
class ResolveTenant
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->set($this->resolver->resolve($request));

        return $next($request);
    }
}

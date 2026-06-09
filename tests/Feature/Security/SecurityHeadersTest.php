<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/** Security headers applied globally (T-9.1 / SECURITY_TEST_SPEC HD-/CSP-). */
class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/up'); // built-in health route; global middleware applies

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    public function test_csp_is_strict(): void
    {
        $csp = (string) $this->get('/up')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp); // D-048
    }
}

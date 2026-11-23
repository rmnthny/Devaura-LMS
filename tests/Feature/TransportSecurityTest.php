<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransportSecurityTest extends TestCase
{
    public function test_production_transport_policy_blocks_http_before_login(): void
    {
        config(['app.require_https' => true, 'session.driver' => 'array', 'session.secure' => true]);

        $this->post('http://localhost/api/v1/login')->assertForbidden()->assertHeaderMissing('Set-Cookie');
        $this->get('https://localhost/login')->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}

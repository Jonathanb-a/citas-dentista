<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * En Railway el enlace se firma con https, pero la petición llega del proxy
 * como http con X-Forwarded-Proto: https. La firma debe validar igual.
 */
class ProxyHttpsTest extends TestCase
{
    use RefreshDatabase;

    private function pacienteSinVerificar(): User
    {
        $user = User::factory()->unverified()->make(['apellidos' => 'Pérez', 'activo' => true]);
        $user->role = 'paciente';
        $user->save();

        return $user;
    }

    // Enlace de verificación firmado con https, pedido como http (como llega detrás del proxy)
    private function enlaceComoLlegaDelProxy(User $user): string
    {
        URL::forceScheme('https');

        $firmado = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        URL::forceScheme('http');

        return preg_replace('/^https:/', 'http:', $firmado);
    }

    public function test_el_enlace_firmado_valida_con_x_forwarded_proto_https(): void
    {
        $user = $this->pacienteSinVerificar();

        $this->actingAs($user)
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get($this->enlaceComoLlegaDelProxy($user))
            ->assertRedirect()
            ->assertSessionHas('success', '¡Tu correo fue verificado correctamente!');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_sin_el_header_del_proxy_la_firma_no_coincide(): void
    {
        $user = $this->pacienteSinVerificar();

        $this->actingAs($user)
            ->get($this->enlaceComoLlegaDelProxy($user))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }
}

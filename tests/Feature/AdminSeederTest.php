<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * El primer administrador sale de ADMIN_EMAIL y ADMIN_PASSWORD. En producción
 * no se permite sembrarlo sin ellos ni con una contraseña corta, y el seeder
 * ya no crea la asistente ni usa contraseñas fijas.
 */
class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    private function enProduccion(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    private function sembrar(): void
    {
        $this->app->make(AdminSeeder::class)->setContainer($this->app)->__invoke();
    }

    public function test_usa_el_correo_y_la_contrasena_de_las_variables(): void
    {
        config(['admin.email' => 'doctora@clinica.mx', 'admin.password' => 'una-clave-larga-123']);

        $this->sembrar();

        $admin = User::where('email', 'doctora@clinica.mx')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue((bool) $admin->activo);
        $this->assertTrue(Hash::check('una-clave-larga-123', $admin->password));
    }

    public function test_no_crea_la_asistente_ni_usa_las_contrasenas_fijas_de_antes(): void
    {
        config(['admin.email' => null, 'admin.password' => null]);

        $this->artisan('db:seed', ['--class' => AdminSeeder::class])
            ->expectsOutputToContain('Contraseña generada')
            ->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertFalse(User::where('role', 'asistente')->exists());
        $admin = User::where('email', 'admin@dentissa.com')->firstOrFail();
        $this->assertFalse(Hash::check('admin123', $admin->password));
    }

    public function test_en_produccion_falla_sin_correo(): void
    {
        $this->enProduccion();
        config(['admin.email' => null, 'admin.password' => 'una-clave-larga-123']);

        try {
            $this->sembrar();
            $this->fail('El seeder debió fallar sin ADMIN_EMAIL');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ADMIN_EMAIL', $e->getMessage());
        }

        $this->assertSame(0, User::count());
    }

    public function test_en_produccion_falla_con_un_correo_invalido(): void
    {
        $this->enProduccion();
        config(['admin.email' => 'no-es-correo', 'admin.password' => 'una-clave-larga-123']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_EMAIL');
        $this->sembrar();
    }

    public function test_en_produccion_falla_sin_contrasena(): void
    {
        $this->enProduccion();
        config(['admin.email' => 'doctora@clinica.mx', 'admin.password' => null]);

        try {
            $this->sembrar();
            $this->fail('El seeder debió fallar sin ADMIN_PASSWORD');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ADMIN_PASSWORD', $e->getMessage());
        }

        $this->assertSame(0, User::count());
    }

    public function test_en_produccion_falla_con_contrasena_de_11_caracteres(): void
    {
        $this->enProduccion();
        config(['admin.email' => 'doctora@clinica.mx', 'admin.password' => str_repeat('a', 11)]);

        try {
            $this->sembrar();
            $this->fail('El seeder debió fallar con una contraseña de 11 caracteres');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('menos de 12 caracteres', $e->getMessage());
        }

        $this->assertSame(0, User::count());
    }

    public function test_en_produccion_acepta_contrasena_de_12_caracteres(): void
    {
        $this->enProduccion();
        config(['admin.email' => 'doctora@clinica.mx', 'admin.password' => str_repeat('a', 12)]);

        $this->sembrar();

        $this->assertTrue(Hash::check(str_repeat('a', 12), User::where('email', 'doctora@clinica.mx')->value('password')));
    }

    public function test_si_el_correo_ya_existe_no_lo_modifica(): void
    {
        config(['admin.email' => 'doctora@clinica.mx', 'admin.password' => 'primera-clave-123']);
        $this->sembrar();

        config(['admin.password' => 'otra-clave-distinta-456']);
        $this->sembrar();

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('primera-clave-123', User::first()->password));
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Crea el primer administrador (la doctora) con ADMIN_EMAIL y ADMIN_PASSWORD
 * (config/admin.php). En producción los dos son obligatorios y la contraseña
 * debe tener al menos 12 caracteres; si no, falla sin crear nada.
 * Fuera de producción, sin variables usa admin@dentissa.com y una contraseña
 * aleatoria que muestra en la consola.
 * El resto del personal (asistente u otros admins) se da de alta en el módulo
 * Personal, que genera contraseñas temporales.
 */
class AdminSeeder extends Seeder
{
    public const LARGO_MINIMO_PASSWORD = 12;

    public function run(): void
    {
        [$email, $password, $generada] = $this->credenciales();

        if (User::where('email', $email)->exists()) {
            // No se toca: volver a correr el seeder no debe cambiar la contraseña de nadie
            $this->command?->warn("Ya existe un usuario con el correo {$email}: no se modificó.");
            return;
        }

        // Admin — Doctora
        $admin = new User();
        $admin->name      = 'Melissa';
        $admin->apellidos = 'López N.';
        $admin->email     = $email;
        $admin->telefono  = '9991231234';
        $admin->password  = Hash::make($password);
        $admin->role      = 'admin';
        $admin->activo    = true;
        $admin->save();

        $this->command?->info("Administrador creado: {$email}");

        if ($generada) {
            $this->command?->warn("Contraseña generada (no se volverá a mostrar): {$password}");
        }
    }

    /** @return array{0: string, 1: string, 2: bool} correo, contraseña y si la contraseña se generó */
    private function credenciales(): array
    {
        $email = trim((string) config('admin.email'));
        $password = (string) config('admin.password');

        if (app()->environment('production')) {
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException(
                    'AdminSeeder: falta ADMIN_EMAIL o no es un correo válido. '
                    . 'Defínelo en las variables de entorno antes de correr el seeder.'
                );
            }

            if (mb_strlen($password) < self::LARGO_MINIMO_PASSWORD) {
                throw new RuntimeException(
                    'AdminSeeder: falta ADMIN_PASSWORD o tiene menos de '
                    . self::LARGO_MINIMO_PASSWORD . ' caracteres. '
                    . 'Defínela en las variables de entorno antes de correr el seeder.'
                );
            }

            return [$email, $password, false];
        }

        $generada = $password === '';

        return [
            $email !== '' ? $email : 'admin@dentissa.com',
            $generada ? Str::password(16) : $password,
            $generada,
        ];
    }
}

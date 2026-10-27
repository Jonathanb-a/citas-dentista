<?php

/**
 * Primer administrador (la doctora), lo crea database/seeders/AdminSeeder.
 * Se leen aquí y no con env() en el seeder: Railpack cachea la configuración
 * (php artisan optimize) y con la caché env() fuera de config/ no lee el .env.
 * En producción el seeder exige los dos valores y una contraseña de al menos
 * 12 caracteres.
 */
return [
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un campo de la landing que la doctora personalizó. Si no hay fila para una
 * clave, la landing muestra el valor original definido en config/landing.php.
 */
class LandingContenido extends Model
{
    protected $table = 'landing_contenidos';

    protected $fillable = ['clave', 'valor'];
}

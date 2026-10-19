<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un elemento de una lista administrable de la landing (ver LandingColeccionService). */
class LandingItem extends Model
{
    protected $table = 'landing_items';

    protected $fillable = ['coleccion', 'orden', 'original', 'imagen', 'datos'];

    protected $casts = [
        'datos' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialCita extends Model
{
    use HasFactory;

    protected $fillable = [
        'cita_id',
        'user_id',
        'accion',
        'descripcion',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
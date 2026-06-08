<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'apellidos',
        'email',
        'telefono',
        'fecha_nacimiento',
        'sexo',
        'role',
        'activo',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    // Relaciones
    public function citas()
    {
        return $this->hasMany(Cita::class, 'paciente_id');
    }

    public function expediente()
    {
        return $this->hasOne(Expediente::class, 'paciente_id');
    }

    // Helpers de roles
    public function esAdmin()
    {
        return $this->role === 'admin';
    }

    public function esAsistente()
    {
        return $this->role === 'asistente';
    }

    public function esPaciente()
    {
        return $this->role === 'paciente';
    }
}
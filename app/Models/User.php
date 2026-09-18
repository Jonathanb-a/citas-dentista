<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'apellidos',
        'email',
        'telefono',
        'fecha_nacimiento',
        'sexo',
        'password',
        'activo',
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
            'password_restablecida_en' => 'datetime',
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
    /**
     * Envía el correo de verificación sin arriesgar la operación del usuario:
     * si el correo falla, lo registra en el log y devuelve false en vez de
     * lanzar la excepción. Un fallo de correo nunca debe tumbar un registro
     * ni un cambio de datos.
     */
    public function enviarVerificacion(): bool
    {
        try {
            $this->sendEmailVerificationNotification();

            return true;
        } catch (\Throwable $error) {
            Log::error('No se pudo enviar el correo de verificación.', [
                'usuario_id' => $this->id,
                'error' => $error->getMessage(),
            ]);

            return false;
        }
    }

    // Notas de evolución del paciente, de la más reciente a la más antigua
    public function notasEvolucion()
    {
        return $this->hasMany(NotaEvolucion::class, 'paciente_id');
    }

    // Quién restableció por última vez la contraseña de esta cuenta
    public function passwordRestablecidaPor()
    {
        return $this->belongsTo(User::class, 'password_restablecida_por');
    }

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
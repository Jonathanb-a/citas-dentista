<?php

namespace App\Policies;

use App\Models\Testimonio;
use App\Models\User;
use App\Services\TestimonioService;

/** Un paciente solo toca el suyo; crear exige cumplir la elegibilidad. La moderación es solo de la doctora (rutas admin) */
class TestimonioPolicy
{
    public function __construct(private TestimonioService $servicio) {}

    public function create(User $usuario): bool
    {
        return $this->servicio->puedeOpinar($usuario) && !$usuario->testimonio()->exists();
    }

    public function update(User $usuario, Testimonio $testimonio): bool
    {
        return $usuario->role === 'paciente' && $testimonio->paciente_id === $usuario->id;
    }

    public function delete(User $usuario, Testimonio $testimonio): bool
    {
        return $this->update($usuario, $testimonio);
    }
}

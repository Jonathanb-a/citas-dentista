<?php

namespace App\Http\Requests;

use App\Services\TestimonioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validación estricta de crear/editar un testimonio. El texto se limpia ANTES de validar su longitud */
class TestimonioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la política se revisa en el controlador
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'texto' => app(TestimonioService::class)->limpiarTexto($this->input('texto')),
        ]);
    }

    public function rules(): array
    {
        return [
            'calificacion' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'texto' => ['required', 'string', 'min:' . (int) config('testimonios.texto_min'), 'max:' . (int) config('testimonios.texto_max')],
            'autorizo' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'calificacion.required' => 'Elige de 1 a 5 estrellas.',
            'calificacion.integer' => 'Elige de 1 a 5 estrellas.',
            'calificacion.in' => 'Elige de 1 a 5 estrellas.',
            'texto.required' => 'Escribe tu opinión.',
            'texto.min' => 'Tu opinión debe tener al menos :min caracteres (sin contar enlaces ni símbolos).',
            'texto.max' => 'Tu opinión puede tener como máximo :max caracteres.',
            'autorizo.accepted' => 'Debes autorizar que se muestre tu nombre y tu opinión en la página.',
        ];
    }
}

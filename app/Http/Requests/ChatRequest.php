<?php

namespace App\Http\Requests;

use App\Services\Chat\FirmaHistorial;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Validación estricta de POST /chat. Los errores nunca devuelven lo que escribió el
 * visitante, solo un texto listo para mostrar.
 *
 * Contrato del historial (lo respeta el widget): turnos completos que alternan
 * user, assistant, user, assistant…; cada mensaje del asistente lleva la firma que
 * recibió con su respuesta. Un mensaje que no obtuvo respuesta no se incluye.
 */
class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $historial = $this->input('historial');

        if (is_array($historial)) {
            foreach ($historial as $i => $turno) {
                if (is_array($turno) && array_key_exists('texto', $turno)) {
                    $historial[$i]['texto'] = $this->limpiar($turno['texto']);
                }
            }
        }

        // También null: un texto con UTF-8 inválido debe reemplazar al original, no quedarse
        $this->merge([
            'mensaje' => $this->limpiar($this->input('mensaje')),
            'historial' => $historial,
        ]);
    }

    public function rules(): array
    {
        $maxCaracteres = (int) config('chat.max_caracteres');

        return [
            'mensaje' => ['required', 'string', 'max:' . $maxCaracteres],
            'historial' => ['nullable', 'array', 'max:' . (int) config('chat.max_mensajes')],
            // array:… rechaza cualquier clave que no sea de este conjunto
            'historial.*' => ['array:rol,texto,firma'],
            'historial.*.rol' => ['required', 'string', Rule::in(['user', 'assistant'])],
            'historial.*.texto' => ['required', 'string', 'max:' . $maxCaracteres],
            'historial.*.firma' => ['required_if:historial.*.rol,assistant', 'prohibited_if:historial.*.rol,user', 'nullable', 'string', 'size:64'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Solo estos campos de primer nivel (_token por si el formulario lo manda en el cuerpo)
            if (array_diff(array_keys($this->all()), ['mensaje', 'historial', '_token']) !== []) {
                $validator->errors()->add('historial', 'campos desconocidos');

                return;
            }

            $firmas = app(FirmaHistorial::class);

            foreach ($turnos = array_values($this->turnos()) as $i => $turno) {
                $esperado = $i % 2 === 0 ? 'user' : 'assistant';

                if ($turno['rol'] !== $esperado) {
                    $validator->errors()->add('historial', 'los turnos deben alternar');

                    return;
                }

                if ($turno['rol'] === 'assistant' && !$firmas->valida($turno['texto'], $turno['firma'] ?? null)) {
                    $validator->errors()->add('historial', 'firma inválida');

                    return;
                }
            }

            // Termina en respuesta del asistente: el mensaje nuevo es el siguiente turno del usuario
            if (count($turnos) % 2 !== 0) {
                $validator->errors()->add('historial', 'turno incompleto');
            }
        });
    }

    public function mensaje(): string
    {
        return (string) $this->validated('mensaje');
    }

    /** @return array<int, array{rol: string, texto: string}> */
    public function historial(): array
    {
        return array_map(
            fn (array $turno) => ['rol' => $turno['rol'], 'texto' => $turno['texto']],
            array_values($this->turnos()),
        );
    }

    // Ya limpio (prepareForValidation) y con la estructura validada; se lee de input() porque
    // validated() no se puede llamar mientras corre la propia validación (withValidator)
    private function turnos(): array
    {
        return (array) ($this->input('historial') ?? []);
    }

    /**
     * Texto limpio: UTF-8 válido, saltos de línea normalizados y sin caracteres de control ni de
     * formato (invisibles, bidireccionales, "tags" Unicode): sirven para ocultar instrucciones.
     */
    private function limpiar(mixed $valor): mixed
    {
        if (!is_string($valor)) {
            return $valor;
        }

        if (!mb_check_encoding($valor, 'UTF-8')) {
            return null;
        }

        $valor = preg_replace('/\r\n?/', "\n", $valor);
        $valor = preg_replace('/(?!\n)[\p{Cc}\p{Cf}\p{Co}\p{Cn}]/u', '', (string) $valor);

        return trim((string) $valor);
    }

    protected function failedValidation(Validator $validator): void
    {
        $errores = $validator->errors();
        $texto = $errores->has('mensaje')
            ? config('chat.mensajes.mensaje_invalido')
            : config('chat.mensajes.historial_invalido');

        throw new HttpResponseException(response()->json(['ok' => false, 'respuesta' => $texto], 422));
    }
}

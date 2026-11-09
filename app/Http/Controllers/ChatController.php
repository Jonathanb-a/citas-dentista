<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;

/**
 * POST /chat: público (CSRF, límite por IP y tope global), con las herramientas de citas.
 * Respuestas: 200 {ok, respuesta, firma?, pendiente?} (ok:false si la IA falló o se acabó el
 * tope diario; `pendiente` es la propuesta de agendado que el navegador devuelve en la
 * petición siguiente), 422 entrada inválida, 429 límite por IP, 419 CSRF.
 */
class ChatController extends Controller
{
    /**
     * GET /chat/token: un token CSRF nuevo para el widget. Con la sesión caducada (419) el widget
     * lo pide y reintenta sin perder la conversación: el historial firmado no depende de la sesión.
     * La política del mismo origen impide que otro sitio lea esta respuesta.
     */
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()])->header('Cache-Control', 'no-store');
    }

    public function __invoke(ChatRequest $request, ChatService $chat): JsonResponse
    {
        return response()
            ->json($chat->responder($request->mensaje(), $request->historial(), $request->pendiente(), (string) $request->ip()))
            ->header('Cache-Control', 'no-store');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordTemporalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PersonalController extends Controller
{
    // Roles que administra este módulo: los pacientes tienen el suyo
    private const ROLES = ['admin', 'asistente'];

    private function personal()
    {
        return User::whereIn('role', self::ROLES);
    }

    // ¿Queda otra cuenta de doctora activa además de esta?
    private function esUltimoAdminActivo(User $usuario): bool
    {
        if ($usuario->role !== 'admin' || !$usuario->activo) {
            return false;
        }

        return !User::where('role', 'admin')
            ->where('activo', true)
            ->where('id', '!=', $usuario->id)
            ->exists();
    }

    // Listado de quienes operan el sistema
    public function index(Request $request)
    {
        $query = $this->personal();

        if ($request->filled('buscar')) {
            foreach (preg_split('/\s+/', trim($request->buscar)) as $palabra) {
                $buscar = '%' . $palabra . '%';

                $query->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', $buscar)
                      ->orWhere('apellidos', 'like', $buscar)
                      ->orWhere('email', 'like', $buscar)
                      ->orWhere('telefono', 'like', $buscar);
                });
            }
        }

        $personal = $query->orderBy('role')
            ->orderBy('name')
            ->orderBy('apellidos')
            ->paginate(15)
            ->withQueryString();

        return view('admin.personal.index', compact('personal'));
    }

    // Alta de personal con contraseña temporal
    public function store(Request $request, PasswordTemporalService $passwordTemporal)
    {
        $request->validateWithBag('crear', $this->reglas(), $this->mensajes());

        $password = $passwordTemporal->generar();

        $usuario = new User();
        $usuario->name = $request->name;
        $usuario->apellidos = $request->apellidos;
        $usuario->email = $request->email;
        $usuario->telefono = $request->telefono;
        $usuario->sexo = $request->sexo;
        $usuario->password = Hash::make($password);
        $usuario->role = $request->role;   // asignado explícitamente, no por fillable
        $usuario->activo = true;
        $usuario->save();

        return redirect()->route('admin.personal.index')
            ->with('success', 'Cuenta creada correctamente.')
            ->with('password_temporal', $passwordTemporal->panel($usuario, $password));
    }

    // Editar datos y rol
    public function update(Request $request, $id)
    {
        $usuario = $this->personal()->findOrFail($id);

        $request->validateWithBag('editar_' . $usuario->id, $this->reglas($usuario), $this->mensajes());

        // El sistema no puede quedarse sin ninguna doctora activa.
        // Va antes que la regla de "tu propio rol" porque explica mejor el motivo.
        if ($request->role !== 'admin' && $this->esUltimoAdminActivo($usuario)) {
            return back()->with('error', 'Debe quedar al menos una cuenta de doctora activa.');
        }

        // Nadie puede quitarse a sí misma el rol de doctora
        if ($usuario->id === auth()->id() && $request->role !== $usuario->role) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        $usuario->update([
            'name' => $request->name,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'sexo' => $request->sexo,
        ]);

        $usuario->role = $request->role;
        $usuario->save();

        return redirect()->route('admin.personal.index')
            ->with('success', 'Datos actualizados correctamente.');
    }

    // Activar / desactivar
    public function toggle($id)
    {
        $usuario = $this->personal()->findOrFail($id);

        // Primero la regla que explica mejor el motivo
        if ($usuario->activo && $this->esUltimoAdminActivo($usuario)) {
            return back()->with('error', 'Debe quedar al menos una cuenta de doctora activa.');
        }

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $usuario->update(['activo' => !$usuario->activo]);

        $mensaje = $usuario->activo
            ? 'Cuenta activada correctamente.'
            : 'Cuenta desactivada. Ya no podrá iniciar sesión.';

        return back()->with('success', $mensaje);
    }

    // Contraseña temporal nueva
    public function restablecerPassword($id, PasswordTemporalService $passwordTemporal)
    {
        $usuario = $this->personal()->findOrFail($id);

        $panel = $passwordTemporal->restablecer($usuario, auth()->id());

        $mensaje = $usuario->id === auth()->id()
            ? 'Tu contraseña fue restablecida. Tendrás que iniciar sesión otra vez con la nueva.'
            : 'Contraseña restablecida. La anterior ya no funciona.';

        return back()->with('success', $mensaje)->with('password_temporal', $panel);
    }

    private function reglas(?User $usuario = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'telefono' => ['nullable', 'string', 'max:20', Rule::unique('users', 'telefono')->ignore($usuario?->id)],
            'sexo' => 'nullable|in:masculino,femenino',
            'role' => ['required', Rule::in(self::ROLES)],
        ];
    }

    private function mensajes(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Ya existe otro usuario con ese correo.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'telefono.unique' => 'Ya existe otro usuario con ese teléfono.',
            'sexo.in' => 'Selecciona una opción válida de sexo.',
            'role.required' => 'Selecciona el rol de la cuenta.',
            'role.in' => 'Selecciona un rol válido.',
        ];
    }
}

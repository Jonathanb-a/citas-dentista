# Dentissa — Sistema de citas odontológicas

## Stack
Laravel 12 · Tailwind CSS v4 · Alpine.js · Vite · MySQL
Entorno local: Laragon. Despliegue: Railway.

## Arquitectura
- MVC + capa de servicios (app/Services/)
- DisponibilidadService: calcula espacios libres de cita
- CitaManualService: crear y reagendar citas (lo usan admin y asistente)
- PasswordTemporalService: genera y asigna contraseñas temporales (alta de
  paciente en cita manual, restablecer contraseña y módulo Personal)
- Middleware CheckRole para los roles: admin, asistente, paciente

## Expediente clínico
- Fase 1 (lista): historia clínica NOM-013-SSA2 en la tabla expedientes
- Fase 2 (lista): odontograma en la tabla `odontograma`, un registro por
  diente marcado; sin registro = diente sano. Estados y colores viven en
  Odontograma::ESTADOS (única fuente de verdad de validación, leyenda,
  resumen y modal)
- Fase 3 (pendiente): notas de evolución y dentición infantil
  - Los números FDI temporales (51-55, 61-65, 71-75, 81-85) ya caben en
    `odontograma.numero_diente` sin migración: solo hay que ampliar
    Odontograma::numerosValidos() y dibujar 20 dientes
  - El caso a resolver es la **dentición mixta**: entre los 6 y los 12 años
    el mismo paciente tiene dientes permanentes y temporales a la vez, así
    que el odontograma tendrá que mostrar ambas denticiones juntas, no un
    interruptor que alterne entre una y otra
  - La clínica sí atiende niños (servicio "Ortodoncia maxilar")

## Qué ve el paciente de su expediente
En "Mi perfil" el paciente consulta su expediente en solo lectura, pero
NO se le muestran `notas_doctora` ni `observaciones`: son el espacio de
trabajo interno de la doctora y no se puede garantizar qué escriba ahí.
Lo que el paciente necesita saber va en `diagnostico`, `plan_tratamiento`
y las `indicaciones` de cada nota de evolución.
Si algún día hace falta un texto dirigido al paciente, se crea un campo
nuevo con ese nombre explícito en vez de destapar estos dos.

## Pendiente antes de entregar: datos del consultorio
La impresión del expediente lee config/consultorio.php, que toma sus valores
del .env. Hoy son provisionales. Hay que confirmarlos con la doctora y
ponerlos en el .env local y en las variables de Railway:

    CONSULTORIO_NOMBRE      (hoy: Melissa López N.)
    CONSULTORIO_SUBTITULO   (hoy: Cirujano Dentista)
    CONSULTORIO_CEDULA      (vacío: no se imprime)
    CONSULTORIO_DIRECCION   (hoy: Lázaro Cárdenas, Michoacán — falta calle y número)
    CONSULTORIO_TELEFONO    (vacío: no se imprime)
    CONSULTORIO_CORREO      (vacío: no se imprime)

## Convenciones
- Todo en español (variables, mensajes, comentarios)
- Colores: rosa #d75078, rosa claro #fde7ed, fondo #FFF6F6, verde #15626C
- Fuente Montserrat
- Tarjetas: rounded-2xl shadow-sm p-6
- Botones primarios: bg-[#d75078] hover:bg-[#f285a2] rounded-xl
- Las tablas deben tener versión móvil en tarjetas (hidden md:block / md:hidden)
- Cada acción sobre una cita se registra en HistorialCita
- 'role' NO está en el $fillable de User: se asigna explícitamente

## Comandos
php artisan serve
npm run dev
php artisan migrate:fresh --seed

## Usuarios de prueba
admin@dentissa.com / admin123
asistente@dentissa.com / asistente123
paciente@test.com / paciente123
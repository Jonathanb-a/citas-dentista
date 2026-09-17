# Dentissa — Sistema de citas odontológicas

## Stack
Laravel 12 · Tailwind CSS v4 · Alpine.js · Vite · MySQL
Entorno local: Laragon. Despliegue: Railway.

## Arquitectura
- MVC + capa de servicios (app/Services/)
- DisponibilidadService: calcula espacios libres de cita
- CitaManualService: crear y reagendar citas (lo usan admin y asistente)
- Middleware CheckRole para los roles: admin, asistente, paciente

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
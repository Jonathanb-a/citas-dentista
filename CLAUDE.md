# Dentissa — Sistema de citas odontológicas

## Stack
Laravel 12 · Tailwind CSS v4 · Alpine.js · Vite · MySQL
Entorno local: Laragon. Despliegue: Railway.

## Arquitectura
- MVC + capa de servicios (app/Services/)
- DisponibilidadService: calcula espacios libres de cita
  (respeta la tabla `bloqueos`: día completo, rango de días u horas de un
  día; todo flujo de agenda pasa por aquí, no validar disponibilidad aparte)
- BloqueoService: citas en conflicto con un bloqueo, guardado (conservar o
  cancelar las citas) y marcado "En periodo bloqueado" en las listas de citas
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

## ⚠️ Pendiente antes de entregar

### 1. Dominio verificado en Resend (decide si el correo sirve para pacientes)
Hoy el correo sale por Resend SIN dominio propio. Eso significa:
- El remitente solo puede ser `onboarding@resend.dev`
- Resend **solo entrega al correo del dueño de la cuenta de Resend**
- **Ningún paciente real recibe** la verificación de correo ni el enlace
  de "Olvidé mi contraseña". Resend rechaza esos envíos.

El sistema no se rompe por eso (ver "Correo" más abajo): el paciente se
registra y entra igual, y si olvida su contraseña, el mensaje lo manda a
la clínica, donde la doctora o la asistente usan "Restablecer contraseña".

Para que el correo funcione con pacientes: verificar en Resend un dominio
(o subdominio) de la clínica, agregar sus registros DNS, y cambiar
`MAIL_FROM_ADDRESS` a una dirección de ese dominio (ej. citas@dominio.mx).

### 2. Datos del consultorio
La impresión del expediente lee config/consultorio.php, que toma sus valores
del .env. Hoy son provisionales. Hay que confirmarlos con la doctora y
ponerlos en el .env local y en las variables de Railway:

    CONSULTORIO_NOMBRE      (hoy: Melissa López N.)
    CONSULTORIO_SUBTITULO   (hoy: Cirujano Dentista)
    CONSULTORIO_CEDULA      (vacío: no se imprime)
    CONSULTORIO_DIRECCION   (hoy: Lázaro Cárdenas, Michoacán — falta calle y número)
    CONSULTORIO_TELEFONO    (vacío: no se imprime)
    CONSULTORIO_CORREO      (vacío: no se imprime)

## Correo
- Se envía con Resend (paquete resend/resend-laravel) por su API HTTPS,
  porque Railway bloquea el SMTP saliente
- Variables en Railway:

      MAIL_MAILER=resend
      RESEND_API_KEY=re_...            (del panel de Resend)
      MAIL_FROM_ADDRESS=onboarding@resend.dev   (hasta tener dominio verificado)
      MAIL_FROM_NAME=Dentissa

- `RESEND_ROUTES` está apagado por defecto en config/resend.php: el paquete
  expone POST /resend/webhook y el proyecto no usa webhooks
- **Un fallo de correo nunca debe tumbar una operación del usuario.** Los
  cuatro puntos de envío están protegidos: registro, reenviar verificación,
  cambio de correo en Mi perfil y "Olvidé mi contraseña". Para la
  verificación se usa `User::enviarVerificacion()`, que registra el error
  en el log y devuelve false en vez de lanzar la excepción
- El mailer SMTP tiene timeout de 5 s: el error "Maximum execution time
  of 30 seconds exceeded" es un error fatal de PHP que ningún try/catch
  atrapa, así que la protección real es no quedarse colgado

## Avisos por Telegram (solo a la clínica)
- `TelegramService` avisa a la doctora y la asistente por la Bot API de
  Telegram (HTTPS). Nunca escribe a los pacientes
- Eventos: paciente agenda, paciente cancela, cita reagendada (admin o
  asistente) y cita liberada por no confirmar. Mensaje corto: evento,
  paciente, servicio, fecha y hora. Sin teléfono ni datos clínicos
- Variables (.env local y Railway):

      TELEGRAM_BOT_TOKEN=123456:ABC...   (de @BotFather)
      TELEGRAM_CHAT_IDS=111111,222222    (separados por coma)

  Sin token o sin chats no hace nada. Cada persona debe abrir el bot y
  pulsar Iniciar, o Telegram rechaza el envío; el chat ID se obtiene
  escribiéndole al bot y abriendo `https://api.telegram.org/bot<TOKEN>/getUpdates`
  (un grupo con el bot tiene ID negativo)
- Un fallo de Telegram nunca debe afectar una cita: timeout de 5 s, todo
  en try/catch con log, y en peticiones web se envía después de responder
  (`enSegundoPlano`). El token se oculta en los logs porque va en la URL
- `phpunit.xml` deja las variables vacías para que las pruebas no manden
  mensajes reales aunque el .env local tenga token
- El aviso de "liberada" depende de que el scheduler corra en Railway
  (`php artisan schedule:work` o un cron que ejecute `schedule:run`)

## Pendiente técnico
- `citas.horario_id` tiene `onDelete cascade`: eliminar un horario semanal
  borra sus citas. Cambiarlo a restrict/null o impedir borrar horarios con
  citas

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
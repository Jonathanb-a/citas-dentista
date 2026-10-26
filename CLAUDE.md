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

## Anuncios y promociones de la landing
- Los edita la doctora en el admin, entrada "Landing Page" del menú
  (`Admin\AnuncioController`, modelo `Anuncio`). La landing la sirve
  `LandingController`
- **Regla de oro: sin anuncios vigentes la landing es idéntica a la
  original.** `tests/Fixtures/landing-sin-anuncios.html` es el HTML de
  referencia y una prueba lo compara. Si algún día cambias la landing a
  propósito, regenera ese archivo; si la prueba falla sin que lo
  hayas hecho, algo se coló en la landing
- **Tratamientos (`#tratamientos`)**: `partials/landing-tratamientos`, tras
  el hero (sustituyó a la cinta rosa fija, que anunciaba servicios que no
  estaban en el catálogo). `LandingController` pasa `$tratamientos`: las
  especialidades activas con sus servicios activos (los mismos que el
  paciente puede agendar), con nombre, descripción y duración; sin precio,
  porque el sistema no lo maneja. Se editan en el panel (Especialidades y
  Servicios). Sin servicios activos, o si la consulta falla, la sección no
  se muestra (no 500)
- Avisos y promociones vigentes salen juntos en un carrusel
  (`partials/landing-carrusel`, sección `#novedades`, máx. 8 slides) tras
  `#tratamientos` y antes de `#nosotros`, en el orden del
  panel (`anuncios.orden`, se cambia con ▲▼). Alpine: `resources/js/carrusel-anuncios.js`
  (avance cada 6 s, pausa con mouse/foco/toque, botón Pausar/Reanudar,
  flechas en pantalla y del teclado, puntos, deslizar; controles de 44 px.
  Con "reducir movimiento" no avanza solo ni muestra el botón de pausa; con
  una sola slide no hay controles ni Alpine)
- Vigente = activo y dentro de las fechas (extremos incluidos, ambas
  opcionales). "Hoy" se calcula en `config('consultorio.zona_horaria')`
  (America/Mexico_City), porque la app corre en UTC
- Todo texto es plano y se escapa. Enlaces: solo https/http, ruta `/…`,
  ancla `#…` o `tel:`. El color es de una paleta fija (rosa, verde, gris)
  con clases completas en `Anuncio::COLORES`
- Si la consulta de anuncios falla, la landing se ve sin anuncios (no 500)

## Contenido editable de la landing (imágenes y textos)
- Pestañas de "Landing Page" en el admin: Anuncios · Imágenes y textos ·
  Contacto (`Admin\LandingContenidoController`). La landing la sigue
  sirviendo `LandingController`, que le pasa a la vista `$landing`
  (`LandingContenidoService`)
- **El catálogo vive en `config/landing.php`**: cada campo con su valor
  ORIGINAL, etiqueta, tipo (texto, titulo, parrafo, imagen, telefono, red,
  mapa), límites y tamaño ideal. La tabla `landing_contenidos` (clave y
  valor) guarda SOLO lo que la doctora personalizó; sin fila se muestra el
  original. Para hacer editable algo nuevo no hay migración: agregar el
  campo al catálogo y leerlo en la vista con `$landing->texto('seccion.campo')`
  o `->imagen('seccion.campo')`. Una prueba verifica que cada campo del
  catálogo se lee de verdad en la landing
- Un campo vacío o igual al original no guarda fila ("Restaurar" borra la
  fila y la imagen subida; los archivos originales de `public/` no se
  tocan). Las imágenes pasan por `ImagenService` (carpeta `landing`)
- Los nombres de campo del formulario cambian el punto por `__`
  (`hero__titulo`), porque Laravel lee los puntos como anidación
- Teléfono: 10 dígitos (se aceptan espacios, guiones y lada 52); de ahí
  salen `tel:+52…` y `wa.me/52…`. Redes: https y solo su dominio
  (instagram.com, facebook.com…). Mapa: solo `https://www.google.com/maps/embed…`
  (se acepta pegar el `<iframe>` completo y se extrae el `src`). Todo texto
  plano, escapado
- Si la lectura de lo personalizado falla, la landing se ve con los
  originales (no 500). La prueba contra `tests/Fixtures/landing-sin-anuncios.html`
  sigue siendo la garantía de que sin cambios la landing es idéntica, y
  otra prueba confirma que personalizar y restaurar cada sección la deja
  idéntica de nuevo
- Esas comparaciones usan `assertTrue(a === b)` y no `assertSame`: si
  fallaran, PHPUnit tarda minutos en calcular el diff de dos páginas enteras
- **Faltan en el repositorio 8 imágenes originales de la landing**
  (`mision.png`, `vision.png`, `galeria-1..5.png` y `certificado.png`;
  solo existen `image 1.png` y el logo): hoy se ven
  rotas y el panel muestra "La imagen original no está disponible". Hay
  que ponerlas en `public/img/` con esos nombres, o subirlas desde el panel
- Los datos de contacto originales son de relleno (teléfono 999 123 1234,
  Mérida, Yucatán, redes con `#`); la clínica está en Lázaro Cárdenas,
  Michoacán. Se editan en la pestaña Contacto
- **Galería (`#galeria`)**: pestaña "Galería" del panel
  (`Admin\LandingGaleriaController`, `LandingColeccionService`, tabla
  `landing_items`). Agregar, quitar, reordenar (▲▼), editar título y
  subtítulo, cambiar la imagen y "Restaurar la galería original". Máximo 12
  fotos y mínimo 1 (así el menú y el pie no apuntan a una sección vacía).
  El título de la sección (`galeria.titulo`) es un campo normal del catálogo
- Las listas se definen en `config/landing.php`, clave `colecciones`, con sus
  elementos ORIGINALES. Mientras la lista no tenga filas se muestran los
  originales (la landing es idéntica); el primer cambio guarda los 5
  originales como filas (sin imagen propia, `original = galeria-N`) y
  desde ahí manda la base de datos. Mientras no se ha tocado, un elemento se
  identifica en las URLs del panel por la clave del original (`galeria-1`);
  después, por su id. Un elemento que viene de un original puede volver a su
  imagen original; uno agregado por la doctora no
- En la landing la galería es una cuadrícula que escribe Blade (cada foto con
  `loading="lazy"` y su título como texto alternativo; con 1 o 2 fotos queda
  centrada) y un lightbox en Alpine (`resources/js/galeria-lightbox.js`:
  Esc, flechas, foco atrapado y de vuelta a la foto). El lightbox lee los
  datos de los atributos `data-*` de cada foto, escapados por Blade: no hay
  JavaScript armado a mano. Sin fotos, la sección no se muestra
- `data-aparecer` anima con `fill-mode: backwards` y no `both`: un transform
  que se queda puesto hace de contenedor de los `position: fixed` de adentro
  (el lightbox quedaría encerrado en la sección)
- Testimonios: se quitaron de la landing (eran 5 tarjetas inventadas). No
  hay tabla ni panel de reseñas; si se agregan, que sean opiniones reales
  de pacientes, con su permiso, y que la sección no se muestre vacía

## Imágenes subidas (ImagenService)
- `App\Services\ImagenService` es el único camino para subir imágenes
  (anuncios hoy; landing editable, transformaciones u otros módulos
  después): `guardar($archivo, 'carpeta')` devuelve la ruta relativa que
  se guarda en la base; `eliminar($ruta)`, `reemplazar(...)`, `url($ruta)`
  y las reglas de formulario `ImagenService::reglas()`. En la base solo se
  guarda la ruta, nunca una URL
- Valida el contenido real: JPG, PNG o WebP (no SVG ni GIF), máx. 5 MB y
  4096 px por lado (`config/imagenes.php`). Nombre aleatorio y extensión
  según el contenido
- **Redimensionado al guardar** (opcional, solo si la extensión GD está
  cargada): reduce a 1600 px de ancho máximo sin ampliar nunca, conserva
  formato y transparencia (JPG, PNG, WebP), aplica la rotación EXIF de las
  fotos de celular y quita los metadatos (EXIF, ubicación GPS, comentarios).
  Siempre vuelve a codificar, también las imágenes chicas (JPG y WebP con
  calidad 85). Se guarda el **original sin tocar**, sin error, cuando: no
  hay GD, está apagado (`IMAGENES_REDIMENSIONAR=false`), el WebP es
  animado (GD solo lee el primer cuadro), no alcanza la memoria (se calcula
  antes, porque quedarse sin memoria es un error fatal que ningún
  try/catch atrapa) o GD falla por cualquier motivo (esto último se
  registra en el log). Nunca impide una subida. Valores en
  `config/imagenes.php` (`ancho_max`, `calidad`)
- **GD en Railway**: no se declara `ext-gd` en composer.json (rompería
  `composer` y el arranque en máquinas sin GD). En el servicio de Railway
  se agrega la variable `RAILPACK_PHP_EXTENSIONS=gd` (Railpack instala esa
  extensión) y se redespliega. Sin ella todo funciona igual, solo que las
  imágenes se guardan sin reducir. Se comprueba subiendo una foto grande y
  mirando sus dimensiones
- Al reemplazar o eliminar un registro hay que borrar la imagen con
  `eliminar()`, después de guardar en la base. `eliminar()` ignora rutas
  que no sean las que genera `guardar()`
- Disco `imagenes` (config/filesystems.php), elegido por .env:
  `IMAGENES_ROOT` (carpeta; por defecto `storage/app/public/imagenes`),
  `IMAGENES_URL` (por defecto `/imagenes`, solo si se usa un CDN) e
  `IMAGENES_DISK` (para cambiar a `s3` sin tocar código). `php artisan
  storage:link` crea `public/imagenes` apuntando a `IMAGENES_ROOT`
- **Railway** (los archivos del contenedor se pierden en cada deploy):
  1. Crear un Volume en el servicio, montado en `/data`
  2. Variable `IMAGENES_ROOT=/data/imagenes`
  3. El enlace `public/imagenes` hay que recrearlo en cada deploy
     (`public/` se reconstruye; sin enlace las imágenes dan 404). Con el
     builder **Railpack** (el de los servicios nuevos; se ve en Settings →
     Build) el script de arranque ya corre `php artisan storage:link`
     (y también `migrate --force`: las migraciones nuevas se aplican
     solas al desplegar). Con Nixpacks o Dockerfile hay que agregar
     `php artisan storage:link --force` al comando de pre-deploy
  4. Si al subir sale "permission denied": variable `RAILWAY_RUN_UID=0`
  5. Límite de 5 MB por imagen: el proyecto trae su propio `php.ini` en la
     raíz con `upload_max_filesize = 6M` (por archivo) y
     `post_max_size = 12M` (por petición: la sección "nosotros" de la
     landing sube 2 imágenes en un mismo formulario). También deja
     `session.upload_progress.enabled = Off`
     - Railpack usa ese archivo **en lugar** del suyo, no los combina: por
       eso es una copia completa de su php.ini (railwayapp/railpack,
       `core/providers/php/php.ini`, commit 9d1a4ea) con esos cambios. Si
       Railpack cambia su php.ini, el proyecto no recibe esos cambios:
       hay que copiarlos a mano conservando los tres valores de arriba
     - Si se sube `max_kb` (config/imagenes.php) o se agregan imágenes a
       un formulario, revisar los dos límites (el encabezado del php.ini
       explica el cálculo)
     - En local no se lee: Laragon/XAMPP usan su propio php.ini
     - El Caddyfile de Railpack no limita el cuerpo. Con Nixpacks/nginx o
       un Dockerfile propio el php.ini de la raíz no se usa: los valores
       por defecto son 2 MB (PHP) y 1 MB (nginx) y hay que poner los
       mismos límites (`upload_max_filesize=6M`, `post_max_size=12M`) y
       `client_max_body_size 12m;`
     - Si una imagen pasa de 6 MB, Laravel dice "No se pudo subir la
       imagen" (límite de PHP); si la petición entera pasa de 12 MB,
       PHP la descarta y Laravel responde 413
  Un Volume sirve solo a una réplica y no tiene respaldo automático. Si
  el enlace público no funciona en Railway, plan B: servir las imágenes
  desde una ruta de Laravel con caché
- Local: hay dos PHP en la máquina y no tienen la misma configuración.
  Laragon (`C:\laragon\bin\php\...`) tiene GD activo y límites de 2 GB;
  XAMPP (`C:\xampp\php`, el que está en el PATH y usa `php artisan`)
  tiene límites de 40 MB y GD desactivado (`;extension=gd`)
- En local con Windows, `storage:link` puede pedir una consola de
  administrador para crear el enlace
- Pruebas: `Storage::fake('imagenes')` y `tests/Support/ImagenesDePrueba`
  (bytes reales de imagen, sin GD). Las pruebas del redimensionado
  (`ImagenRedimensionadoTest`) que necesitan GD se omiten solas si no está
  cargada. Para correrlas con el PHP de XAMPP, que no la trae activa:
  `php -d extension=gd vendor/bin/phpunit` (con `php artisan test` la
  opción `-d` no llega al proceso de pruebas; o se descomenta
  `extension=gd` en `C:\xampp\php\php.ini`)

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
- Administrador (la doctora): lo crea `AdminSeeder` con `ADMIN_EMAIL` y
  `ADMIN_PASSWORD` (config/admin.php). En local, sin esas variables, usa
  admin@dentissa.com y una contraseña aleatoria que muestra en la consola al
  sembrar; ponlas en el .env para tener siempre la misma
- En producción las dos variables son obligatorias y la contraseña debe tener
  al menos 12 caracteres; si no, el seeder falla sin crear nada. El primer
  admin se crea con `php artisan db:seed --class=AdminSeeder --force` (no
  correr `db:seed` a secas: `DatosPruebaSeeder` mete datos de demo). Si el
  correo ya existe, el seeder no lo modifica
- El seeder ya no crea la asistente: se da de alta en el módulo Personal
- paciente@test.com / paciente123 (de `DatosPruebaSeeder`, solo para local)
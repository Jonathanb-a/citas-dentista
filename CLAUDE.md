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
- App\Services\IA\ProveedorIA: servicio de IA con proveedor intercambiable
  (ver "Chatbot con IA" más abajo; el chat de la landing se construye encima)
- App\Services\Chat\ChatService: una petición del chat público (`POST /chat`):
  bucle de herramientas, tope global, instrucciones, historial y propuesta firmada
- App\Services\Chat\HerramientasChat y Herramientas/: las tres herramientas de citas
  de la IA (ver "Chatbot con IA (paso 3)"); `AgendarCita` replica las reglas del
  agendado normal (no usa CitaManualService, que depende de `auth()`)
- Middleware CheckRole para los roles: admin, asistente, paciente

## Expediente clínico
- Fase 1 (lista): historia clínica NOM-013-SSA2 en la tabla expedientes
- Fase 2 (lista): odontograma en la tabla `odontograma`, un registro por
  diente marcado; sin registro = diente sano. Estados y colores viven en
  Odontograma::ESTADOS (única fuente de verdad de validación, leyenda,
  resumen y modal)
- Fase 3 (pendiente): dentición infantil (las notas de evolución ya existen, solo para la clínica)
  - Los números FDI temporales (51-55, 61-65, 71-75, 81-85) ya caben en
    `odontograma.numero_diente` sin migración: solo hay que ampliar
    Odontograma::numerosValidos() y dibujar 20 dientes
  - El caso a resolver es la **dentición mixta**: entre los 6 y los 12 años
    el mismo paciente tiene dientes permanentes y temporales a la vez, así
    que el odontograma tendrá que mostrar ambas denticiones juntas, no un
    interruptor que alterne entre una y otra
  - La clínica sí atiende niños (servicio "Ortodoncia maxilar")

## El paciente NO ve su expediente
El paciente **no tiene acceso al expediente clínico**: ni a los antecedentes, hábitos, diagnóstico,
plan de tratamiento, odontograma y notas de evolución, ni a `notas_doctora` y `observaciones` (el
espacio de trabajo interno de la doctora, donde no se puede garantizar qué escriba). Solo la doctora
(admin) lo ve y lo edita. **La asistente tampoco tiene acceso** (su único permiso sobre pacientes es
restablecer contraseña). Nada se borró: las tablas `expedientes`, `odontograma` y `notas_evolucion` y
sus datos siguen igual; solo se quitó el acceso del paciente.

- "Mi perfil" tiene solo las pestañas **Mis datos** y **Seguridad**. El paciente edita ahí su
  nombre, apellidos, correo, teléfono, sexo, fecha de nacimiento y contraseña (viven en `users`);
  nunca escribió nada del expediente. `Paciente\PerfilController` y `Paciente\DashboardController`
  **no cargan** el expediente, el odontograma ni las notas (una prueba vigila que ni siquiera se
  consulten esas tablas). El dashboard ya no tiene el bloque "Notas de tu doctora"
- No hay rutas del paciente para el expediente. Las del expediente son de la doctora
  (`/admin/pacientes/{id}` y sus `expediente`, `odontograma`, `imprimir` y `update`, con
  `role:admin`): un paciente o la asistente reciben **403** con cualquier id, el suyo o el de otro
- El registro, el agendado manual y el chat siguen creando un expediente **vacío** por paciente
  (la doctora lo llena en consulta)
- Las pantallas de login y registro ya no prometen "Tu expediente a la mano": la tarjeta dice
  "Tu cuenta, tus citas"
- **Posible mejora futura**: el paciente dejó de ver qué se le hizo, las indicaciones para casa y la
  próxima cita sugerida (antes en "Mis consultas"). Si hace falta, se crea un campo nuevo y
  explícito **"indicaciones para el paciente"** en la nota de evolución (que la doctora escribe
  pensando en que el paciente lo lea) y solo eso se muestra; no se destapan `observaciones`,
  `notas_doctora` ni el resto del expediente
- **Pruebas**: `ExpedienteSinAccesoPacienteTest` (Mi perfil y dashboard sin ningún dato del
  expediente, sin consultar sus tablas, login y registro, 403 para el paciente —con su id y con el
  de otro— y para la asistente, redirección al login sin sesión, la doctora sigue viendo y editando,
  y los datos siguen existiendo). Verificado quitando a propósito cada defensa

## Perfil e Inicio del paciente (foto, resumen y próxima cita)
- **Foto de perfil** (Mi perfil > Mis datos, `Paciente\FotoPerfilController`): `POST` y `DELETE`
  `/paciente/perfil/foto`, **sin id en la ruta**: cada paciente solo toca la suya (`auth()->user()`).
  Detrás de `role:paciente` (doctora y asistente reciben 403) y **los pacientes del chat reciben 403**
  (`esDeChat()`). Columna `users.foto` (ruta relativa de `ImagenService`, carpeta `perfiles`; migración
  idempotente; **no es asignable en masa**). Subir o cambiar guarda la nueva, registra la ruta y solo
  después borra la anterior; quitar borra el archivo y deja la columna en null
- Pasa por `ImagenService` con la opción `['cuadrada' => 512]` (`config('imagenes.avatar_px')`): con
  GD recorta al centro a cuadrado y reduce a 512 px sin ampliar nunca (una foto de 300x200 queda de
  200x200), conserva formato y transparencia y quita los metadatos. **Sin GD se guarda el original**
  (el avatar circular lo recorta el navegador con `object-cover`). Valida el contenido real (JPG, PNG
  o WebP, máx. 5 MB; un texto o un SVG con extensión `.jpg` se rechaza; una imagen real con extensión
  engañosa se guarda con su extensión real) y usa nombres aleatorios
- **Límite**: 5 subidas, cambios o quitar por hora por usuario (`throttle:foto-perfil`,
  `FOTO_PERFIL_LIMITE_HORA`; 429 al pasarse; el límite es por usuario)
- **Avatar** (`<x-avatar :usuario="$u" tamano="h-10 w-10" texto="text-sm" />`): foto circular o, sin
  foto, un círculo rosa con las iniciales (`User::iniciales()`: la primera letra del nombre y del
  apellido; nunca vacío). Se ve junto a "Hola, {nombre}" en el menú superior, en el menú móvil, en Mi
  perfil y en la **ficha de la doctora** (`admin.pacientes.show`). Si la imagen no carga (404) cae a
  las iniciales. Nombre e iniciales se escapan con Blade. **No aparece en la landing** (los
  testimonios solo usan el nombre)
- **Si el disco de imágenes no está disponible** (`IMAGENES_ROOT` sin montar o sin permisos): al subir,
  mensaje amable ("No pudimos guardar tu foto en este momento… tu perfil sigue funcionando") y la foto
  anterior queda; `User::fotoUrl()` nunca lanza (devuelve null y se ven las iniciales), porque Laravel
  falla al abrir un disco local cuyo directorio no se puede crear y eso tumbaría el perfil entero
- **Logs**: solo la clase del error ("Foto de perfil: no se pudo guardar (UnableToCreateDirectory)"),
  sin rutas ni contenido. Los logs de `ImagenService` también quedaron así: **solo la clase del
  error**, sin ruta ni mensaje (antes `eliminar()` y el redimensionado registraban `getMessage()`)
- **Privacidad (mejora futura)**: las fotos quedan en el disco público `imagenes`
  (`/imagenes/perfiles/<id aleatorio>.jpg`): el nombre no se puede adivinar, pero **no hay control de
  acceso** (quien tenga el enlace la ve; el enlace solo se muestra al paciente y a la doctora). Si se
  usa con pacientes reales, conviene **servir las fotos desde un disco privado con una ruta
  autenticada** (solo el paciente y la doctora) en lugar del disco público
- **Inicio del paciente** (`Paciente\DashboardController`): el **resumen** muestra **Total, Atendidas
  y Canceladas** con **una sola consulta agrupada** (`select status, count(*) … group by status`), sin
  cargar citas en memoria. **Total cuenta todas las citas, incluidas canceladas y reagendadas**
  (reemplazó a Pendientes y Confirmadas). "Tu próxima cita" es la más cercana pendiente o confirmada
  desde **hoy en la zona horaria del consultorio**, solo del propio paciente, con servicio,
  especialidad, fecha (en español) y hora. Estados: **pendiente** = "la clínica aún no la acepta" (sin
  botón); **confirmada** y sin confirmar = "Aceptada por la clínica" con el botón **Confirmar mi
  asistencia** (la ruta `paciente.citas.confirmar` de siempre); confirmada y confirmada por el
  paciente = "Confirmada ✓". Sin próxima cita: mensaje amable y botón "Agendar cita". "Mis citas"
  sigue siendo la lista completa
- **Pruebas**: `FotoPerfilTest` (subir, cambiar y quitar con el archivo viejo desaparecido, tipos y
  tamaños inválidos, imagen falsa, recorte cuadrado con GD, autorización, límite, disco caído, avatar
  y logs) e `InicioPacienteTest` (contadores, próxima cita con y sin cita, estados, zona horaria, datos
  de otros pacientes). Verificado quitando a propósito cada defensa y en navegador (52 comprobaciones
  en 390 y 1440 px, con un Volume real: foto de 512 px, el archivo viejo borrado del Volume, el enlace
  `public/imagenes` creado solo)

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
**Railway bloquea el SMTP saliente** (conectar a smtp.gmail.com:465 da timeout), así que el correo
sale por la API HTTPS de Resend (`resend/resend-laravel`, que trae `resend/resend-php`).

- **Variables en Railway** (lo primero que hay que poner; sin `MAIL_MAILER=resend` Laravel sigue
  intentando SMTP o escribe en el log y ningún correo sale):

      MAIL_MAILER=resend
      RESEND_API_KEY=re_...                     (del panel de Resend)
      MAIL_FROM_ADDRESS=onboarding@resend.dev   (hasta tener dominio verificado)
      MAIL_FROM_NAME=Dentissa

- **Remitente**: siempre por `.env` (`MAIL_FROM_ADDRESS`). Sin dominio verificado en Resend solo
  sirve `onboarding@resend.dev` y **solo entrega al correo del dueño de la cuenta de Resend**
  (ver "Pendiente antes de entregar"). Con dominio verificado se cambia a una dirección propia
  (ej. citas@dominio.mx) sin tocar código
- **Qué correos salen** (solo dos, notificaciones nativas de Laravel que usan el mailer por
  defecto): verificación de correo (registro, reenviar y cambio de correo en Mi perfil) y el
  enlace de "Olvidé mi contraseña". No hay `Mail::` ni mailables propios; los testimonios y las
  citas no mandan correo (los avisos a la clínica van por Telegram)
- **Local y pruebas**: `.env.example` trae `MAIL_MAILER=log` (los correos se escriben en
  `storage/logs/laravel.log` y nunca salen). `phpunit.xml` fija `MAIL_MAILER=array`, deja
  `RESEND_API_KEY` vacía y un remitente ficticio: aunque el .env local tenga clave, las pruebas no
  llaman a Resend (el cliente de Resend va directo por Guzzle, así que `Http::fake()` no lo
  interceptaría: por eso se prueba con el mailer `array`, `Mail::fake` o un transporte falso)
- **Clave y tiempos** (`App\Services\CorreoResend`, registrado con `Mail::extend('resend')` en
  `AppServiceProvider`): la clave se lee de `config('services.resend.key')`, nunca de `env()`
  en el código. El cliente de Resend del paquete no trae tiempo límite; el nuestro lleva **5 s en
  total y 3 s para conectar** (`mail.mailers.resend.timeout` y `connect_timeout`; .env
  `MAIL_RESEND_TIMEOUT` y `MAIL_RESEND_CONNECT_TIMEOUT`). Si falta `RESEND_API_KEY` con
  `MAIL_MAILER=resend`, el log dice una vez por proceso "falta RESEND_API_KEY" (sin datos) y el
  envío falla con una excepción que ya atrapan los puntos de envío
- `RESEND_ROUTES` está apagado por defecto en config/resend.php: el paquete
  expone POST /resend/webhook y el proyecto no usa webhooks
- **Un fallo de correo nunca debe tumbar una operación del usuario.** Los
  cuatro puntos de envío están protegidos: registro, reenviar verificación,
  cambio de correo en Mi perfil y "Olvidé mi contraseña". Para la
  verificación se usa `User::enviarVerificacion()`, que registra el error
  en el log y devuelve false en vez de lanzar la excepción. **El log solo lleva la clase de la
  excepción** (`TransportException`…): ni el mensaje, que puede traer la dirección de correo,
  ni el id del usuario ni el contenido
- El mailer SMTP (solo si algún día se vuelve a él) tiene timeout de 5 s: el error "Maximum
  execution time of 30 seconds exceeded" es un error fatal de PHP que ningún try/catch
  atrapa, así que la protección real es no quedarse colgado
- **Pruebas**: `CorreoFallidoTest` (los cuatro puntos con el correo caído) y `CorreoResendTest`
  (mailer array sin red, remitente por config, timeouts 5/3 y ajustables, aviso de clave una vez,
  logs sin direcciones ni contenido). Verificado quitando a propósito cada defensa

## Anuncios y promociones de la landing
- Los edita la doctora en el admin, entrada "Landing Page" del menú
  (`Admin\AnuncioController`, modelo `Anuncio`). La landing la sirve
  `LandingController`
- **Regla de oro: sin anuncios vigentes la landing es idéntica a la
  original.** `tests/Fixtures/landing-sin-anuncios.html` es el HTML de
  referencia y una prueba lo compara. Si algún día cambias la landing a
  propósito, regenera ese archivo; si la prueba falla sin que lo
  hayas hecho, algo se coló en la landing
- **Orden de la landing** (`welcome.blade.php` y `resources/views/partials/landing-*`):
  hero → `#tratamientos` → carrusel `#novedades` (si hay anuncios) →
  `#nosotros` (misión, visión y `#certificados` en bloques compactos) →
  `#galeria` → `#testimonios` (si hay opiniones autorizadas) → `#agendar`
  (franja final: título y texto editables `agendar.*`, botones fijos a
  `route('paciente.citas.create')` y `/register`) → `#contacto` → pie
- El navbar y el pie enlazan solo a secciones que se muestran:
  `LandingController` comparte `$navegacion` (sin tratamientos ni opiniones
  si no hay). El menú completo se ve desde `lg`; abajo, el desplegable
  (botón con nombre, `aria-expanded`, Esc lo cierra). Las secciones del menú
  llevan `scroll-mt` para que el navbar fijo no tape su título
- Colores con texto en la landing: AA (4.5:1). Botones `#c03a63` con hover
  `#a13153`, enlaces de texto `#c03a63`; `#d75078` y `#f285a2` solo en
  fondos, íconos y bordes
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
- **Opiniones (`#testimonios`)**: pestaña "Opiniones" del panel
  (`Admin\LandingTestimoniosController`), colección `testimonios` de
  `config/landing.php` guardada en `landing_items` con
  `LandingColeccionService`. Sin fotos y sin originales (mínimo 0, máximo
  12). Campos: nombre (puede ser solo nombre e inicial), opinión,
  calificación de 1 a 5 y la casilla obligatoria "El paciente autorizó
  publicar esta opinión". La landing (`partials/landing-testimonios`, tras
  la galería) muestra solo las autorizadas; sin ninguna, la sección no
  aparece. Las 5 tarjetas inventadas que había antes se quitaron
- `LandingColeccionService` sirve para listas con o sin imágenes
  (`imagenes` en la colección) y con campos `texto`, `calificacion` o
  `casilla`; los avisos de máximo y mínimo van en `textos` de cada colección

## Testimonios de pacientes (módulo "Testimonios")
Opiniones de pacientes con cuenta, moderadas por la doctora. **Conviven** con las opiniones
manuales de la pestaña "Opiniones" de Landing Page (`landing_items`): la landing las une en
la misma sección `#testimonios`; el promedio de estrellas solo cuenta las de pacientes.

- **Quién puede opinar** (`TestimonioService::puedeOpinar`, el único lugar que lo decide; la
  política y los controladores lo usan): rol paciente, cuenta activa, **no del chat**
  (`esDeChat()`) y **al menos una cita `atendida`**. Un testimonio por paciente (índice único
  en `testimonios.paciente_id`; ante una carrera `crear()` devuelve null). Sin cita atendida el
  paciente ve la explicación en lugar del formulario
- **Datos** (`testimonios`, modelo `Testimonio`): calificación 1-5, texto de **20 a 300**
  caracteres (`config/testimonios.php`), `autorizado` (la casilla "Autorizo que se muestre mi
  nombre y mi opinión en la página", obligatoria), `estado` (pendiente | aprobado | oculto),
  `destacado` y `aprobado_en`. **`estado` y `destacado` no son asignables en masa**: solo los
  cambia `TestimonioService` (cuidado: `update(['destacado' => ...])` se ignora). El nombre
  público sale de la cuenta ("María G.": nombre de pila e inicial del apellido); no se guarda aparte
- **Estados**: crear → pendiente. **Editar siempre vuelve a pendiente** (también uno oculto) y
  quita lo destacado. La doctora: aprobar (solo desde pendiente), ocultar (de pendiente o
  aprobado; quita lo destacado), restaurar (oculto → **pendiente**, no se publica directo),
  destacar (solo aprobados, máx. `TESTIMONIOS_DESTACADOS_MAX`, 6; el conteo y el guardado van en
  una transacción) y eliminar (**definitivo**; el paciente también puede borrar el suyo).
  Borrar la cuenta del paciente borra su testimonio (cascada)
- **Lo que ve el paciente** (menú "Mi opinión", `Paciente\TestimonioController`): En revisión,
  Publicado o **No publicado** (un oculto nunca se le anuncia como "oculto"). Advertencia junto
  al formulario: no escribir datos médicos ni de contacto; **sin filtros automáticos de
  contenido, la doctora revisa a mano**. No hay correo al paciente (Resend aún no entrega a
  pacientes reales): el estado se ve en su panel
- **Panel de la doctora** (menú "Testimonios", `Admin\TestimonioController`, solo `role:admin`):
  filtros Pendientes / Aprobados / Ocultos con contador (por defecto pendientes), tabla en
  escritorio y tarjetas en móvil, nombre completo y "se verá como"
- **Landing** (`TestimonioService::paraLanding`, `LandingController`): solo APROBADOS; orden:
  destacados de pacientes → manuales → pacientes más recientes; **máx. 12 en total**
  (`testimonios.max_landing`). El promedio cuenta TODOS los aprobados (no solo los que caben),
  con un decimal y coma ("4,4 de 5 · 5 opiniones"), y solo se muestra desde
  `TESTIMONIOS_PROMEDIO_MINIMO` aprobados (3). **Sin aprobados ni manuales la landing es
  idéntica al fixture** (hay pruebas con pendientes, ocultos y con la tabla caída). Si la
  consulta falla, la landing se ve sin testimonios de pacientes (no 500)
- **Seguridad**: todo texto se escapa al mostrarse (`{{ }}`, nunca HTML). `limpiarTexto()`
  (`DatosParaPrompt::limpiar`) quita invisibles, enlaces y marcado (`< > { } [ ] | \``) y deja una
  sola línea **antes** de validar la longitud (los 20 caracteres son de contenido). Política
  `TestimonioPolicy` (un paciente solo toca el suyo; crear exige elegibilidad). Límite por usuario
  `throttle:testimonios`: **5 envíos por hora** entre crear, editar y borrar
  (`TESTIMONIOS_LIMITE_HORA`; 429 al pasarse). La validación pone un tope de 1-5 con `Rule::in` y
  `accepted` para la casilla
- **Telegram** (`TelegramService::testimonioRecibido`, en segundo plano): "Nuevo testimonio" o
  "Testimonio editado", **solo nombre de pila, estrellas y "pendiente de revisión"** (sin texto
  ni apellido). Moderar o borrar no avisa
- **Logs**: nunca el texto ni el nombre; solo la clase de la excepción
- **Pruebas** (`tests/Feature/Testimonios/*`, 67): elegibilidad, un testimonio por paciente,
  validación y limpieza, estados, política, límite, landing (fixture, orden, tope, promedio,
  convivencia con manuales), XSS en landing/admin/paciente, Telegram y logs. Verificado quitando a
  propósito cada defensa (la prueba del tope de 12 con manuales salió de ahí) y en navegador
  (39 comprobaciones en 390 y 1440 px: formulario, errores, teclado, moderación y landing).
  Pendiente: el `confirm()` del navegador en Eliminar es básico; se puede cambiar por un modal

## Imágenes subidas (ImagenService)
- `App\Services\ImagenService` es el único camino para subir imágenes
  (anuncios hoy; landing editable, transformaciones u otros módulos
  después): `guardar($archivo, 'carpeta')` devuelve la ruta relativa que
  se guarda en la base; `eliminar($ruta)`, `reemplazar(...)`, `url($ruta)`
  y las reglas de formulario `ImagenService::reglas()`. En la base solo se
  guarda la ruta, nunca una URL
- Valida el contenido real: JPG, PNG o WebP (no SVG ni GIF), máx. 5 MB y
  4096 px por lado (`config/imagenes.php`). Nombre aleatorio y extensión
  según el contenido. Opción `guardar(..., opciones: ['cuadrada' => 512])`: recorte cuadrado al
  centro y reducción a ese lado (fotos de perfil). Los logs del servicio llevan **solo la clase del
  error**, sin rutas ni mensajes
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
  storage:link` crea `public/imagenes` apuntando a `IMAGENES_ROOT` (en local; en Railway lo hace
  solo la app al arrancar, ver el paso 3 de Railway)
- **Railway** (los archivos del contenedor se pierden en cada deploy):
  1. Crear un Volume en el servicio, montado en `/data`
  2. Variable `IMAGENES_ROOT=/data/imagenes`
  3. **El enlace `public/imagenes` se crea solo al arrancar** (`App\Services\EnlaceImagenes`, llamado
     desde `AppServiceProvider::boot()`): `public/` se reconstruye en cada deploy y sin enlace las
     imágenes darían 404, pero ya no hace falta `storage:link` en ningún comando de Railway. El
     **Start Command de Railway está vacío** y las migraciones se aplican solas al desplegar
     (el script de arranque de Railpack corre `migrate --force`): no hay nada que configurar allá.
     Reglas del enlace automático:
     - Solo actúa con un Volume: `IMAGENES_ROOT` definido y **distinto de la ruta por defecto** del
       proyecto (`filesystems.imagenes_ruta_defecto`, la misma que usan el disco y `links`), y con
       `IMAGENES_DISK=imagenes` (con `s3` no hace nada)
     - **Solo en peticiones web**: no corre en pruebas ni en consola (`migrate`, `schedule`,
       `storage:link`…). Una vez por proceso; si el enlace ya está bien, el costo es un `readlink`
     - Crea la carpeta destino si falta; si `public/imagenes` no existe, la enlaza; si es un enlace
       a otro lugar (o roto), lo corrige. Si es una **carpeta o archivo real, no la toca** (podría
       tener imágenes) y solo avisa en el log
     - Nunca rompe la app: si falla (p. ej. permisos de `public/`), el log dice "no se pudo crear el
       enlace public/imagenes (falló symlink|mkdir|unlink)" con solo la clase o la función que falló,
       **sin rutas**, y las imágenes darán 404. Respaldo manual: `php artisan storage:link --force`
       desde una consola de Railway (o como pre-deploy)
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
  administrador para crear el enlace (el enlace automático de Railway no corre en local: sin
  `IMAGENES_ROOT` o con la ruta por defecto no hace nada)
- Pruebas del enlace: `EnlaceImagenesTest` (carpetas temporales reales: crea, ya estaba bien, corrige,
  carpeta real intacta, fallos sin rutas en el log, una vez por proceso, no corre sin Volume ni con la
  ruta por defecto ni con s3; `phpunit.xml` deja `IMAGENES_ROOT` vacío). Verificado quitando a
  propósito cada defensa y con un servidor web real con `IMAGENES_ROOT` (el enlace aparece en la
  primera petición y no en consola). Una defensa no se puede probar sola: que no corra en consola,
  porque en pruebas `runningUnitTests` y `runningInConsole` son ambas verdaderas
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

## Chatbot con IA (paso 1: servicio de IA)
Pasos: 1 servicio de IA (este apartado), 2 endpoint `POST /chat` (apartado
siguiente), 3 herramientas de citas (ver horarios, agendar) y 4 botón/widget en la
landing (los cuatro pasos ya existen; ver "paso 4" para el widget; el paso 5 añade botones y consultas).

- `App\Services\IA\ProveedorIA` (interfaz): `responder($mensajes, $herramientas,
  $instrucciones)` devuelve una `RespuestaIA` con texto, llamadas a herramienta
  (function calling) o un fallo. **Nunca lanza excepciones**: ante cualquier
  problema el `texto` trae el mensaje amable de `config('ia.mensaje_error')` y
  `falla` es true (el `motivo` técnico es solo para el log; no se agrega al historial)
- Formato neutro en `App\Services\IA\Datos`: `MensajeIA` (usuario, asistente,
  herramienta), `HerramientaIA` (nombre, descripción, JSON Schema), `LlamadaHerramienta`
  y `RespuestaIA`. Cada proveedor lo traduce al suyo
- `ProveedorHttp` (base) concentra lo común: timeout de 15 s (5 s para conectar),
  errores controlados y log sin secretos. Un proveedor solo implementa
  `url`, `encabezados`, `cuerpo` e `interpretar`
- `ProveedorIAFactory` elige la clase según `IA_PROVEEDOR` y el contenedor la
  entrega donde se pida `ProveedorIA`. Un proveedor mal escrito devuelve
  `ProveedorNoDisponible` (mensaje amable + aviso en el log), no rompe la página
- **Proveedores hoy**: `GroqProveedor` (el predeterminado) y `GeminiProveedor`.
  **Agregar otro** (OpenAI, Anthropic…): una clase que extienda `ProveedorHttp` y una
  entrada en `config/ia.php` (`clase`, `api_key`, `modelo`…). No se toca nada más.
  OpenAI usa el mismo formato (chat completions) que Groq, así que `GroqProveedor`
  es el punto de partida
- Config en `config/ia.php`; el código nunca llama a `env()` (una prueba lo vigila).
  Variables (.env local y Railway): `IA_PROVEEDOR` (por defecto `groq`), una key por
  proveedor (`GROQ_API_KEY`, `GEMINI_API_KEY`…) y el modelo de cada uno
  (`GROQ_MODELO`, por defecto `openai/gpt-oss-120b`; `GEMINI_MODELO`, por defecto
  `gemini-3.6-flash`). Cambiar de proveedor es cambiar `IA_PROVEEDOR`. Sin key no
  se llama a nadie. `php artisan ia:probar` (comando temporal) usa el proveedor de
  `IA_PROVEEDOR` con la key real del .env
- **Groq** (`GroqProveedor`): API compatible con OpenAI,
  `POST https://api.groq.com/openai/v1/chat/completions`, key en
  `Authorization: Bearer` (no en la URL). Herramientas como `{type: function,
  function: {...}}`; los argumentos de cada llamada llegan como **cadena JSON** y se
  decodifican (si no son JSON válido, fallo amable). No hay firmas que reenviar:
  el historial se reconstruye del formato neutro. **No se manda `name` en ningún
  mensaje**: Groq lo rechaza con 400 (`tool_call_id` basta). Documentación:
  console.groq.com/docs (tool-use, models, rate-limits, your-data)
  - Modelo `openai/gpt-oss-120b`: de producción, con herramientas y en el plan
    gratuito (no admite llamadas en paralelo; el flujo de citas es secuencial).
    Alternativa `qwen/qwen3.8-27b`, pero está en **Preview** y Groq puede
    descontinuarla sin aviso. Llama 3.3 70B y 3.1 8B ya son solo Enterprise
  - `reasoning_effort` (`low` por defecto, en `config/ia.php`; `medium`/`high` o
    `null` para no mandarlo): menos latencia y menos tokens. Solo se manda con
    modelos `openai/gpt-oss*`, porque otros darían 400. `max_tokens` (1024) topa
    la respuesta, y el razonamiento cuenta dentro. El razonamiento que devuelve el
    modelo (`message.reasoning`) se ignora: nunca llega al visitante
  - **Límites del plan gratuito** en gpt-oss-120b: 30 peticiones/min, 1.000/día,
    **8.000 tokens/min** y 200.000/día (verificar los de la cuenta en su página de
    límites). Cada mensaje reenvía historial, instrucciones y herramientas
    (≈2-3 mil tokens): con varios visitantes a la vez llega el 429 y el visitante
    ve el mensaje amable. Para producción, el plan Developer (250.000 tokens/min)
  - **Gemini** quedó como alternativa: la cuenta actual recibe 403 "free tier not
    available for this project" y Google pide activar la facturación
- **Gemini** usa la Interactions API (`POST /v1beta/interactions`), la que Google
  recomienda para integraciones nuevas (`generateContent` quedó como heredada). Va
  sin estado (`store: false`, se manda el historial completo), con la key en el
  encabezado `x-goog-api-key` (no en la URL) y la versión del contrato fijada con
  `Api-Revision`. La documentación está en aistudio.google.com/docs (ai.google.dev
  redirige ahí)
- **Firmas de pensamiento**: con los modelos Gemini 3 en adelante, omitir la firma
  de una llamada a función es un error 400. Por eso la `RespuestaIA` guarda los pasos
  originales del modelo (`crudo`) y hay que agregar al historial
  `$respuesta->comoMensaje()`, no un `MensajeIA::asistente()` armado a mano. El
  orquestador del chat (paso siguiente) hace el bucle: IA pide herramienta, se
  ejecuta, se manda `MensajeIA::resultado(...)` y se vuelve a preguntar
- **Log**: solo proveedor, estado HTTP y código de error de la API. Nunca la key ni
  los mensajes del usuario (pueden traer nombre y teléfono), ni siquiera el
  `message` del error de la API, que puede repetir lo enviado. Los errores de
  conexión pasan por un filtro que tapa la key
- **Privacidad**: Groq no retiene los datos de inferencia por defecto; solo puede
  guardar entradas y salidas hasta 30 días para fiabilidad o abuso (se evita con
  Zero Data Retention en su consola, Data Controls), en servidores de EE. UU. Gemini:
  el nivel gratuito puede usar lo enviado para mejorar los productos de Google; el
  de pago no. El chatbot mandaría nombre y teléfono del paciente: no enviar datos
  clínicos y valorar activar Zero Data Retention antes de usarlo con pacientes
  reales. Pendiente de decidir en el paso de herramientas de citas
- Límite por visitante y control de gasto del endpoint público: ver el paso 2
- `phpunit.xml` fija `IA_PROVEEDOR=groq` y deja las keys y los modelos vacíos (así
  el .env local no cambia las pruebas); estas usan `Http::fake()` con
  `Http::preventStrayRequests()`: ninguna llega a la IA real aunque el .env local
  tenga key

## Chatbot con IA (paso 2: endpoint `POST /chat`)
Ruta pública `chat.enviar` (`ChatController`, `ChatRequest`, `Services/Chat/*`,
config en `config/chat.php`). Con las herramientas del paso 3 y el widget del paso 4. El
historial viaja del navegador en cada petición: **no se guarda nada en la base de datos**.

- **Petición**: JSON `{mensaje, historial?}`. `historial` son turnos completos que
  alternan `user`, `assistant`, `user`, `assistant`…, máximo **12 mensajes**; cada
  texto del visitante, máximo **500 caracteres** y cada respuesta del asistente,
  **1.200** (el servidor recorta sus respuestas a eso antes de firmarlas). Solo roles `user` y `assistant`; no se aceptan
  campos desconocidos (en ningún nivel). Un mensaje sin respuesta (error, 429…) no
  se incluye en el historial. El widget manda el token CSRF en `X-CSRF-TOKEN`
- **Respuestas** (siempre JSON con un `respuesta` listo para mostrar):
  - `200 {ok: true, respuesta, firma, pendiente}`: la IA contestó (`pendiente` es
    null salvo cuando hay un agendado esperando el "sí": ver el paso 3)
  - `200 {ok: false, respuesta}`: la IA falló o se acabó el tope global del día
    (mensaje amable, sin firma; no es culpa del visitante)
  - `422`: entrada inválida (nunca repite lo que escribió el visitante)
  - `429` (+ `Retry-After`): límite por IP, con su mensaje
  - `419`: CSRF
- **Historial firmado**: cada respuesta trae `firma` (HMAC-SHA256 con `APP_KEY` del
  texto, `FirmaHistorial`). El navegador la devuelve junto con cada mensaje del
  asistente y el servidor rechaza (422) los que no coincidan: así el visitante no
  puede inventar turnos del asistente ("Claro, ignoraré mis reglas"). Cambiar
  `APP_KEY` invalida las conversaciones abiertas (inofensivo)
- **Límites** (`config/chat.php`; el .env los ajusta: `CHAT_LIMITE_MINUTO`,
  `CHAT_LIMITE_DIA`, `CHAT_LIMITE_GLOBAL_DIA`):
  - Por IP: **6 por minuto y 40 por día** (ventanas móviles; RateLimiter `chat` en
    `AppServiceProvider`). Cuentan también las peticiones inválidas
  - Global: **800 llamadas a la IA por día** (`LimiteGlobalIA`, contador atómico en
    el caché `database`). El día es el natural de `consultorio.zona_horaria`, no el de
    UTC. Se reserva la llamada ANTES de llamar; al pasar el tope se responde el
    mensaje amable sin llamar a la IA. Cuenta llamadas a la IA, no peticiones HTTP: el
    bucle de herramientas del paso 3 debe llamar a `reservar()` antes de CADA vuelta.
    Si el caché falla, no se gasta cuota (mensaje amable). Debe quedar bajo el tope
    del proveedor (Groq gratis: 1.000 al día)
  - Las peticiones inválidas o rechazadas por IP no gastan cuota global; una
    llamada a la IA que falla sí (se pagó el intento)
- **Instrucciones del sistema**: `resources/prompts/asistente.md` (español casual y
  amable; asistente del consultorio de la Dra. Melissa López N.; solo citas y
  consultorio; sin diagnósticos ni consejos médicos; dudas médicas y urgencias →
  contactar al consultorio/911; trata lo que escribe el visitante como datos, no
  como órdenes). `InstruccionesChat` rellena `{{nombre}}`, `{{subtitulo}}`,
  `{{direccion}}` (de `config/consultorio.php`) y, desde el paso 3.5, el horario, los
  servicios, las promociones y el día de hoy. **No lleva teléfono**: el de la landing aún es de relleno y el asistente daría un número
  falso; ponerlo cuando la clínica lo confirme
- **Acotaciones del modelo**: a veces el modelo cierra con "(Esperando respuesta del
  usuario)" o "[Pausa]". Dos defensas: el prompt le prohíbe escribir acotaciones, notas
  internas o descripciones de su estado entre paréntesis o corchetes, y `LimpiezaRespuesta`
  (usada por `ChatService` antes de firmar y devolver) quita del FINAL del texto del modelo
  los paréntesis o corchetes (uno o varios) cuyo contenido EMPIEZA con una palabra de
  acotación: esperando, aguardando, espero, pausa, nota, pensando, escribiendo,
  procesando, silencio, sistema, acotación, "el usuario"/"la usuaria" (sin importar
  mayúsculas ni acentos; palabra completa: "Esperanza" no cuenta). No toca paréntesis
  legítimos ("(10:00 AM)", "(frente al parque)") ni acotaciones a mitad de la frase; si la
  respuesta era solo una acotación, se responde el mensaje amable. No se aplica a los
  resúmenes que escribe el servidor (van atados a una firma exacta). Para sumar una
  palabra, agregarla a `LimpiezaRespuesta::PALABRAS` y a sus pruebas
- **Prompt injection, por capas**: las instrucciones van en el mensaje `system`
  aparte y el texto del visitante solo entra como mensaje de usuario (nunca se
  concatena ni se interpola); roles inventados y turnos del asistente falsificados
  se rechazan (firma); el texto se limpia de caracteres de control y de formato
  (invisibles, bidireccionales, "tags" Unicode) y tiene tope de longitud; en este
  paso la IA no tiene herramientas. Ningún prompt elimina la inyección por
  completo: **en el paso 3 cada herramienta debe validar en el servidor todo lo que
  pida el modelo** (fechas, servicio, identidad del paciente) y nunca confiar en
  identificadores que él invente
- **Sin contenido en los logs**: ni mensajes ni historial se registran en ningún
  camino (éxito, validación, límites, fallo de la IA, error inesperado: solo la
  clase de la excepción). Una prueba lo comprueba escenario por escenario
- **CSRF no frena a un bot** (un script pide la landing y toma cookie y token): lo
  que de verdad protege son el límite por IP y el tope global. Un atacante con muchas
  IPs podría agotar el tope del día y dejar sin chat a los pacientes; es preferible a
  una factura inesperada
- **La IP por visitante**: `$request->ip()` con `trustProxies('*')` toma la
  **última** entrada de `X-Forwarded-For` (la que agrega el proxy de Railway), que el
  visitante no puede falsificar (hay una prueba). Eso supone que Railway agrega la IP
  real al final y que hay un solo salto de proxy.
  **⚠️ Después del primer deploy, comprobar la IP real desde dos redes distintas** (por
  ejemplo wifi y datos del celular): con cada red, mandar mensajes al chat hasta
  recibir el 429 y confirmar que el límite de una red no afecta a la otra. Si las dos
  comparten el límite, todos los visitantes llegan con la IP del proxy y hay que
  ajustar `trustProxies` (o la clave del limitador) antes de abrir el chat
- Las sesiones y el caché usan `database`: el chat comparte ambos con el resto del
  sitio (el `POST /chat` ya trae sesión desde la landing)

## Chatbot con IA (paso 3: herramientas de citas)
La IA puede consultar servicios y horarios y agendar una cita nueva. **No hay (ni debe
haber) herramientas para cancelar, reprogramar ni consultar citas existentes**: por chat
no hay forma segura de verificar quién es el paciente. Si lo piden, la IA los manda a
contactar al consultorio. Ninguna herramienta lee ni devuelve citas o datos de ningún
paciente. Código en `Services/Chat/Herramientas/`, registro en `HerramientasChat`
(cualquier otro nombre que pida el modelo da `herramienta_desconocida`).

- **Herramientas**
  - `listar_servicios`: activos de especialidades activas (los mismos que ve el paciente),
    con id, nombre, especialidad, duración y descripción corta. **Sin precio**: `servicios`
    no tiene esa columna y el sistema no lo maneja
  - `ver_horarios_libres(servicio_id, fecha?)`: espacios libres por `DisponibilidadService`
    (bloqueos, citas, descansos); máx. 8 (paso 5; sin fecha, próximos días); solo `hora` y `hora_formato` (el `horario_id`
    nunca sale)
  - `agendar_cita(servicio_id, fecha, hora, nombre, apellidos, telefono)`: usa `hora` y no
    `horario_id`: el servidor busca el espacio libre y deriva el `horario_id`
- **El servidor valida TODO** lo que pide el modelo (`DatosCita`), y nunca repite lo
  recibido en los errores: servicio activo; fecha `AAAA-MM-DD` real, **desde mañana** hasta
  3 meses en la zona del consultorio (hoy no se ofrece: `DisponibilidadService` compara las
  horas de hoy con `Carbon::now()` en UTC; es un defecto del agendado normal, no del chat);
  hora `HH:MM` dentro de los espacios libres; teléfono de 10 dígitos (acepta espacios,
  guiones y lada 52/+52/521; rechaza los que empiezan en 0 o 1, repetidos y secuencias);
  nombre y apellidos solo letras, 2 a 60 caracteres. El error `{error, mensaje, campos}` le
  permite corregirse. Espacio ocupado: `horario_no_disponible` con 3 alternativas
- **Confirmación estricta, verificada por el servidor** (`ConfirmacionAgendado`): la
  primera llamada válida a `agendar_cita` NO agenda. El servidor escribe el resumen
  (servicio, fecha, hora, nombre y teléfono, plantilla `chat.mensajes.propuesta`), sin otra
  llamada a la IA, y devuelve `pendiente`: propuesta firmada (HMAC con `APP_KEY`, 15 min)
  con los datos y el hash EXACTO de ese resumen. Se agenda solo si, en la petición
  siguiente: la firma es válida y vigente, el último mensaje del asistente en el historial
  es ese resumen, la IA llama con exactamente los mismos datos y el último mensaje del
  visitante es un "sí" claro (sin negaciones, cambios, números ni más de 8 palabras).
  Cualquier otra cosa genera una propuesta nueva. Por eso "agenda sin preguntar" no
  funciona. Tras agendar, el servidor escribe la confirmación (`chat.mensajes.agendada`)
  y no hace otra llamada a la IA
- **Contrato con el widget (paso 4, ya implementado)**: guardar `pendiente` de la última respuesta y
  mandarlo en la petición siguiente (`pendiente`, junto con `mensaje` e `historial`); el
  historial sigue siendo solo texto con firma. Sin respuesta (error, 429…) no se agrega al
  historial. Las llamadas y resultados de herramientas **nunca viajan por el navegador**
  (viven dentro de una petición): el servidor reconstruye, desde la propuesta firmada, el
  intercambio que la produjo para que la IA sepa con qué datos llamar
- **Bucle** (`ChatService`): máx. **4 vueltas** por mensaje y **3 herramientas** por vuelta;
  cada llamada a la IA reserva su lugar en `LimiteGlobalIA`; al pasarse, mensaje amable.
  Una sola llamada válida a `agendar_cita` por mensaje (si el horario no estaba libre, la
  IA no puede reintentar sola con otro: debe preguntarle al paciente). Se usa
  `$respuesta->comoMensaje()` para reenviar el historial dentro de la petición
- **Cuenta del paciente del chat**: `citas.paciente_id` exige un usuario, así que se crea
  uno interno (rol paciente, expediente vacío, contraseña aleatoria que nadie conoce y
  correo marcador `chat-…@chat.dentissa.invalid`, `config('chat.agenda.correo_dominio')`).
  `User::esDeChat()` los reconoce. No pueden entrar a su cuenta; en el panel de pacientes
  aparecen con ese correo (la clínica puede editarlo y, si le pone uno real, deja de ser
  "del chat"). Si el teléfono ya es de un paciente real, de la doctora o de la asistente
  (se compara por los últimos 10 dígitos: hay teléfonos guardados con formato) o de una
  cuenta inactiva, se rechaza con un mensaje **genérico, idéntico al de otros motivos**
  (`no_disponible_por_chat`): no se revela si el teléfono existe, no se agenda a nombre de
  otro y nadie puede llenarle las 3 citas. Un paciente del chat sí puede volver a agendar
  (misma cuenta, máx. 3 citas activas por teléfono, como el agendado normal)
- **⚠️ Confirmación a 24 horas**: el paciente del chat no tiene cuenta para pulsar
  "confirmar", así que la liberación automática (`citas:liberar-no-confirmadas`) lo
  cancelaría a las 24 h de que la clínica aceptara su cita. Por eso, **la aceptación de la
  clínica cuenta como confirmación del paciente del chat** (`confirmado_por_paciente =
  true`) en `Admin\CitaController::confirmar`, `Asistente\CitaController::confirmar` y en
  `CitaManualService::crear` (existente, confirmada) y `::reagendar`. Para los demás
  pacientes nada cambia. La clínica debe llamarles para aceptar la cita (el aviso de
  Telegram lo recuerda)
- **Al agendar**: cita `pendiente` con `confirmacion_limite` a 24 h (igual que el agendado
  normal), `HistorialCita` "creada" con "chat web" a nombre del paciente, la nota interna
  con el nombre dado en el chat y aviso por Telegram `(chat web)` sin teléfono, en segundo
  plano (su fallo no afecta la cita). El agendado normal tampoco manda correo al paciente,
  así que aquí tampoco. Todo en una transacción: nada queda a medias, y si el índice único
  rechaza el espacio (carrera) no queda un paciente huérfano ni se gasta cuota
- **Spam de agenda**: un chat público baja la barrera para llenar la agenda con citas
  falsas. Tope de **2 citas por IP y 10 en total al día** (`LimiteCitasChat`;
  `CHAT_CITAS_LIMITE_IP_DIA` y `CHAT_CITAS_LIMITE_DIA`), que se reserva justo antes de
  crear y se libera si falla. Las citas pendientes que la clínica no acepta siguen
  ocupando el horario: revisar y cancelar las sospechosas
- **Instrucciones** (`resources/prompts/asistente.md`): flujo servicio → fecha → máx. 3
  horarios → nombre y apellidos → teléfono → `agendar_cita`; no cancela ni reprograma ni
  consulta; no habla de otros pacientes; y **pide no compartir información médica ni
  síntomas por el chat ("para eso contacta al consultorio"), sin repetirla ni usarla**. El
  catálogo de servicios con su id (`{{servicios}}`, de la base de datos) va en las
  instrucciones: ahorra la llamada a `listar_servicios` en casi todas las conversaciones
- **Privacidad**: los mensajes no se registran (ni nombres ni teléfonos ni lo escrito),
  ni se guardan: `motivo` queda vacío y la cita solo lleva el nombre y el teléfono que
  se necesitan. Los errores de herramienta se registran por su nombre y la clase de la
  excepción. El modelo recibe nombre y teléfono del paciente dentro de la conversación
  (ver la nota de privacidad de Groq en el paso 1)
- **⚠️ Plan gratuito de Groq**: cada llamada con herramientas pesa 2.500–3.500 tokens y el
  plan gratuito da 8.000 por minuto: un mensaje con 2–3 llamadas ya puede dar 429 a media
  conversación (mensaje amable). El resumen y la confirmación los escribe el servidor y el
  catálogo va en las instrucciones justamente para ahorrar llamadas, pero para tráfico real
  conviene el plan Developer (250.000 tokens/min). Cada llamada cuenta en `LimiteGlobalIA`
  (800 al día): ≈2–4 por mensaje con herramientas
- **Deuda técnica**: la lógica de crear una cita está repetida (agendado del paciente,
  `CitaManualService` y `AgendarCita`); conviene unificarla cuando se pueda tocar sin riesgo
- **Pruebas**: todo con `Http::fake()` y datos ficticios (nombres como "Ana Ejemplo
  Prueba", teléfonos 555 010 00xx). Se verificó quitando a propósito cada defensa
  (confirmación, "sí" claro, paciente real, una cita por mensaje, topes, hash del resumen,
  caducidad, firma, vueltas, confirmación de la clínica…): cada una hace fallar pruebas

## Chatbot con IA (paso 4: widget en la landing)
Botón flotante "Agenda tu cita" y ventana de chat solo en la landing pública. Archivos:
`partials/landing-chat.blade.php` (estructura), `resources/js/chat-widget.js` (componente
Alpine `chatWidget`, registrado en `app.js` como el carrusel y el lightbox) y unos
cuantos estilos en `app.css` (puntos de "escribiendo"). Los datos del servidor (URL de
`chat.enviar`, de `chat.token`, token CSRF, máx. de caracteres y nombre del consultorio)
viajan en atributos `data-*` escapados por Blade.

- **Cuándo aparece**: solo si `config('chat.activo')` (`CHAT_ACTIVO`, por defecto true;
  `false`/`0`/`off`/`no` lo apaga sin quitar la key) **y** el proveedor de IA elegido
  existe y tiene key (`ChatActivo`, calculado en `LandingController`, que nunca puede
  tumbar la landing). **Sin eso la landing es idéntica a la original** y
  `tests/Fixtures/landing-sin-anuncios.html` sigue verde sin tocarlo (las pruebas corren
  sin key). Con el chat activo el widget va entre `<!-- chat-widget:inicio -->` y
  `<!-- chat-widget:fin -->`: una prueba quita ese bloque y comprueba que el resto de la
  landing sigue siendo idéntico al fixture. Si algún día se activa el chat en producción y
  el widget no aparece, falta `GROQ_API_KEY` (o `CHAT_ACTIVO=false`)
- **Solo en la landing**: se incluye desde `welcome.blade.php` (el único que usa
  `x-layout`), no desde el layout ni el navbar; una prueba verifica que login, registro,
  "olvidé mi contraseña" y los paneles de admin, asistente y paciente no lo traen
- **Contrato**: `POST /chat` con `X-CSRF-TOKEN` y `{mensaje, historial, pendiente}`. El
  historial solo lleva turnos completos (pregunta + respuesta firmada), los últimos 6
  (12 mensajes); lo que terminó en error, 429 o caída no entra, ni su aviso. Se guarda la
  firma de cada respuesta y el `pendiente` de la última `ok: true` (null lo borra); si la
  petición falla, el anterior se conserva (sigue vigente unos minutos). Un reintento manda
  la misma pregunta
- **Respuestas**: `ok:true` se muestra; `ok:false` muestra el mensaje amable con
  "No se envió" y "Reintentar"; `422` muestra el texto del servidor y, si ya había
  historial, "Empezar de nuevo"; `429` muestra el mensaje y, si `Retry-After` ≤ 120 s,
  bloquea el envío con cuenta regresiva (un `Retry-After` largo es el límite diario: solo el
  aviso); `419` pide un token nuevo con `GET /chat/token` y reintenta **una sola vez**
  sin perder la conversación (si falla de nuevo: "Tu sesión caducó. Recarga la página");
  red caída, 5xx con HTML (no JSON) o 35 s sin respuesta: "No pude conectarme" con
  "Reintentar". A los 15 s dice "Sigo pensando…"
- **`GET /chat/token`** (`chat.token`, 20 por minuto por IP): `{token}` con `no-store`. El
  historial firmado no depende de la sesión, por eso se puede recuperar una sesión
  caducada sin perder la conversación. El mismo origen impide que otro sitio lo lea
- **Presupuesto de tiempo** (`chat.max_segundos`, 30): antes de cada vuelta del bucle de
  herramientas el servidor revisa que quepa otra llamada a la IA (`ia.timeout`); si no,
  mensaje amable (`chat.mensajes.tiempo`). La primera llamada siempre se hace. Nota: en
  Linux (Railway) `max_execution_time` mide tiempo de CPU y NO la espera de la red; en
  Windows (Laragon/XAMPP) sí mide tiempo real. El tope sirve sobre todo para que una
  petición no deje al visitante esperando más de 30 s
- **UX**: bienvenida escrita en Blade (no entra al historial) con el aviso de no compartir
  información médica; sugerencias "Agendar una cita" y "Ver horarios" hasta que el
  visitante escribe; Enter envía, Shift+Enter salto de línea, Enter durante una
  composición de teclado (IME) se ignora; envío desactivado mientras espera (el campo sigue
  editable); contador de 500 caracteres; "Escribiendo…"; scroll automático; reiniciar
  (cancela la petición en curso y ignora su respuesta tardía); si responde con la ventana
  cerrada, un punto en el botón avisa. Sin ventana emergente automática
- **Privacidad**: la conversación vive solo en la memoria de la página: no se guarda nada
  en el navegador (ni almacenamiento local o de sesión, ni IndexedDB, ni cookies propias).
  Cerrar la ventana la conserva; recargar la borra, a propósito. Una prueba estática vigila
  el JS
- **Texto, nunca HTML**: todo se pinta con `x-text` y `white-space: pre-wrap`; no hay
  `x-html`, `innerHTML` ni enlaces automáticos (una prueba estática lo vigila en el JS y en
  el partial). Detalle de Alpine: los mensajes se modifican siempre a través del objeto de
  la lista (`this.mensajes[...]`); modificar el objeto original no actualiza la pantalla
- **Posición y diseño**: botón redondo de 56 px abajo a la derecha (`z-40`); ventana de
  380 px de ancho y hasta 640 px de alto desde `sm`, y casi a pantalla completa en celular
  (`inset-2`, modal: bloquea el scroll del fondo y atrapa el foco). Navbar y menú móvil
  `z-50`, ventana `z-[55]`, lightbox `z-60`. **No hay botón flotante de WhatsApp**: el de
  `#contacto` y los íconos del pie son enlaces normales. El botón **sube justo lo que mide
  la parte visible del pie** cuando este aparece (variable CSS `--chat-subir`, calculada en
  `chat-widget.js`), para no tapar los íconos de redes y WhatsApp del pie; no cambia el
  diseño de la landing. Verificado en 9 anchos (320 a 1920 px) y en todas las posiciones
  del scroll. Al final de la página en celular el botón queda sobre la esquina del mapa
  de contacto (un iframe): el mapa se usa con gestos. Si algún día se agrega un botón
  flotante en esa esquina, hay que subir el del chat
- **Accesibilidad**: foco al campo al abrir (en pantallas táctiles, a la ventana, para no
  abrir el teclado de golpe); Escape cierra y devuelve el foco al botón; `role="dialog"`
  con `aria-labelledby`, `aria-modal` solo en pantalla completa, `role="log"` con
  `aria-live="polite"`, `role="status"` para "escribiendo", `role="alert"` en los avisos;
  campo con etiqueta y contador asociado; todos los controles de 44 px como mínimo; foco
  visible `#a13153`; contraste AA en todos los pares de colores. Con "reducir movimiento"
  no hay transiciones ni puntos animados y el scroll salta sin animación
- **Peso**: el componente va en el bundle principal (como el carrusel y el lightbox):
  JS 91,15 → 97,52 kB (gzip 33,66 → 35,72 kB) y CSS 52,71 → 55,27 kB (gzip 9,59 → 10,14 kB).
  Si algún día pesara demasiado, se puede pasar a un archivo que solo cargue la landing
- **Pruebas**: `ChatWidgetTest` (PHPUnit: presencia y ausencia, fixture, vistas sin widget,
  vigilancia estática, token y presupuesto de tiempo). El JavaScript **no** tiene pruebas
  automáticas en el repositorio (no hay framework de pruebas de JS); se verificó con
  Playwright (más de 160 comprobaciones: contrato, las seis respuestas, XSS, privacidad,
  movimiento reducido, accesibilidad, contraste, solapes y un recorrido completo contra el
  backend real con un Groq falso, incluido agendar una cita). Pendiente: el teclado
  virtual de iOS no se pudo comprobar

## Chatbot con IA (paso 5: listas, botones y solo consultar)
El asistente puede **solo consultar** (servicios u horarios) sin pedir nombre ni teléfono, y la
respuesta trae **botones** tocables que arma el servidor.

- **Solo consultar** (`resources/prompts/asistente.md`): lista ordenada en texto, sin pedir datos,
  y termina ofreciendo agendar; solo sigue el flujo de agendado si el paciente lo acepta o toca
  una hora. Servicios: siempre `listar_servicios` y lista todos en orden. Horarios sin servicio:
  primero el horario general y `listar_servicios`; con servicio y sin fecha, `ver_horarios_libres`
  **sin fecha**; con fecha, los horarios de ese día (todos, en orden; ya no "máximo 3")
- **`ver_horarios_libres`**: `fecha` es opcional. Sin fecha devuelve `proximos_dias` (hasta 5 días
  con lugar, buscados desde mañana en 14 días, por `DisponibilidadService`: respeta bloqueos y
  citas). `chat.agenda.max_horarios` bajó de 12 a 8 (un botón por hora)
- **Opciones** (`OpcionesChat`, `ContextoChat::$opciones`, `config/chat.php` clave `opciones`): la
  respuesta `ok:true` trae `opciones: [{texto, mensaje}]`, máx. **8**, generadas por el servidor
  desde los resultados de las herramientas, **nunca del modelo** (ni de sus argumentos ni de su
  texto). `listar_servicios` → botón por servicio ("Ver horarios de {servicio}"); días →
  "lun 16 nov" ("Ver horarios de {servicio} el lunes 16 de noviembre de 2026"); horas →
  "09:00 AM" ("Quiero agendar {servicio} el {fecha} a las 09:00 AM"). Textos pasan por
  `DatosParaPrompt` (una línea, sin marcas; texto ≤40 y mensaje ≤160 caracteres; lo que no cabe,
  parezca instrucción o se repita se descarta). Cada herramienta borra los botones al empezar:
  valen los de la **última** consulta de la petición; un error no deja botones. El resumen y la
  confirmación del agendado (texto del servidor) nunca llevan botones. Las opciones no se firman
  ni entran al historial (un historial con campos de más sigue dando 422)
- **Widget**: botones bajo el mensaje (`role="group"`, `x-text`, 44 px, mismo estilo de chips).
  Tocar uno llama a `enviar(mensaje)`: es un mensaje normal (CSRF, historial firmado, límites por
  IP y tope global; y el agendado sigue exigiendo el "sí" claro: un mensaje con números nunca
  confirma). Solo están activos los de la última respuesta: **cualquier mensaje más nuevo** (del
  visitante o un aviso) o una petición en curso los desactiva (siguen visibles). Chip inicial
  nuevo "Ver servicios". El JS descarta opciones mal formadas y recorta a 8
- **Pruebas**: `ChatOpcionesTest` (servicios, días, horas, topes, limpieza, instrucciones,
  consultar sin crear cita ni paciente, botones del servidor y no del modelo, sin botones en
  resumen/fallos, límites) y `ChatWidgetTest`. Verificado con mutaciones y en navegador
  (57 comprobaciones nuevas: render, 44 px, teclado Enter/Espacio, desactivado, XSS como texto,
  tope de 8, 320 px y recorrido real servicios → días → horas → resumen en 390 y 1440 px).
  Pendiente: validar con el modelo real que sigue las nuevas reglas del prompt

## Chatbot con IA (paso 3.5: horario general y promociones en las instrucciones)
El asistente conoce el horario de atención y las promociones vigentes, **sin herramientas
nuevas**: son bloques de solo lectura al final de las instrucciones
(`InstruccionesChat`, plantilla `resources/prompts/asistente.md`).

- **Orden del prompt, de lo más estable a lo más cambiante**: reglas → horario → servicios →
  promociones → "Hoy es …" (solo el día, **sin hora**). Groq cachea por **prefijo exacto**
  (`gpt-oss-120b` lo soporta; los tokens cacheados cuestan 50 % menos y **no cuentan contra el
  límite por minuto**; no está garantizado). Antes la hora iba en la tercera línea y cambiaba
  el prompt cada minuto: ahora es **idéntico durante todo el día** (dos peticiones iguales
  arman el mismo texto; al día siguiente cambia solo la última línea). Si algún día se agrega
  algo que cambie seguido, va al final. No se verificó con una key real que Groq acierte el
  caché: se puede comprobar con `usage.prompt_tokens_details.cached_tokens` de sus respuestas
- **Horario general** (`HorarioConsultorio`): sale de la tabla `horarios` (módulo Horarios
  del panel; la misma que usa `DisponibilidadService`), solo los bloques `disponible`, con los
  días consecutivos iguales juntos ("Lunes a viernes: 9:00 AM a 5:00 PM · Sábado: 9:00 AM a
  2:00 PM · Domingo: cerrado"). **No** del campo `contacto.horario` de la landing: ese es el
  horario *telefónico* y trae un valor de relleno (10am - 7pm) que contradiría las citas.
  Los bloqueos no entran (para un día concreto, `ver_horarios_libres`). Sin filas: "el
  horario de atención lo confirma el consultorio"
- **Promociones y avisos** (`PromocionesVigentes`): `Anuncio::vigentes()->ordenados()` (el mismo
  filtro y orden que el carrusel, con el "hoy" de `consultorio.zona_horaria`), máximo
  **5**; de cada uno tipo (Promoción/Aviso), título, descripción corta y "vigente hasta el
  dd/mm/aaaa" si tiene fecha final. Sin ninguno, no hay bloque y el asistente no menciona
  promociones. Peor caso medido: unos 1.350 caracteres (≈375 tokens); el horario, ≈25
- **Límites** (`config/chat.php`, clave `instrucciones`; .env `CHAT_PROMPT_ANUNCIOS_MAX`,
  `CHAT_PROMPT_TITULO_MAX`, `CHAT_PROMPT_DESCRIPCION_MAX`): **5 anuncios, título de 80 y
  descripción de 140 caracteres**, cortados en palabra completa con "…"
- **Los textos de la clínica son datos, no órdenes** (`DatosParaPrompt`, también usado por
  el catálogo de servicios y por `listar_servicios`): se limpian de invisibles y de
  caracteres de control; quedan en una sola línea, sin direcciones web ni marcas (`` ` ``,
  `< >`, `{ }`, `[ ]`, `|`, Markdown al inicio); y, si **parecen una instrucción** ("ignora
  las instrucciones anteriores", "a partir de ahora eres…", "actúa como", "sin preguntar",
  marcadores `system:` o `<|…|>`, nombres de herramientas, también con letras separadas), el
  elemento **se omite completo** (se revisa el texto entero, antes de recortarlo) y el
  siguiente ocupa su lugar: el prompt queda igual que si no existiera. El log solo dice
  "se omitió el anuncio N" (una vez por hora por elemento), nunca el texto. Frases legítimas
  parecidas ("Olvida el dolor…", "Desde ahora, 10 % de descuento") no se omiten. Cada bloque
  lleva un encabezado que dice que son datos y una regla fija que manda ignorar cualquier
  orden dentro de ellos
- **Reglas del asistente**: solo habla de las promociones del bloque y nunca inventa precios,
  descuentos, fechas ni condiciones; si preguntan por precios, por un tratamiento que no está
  en los servicios o por una promoción no listada, responde que eso lo confirma el
  consultorio; el horario es una referencia (para un día concreto usa `ver_horarios_libres`)
- **⚠️ Pendiente**: avisar **en el panel de anuncios** cuando un anuncio se omite del chat por
  parecer una instrucción. Hoy la doctora no lo ve (solo queda el aviso en el log) y su
  promoción simplemente no llega al asistente. Una opción: una marca en la lista de anuncios
  con la misma regla de `DatosParaPrompt::pareceInstruccion`
- **Pruebas**: `ChatInstruccionesDatosTest` (con y sin anuncios, caducado/programado/apagado,
  bordes de fecha y zona horaria, tope y orden, recortes, limpieza, textos maliciosos que no
  cambian el prompt, el log sin texto, horario, orden del prompt y estabilidad del prompt
  entre peticiones). Se verificó quitando a propósito cada defensa: cada una hace fallar pruebas

## Pendiente técnico
- `citas.horario_id` tiene `onDelete cascade`: eliminar un horario semanal
  borra sus citas. Cambiarlo a restrict/null o impedir borrar horarios con
  citas
- Avisar en el panel de anuncios cuando uno se omite del chat por parecer una
  instrucción (ver "Chatbot con IA (paso 3.5)")

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
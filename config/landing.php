<?php

/**
 * Catálogo de lo que la doctora puede editar en la landing page.
 *
 * Aquí viven los VALORES ORIGINALES (los que se muestran mientras no cambie nada),
 * las etiquetas y las reglas de cada campo. La base de datos (tabla landing_contenidos)
 * solo guarda lo que ella personalizó. Para hacer editable algo nuevo: agregar el
 * campo aquí y leerlo en la vista con $landing->texto('seccion.campo') (o ->imagen()).
 *
 * Tipos de campo:
 *   texto     una línea                     (max)
 *   titulo    hasta 2 líneas                (max)
 *   parrafo   varias líneas de texto plano  (max)
 *   imagen    imagen subida                 (defecto: ruta dentro de public/, ideal: tamaño sugerido)
 *   telefono  10 dígitos, México
 *   red       enlace a una red social       (hosts: dominios permitidos)
 *   mapa      mapa de Google Maps para insertar
 *
 * Las claves van en minúsculas y sin espacios: "seccion.campo".
 */
return [

    // Título de cada sección y pestaña del panel en la que se edita
    'secciones' => [
        'hero' => ['titulo' => 'Portada', 'pestana' => 'contenido'],
        'nosotros' => ['titulo' => 'Nosotros: misión y visión', 'pestana' => 'contenido'],
        'certificados' => ['titulo' => 'Certificados', 'pestana' => 'contenido'],
        'agendar' => ['titulo' => 'Franja final para agendar', 'pestana' => 'contenido'],
        'contacto' => ['titulo' => 'Contacto', 'pestana' => 'contacto'],
        'pie' => ['titulo' => 'Pie de página', 'pestana' => 'contacto'],
        'galeria' => ['titulo' => 'Título de la galería', 'pestana' => 'galeria'],
    ],

    'campos' => [

        // ---------- Portada ----------
        'hero.imagen' => [
            'tipo' => 'imagen', 'etiqueta' => 'Imagen de portada',
            'defecto' => 'img/image 1.png', 'ideal' => '1160 × 846 px (horizontal 4:3). Solo se ve en pantallas grandes.',
        ],
        'hero.etiqueta' => ['tipo' => 'texto', 'etiqueta' => 'Frase superior', 'defecto' => 'SONRISAS QUE ENAMORAN', 'max' => 60],
        'hero.titulo' => ['tipo' => 'titulo', 'etiqueta' => 'Título (hasta 2 líneas)', 'defecto' => "TU MEJOR SONRISA\nCOMIENZA AQUI", 'max' => 120],
        'hero.descripcion' => [
            'tipo' => 'parrafo', 'etiqueta' => 'Descripción',
            'defecto' => 'Tecnología de vanguardia y un trato humano para transformar tu salud dental sin dolor y con resultados increíbles.',
            'max' => 300,
        ],
        'hero.boton' => ['tipo' => 'texto', 'etiqueta' => 'Texto del botón', 'defecto' => 'AGENDAR CITA', 'max' => 30],

        // ---------- Nosotros ----------
        'nosotros.mision_texto' => [
            'tipo' => 'parrafo', 'etiqueta' => 'Texto de la misión',
            'defecto' => 'Cuidar y transformar sonrisas a través de servicios odontológicos confiables, éticos y accesibles. Trabajamos con tecnología actual y un trato humano para que cada paciente viva una experiencia segura.',
            'max' => 500,
        ],
        'nosotros.mision_imagen' => [
            'tipo' => 'imagen', 'etiqueta' => 'Imagen de la misión',
            'defecto' => 'img/mision.png', 'ideal' => '980 × 640 px (horizontal). Se recorta al mostrarla.',
        ],
        'nosotros.vision_texto' => [
            'tipo' => 'parrafo', 'etiqueta' => 'Texto de la visión',
            'defecto' => 'Ser la consultoría dental más confiable y cercana, reconocida por innovar, educar y acompañar a cada persona en el camino hacia una salud bucal plena.',
            'max' => 500,
        ],
        'nosotros.vision_imagen' => [
            'tipo' => 'imagen', 'etiqueta' => 'Imagen de la visión',
            'defecto' => 'img/vision.png', 'ideal' => '980 × 640 px (horizontal). Se recorta al mostrarla.',
        ],

        // ---------- Certificados ----------
        'certificados.imagen' => [
            'tipo' => 'imagen', 'etiqueta' => 'Imagen del certificado',
            'defecto' => 'img/certificado.png', 'ideal' => 'Al menos 760 px de ancho; se muestra completa, sin recortar.',
        ],
        'certificados.texto' => [
            'tipo' => 'parrafo', 'etiqueta' => 'Texto bajo el certificado',
            'defecto' => 'Certificada por la Asociación Dental Mexicana (ADM) y Especialidad en Ortodoncia.',
            'max' => 300,
        ],

        // ---------- Franja final para agendar (botones fijos: crear cuenta y agendar) ----------
        'agendar.titulo' => ['tipo' => 'titulo', 'etiqueta' => 'Título (hasta 2 líneas)', 'defecto' => '¿Listo para tu próxima cita?', 'max' => 80],
        'agendar.texto' => [
            'tipo' => 'parrafo', 'etiqueta' => 'Texto',
            'defecto' => 'Crea tu cuenta y agenda en línea: eliges el servicio, el día y la hora que te queden mejor.',
            'max' => 200,
        ],

        // ---------- Contacto ----------
        'contacto.telefono' => [
            'tipo' => 'telefono', 'etiqueta' => 'Teléfono para llamadas', 'defecto' => '9991231234',
            'ayuda' => '10 dígitos, sin lada internacional.',
        ],
        'contacto.horario' => [
            'tipo' => 'texto', 'etiqueta' => 'Horario de atención telefónica', 'defecto' => '10am - 7pm', 'max' => 40,
            'ayuda' => 'Aparece como "Horario: …".',
        ],
        'contacto.whatsapp' => [
            'tipo' => 'telefono', 'etiqueta' => 'WhatsApp', 'defecto' => '9991231234',
            'ayuda' => '10 dígitos. Puede ser el mismo teléfono.',
        ],
        'contacto.direccion1' => ['tipo' => 'texto', 'etiqueta' => 'Dirección (línea 1)', 'defecto' => 'Plaza Marsella, Local 1', 'max' => 80],
        'contacto.direccion2' => ['tipo' => 'texto', 'etiqueta' => 'Dirección (línea 2)', 'defecto' => 'Av. Cámara de Comercio, Mérida, Yucatán', 'max' => 120],
        'contacto.mapa' => [
            'tipo' => 'mapa', 'etiqueta' => 'Mapa',
            'defecto' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.5!2d-89.6!3d20.97!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjDCsDU4JzEyLjAiTiA4OcKwMzYnMDAuMCJX!5e0!3m2!1ses!2smx!4v1234567890',
            'ayuda' => 'En Google Maps: buscar el consultorio → Compartir → Insertar un mapa → Copiar HTML, y pegarlo aquí completo (o solo la dirección que empieza con https://www.google.com/maps/embed).',
        ],
        'contacto.instagram' => [
            'tipo' => 'red', 'etiqueta' => 'Instagram', 'defecto' => '#', 'hosts' => ['instagram.com'],
            'ayuda' => 'Dirección de tu perfil, por ejemplo https://www.instagram.com/tuusuario',
        ],
        'contacto.facebook' => [
            'tipo' => 'red', 'etiqueta' => 'Facebook', 'defecto' => '#', 'hosts' => ['facebook.com', 'fb.com', 'fb.me'],
            'ayuda' => 'Dirección de tu página, por ejemplo https://www.facebook.com/tupagina',
        ],

        // ---------- Pie de página ----------
        // ---------- Galería (el título; las fotos son una lista, ver 'colecciones') ----------
        'galeria.titulo' => ['tipo' => 'texto', 'etiqueta' => 'Título de la sección', 'defecto' => 'TRASFORMACIONES REALES', 'max' => 60],

        'pie.derechos' => ['tipo' => 'texto', 'etiqueta' => 'Línea de derechos', 'defecto' => '© 2026 Melissa López N. Todos los derechos reservados.', 'max' => 120],
        'pie.ubicacion' => ['tipo' => 'texto', 'etiqueta' => 'Línea de ubicación', 'defecto' => 'Odontología Integral — Mérida, Yucatán', 'max' => 120],
    ],

    /*
     * Listas administrables (agregar, quitar y reordenar elementos), guardadas en
     * landing_items (LandingColeccionService). "imagenes" dice si cada elemento lleva
     * imagen (por defecto sí). Los campos son de tipo "texto" (por defecto),
     * "calificacion" (1 a 5) o "casilla" (sí/no). "textos" son los avisos de máximo y
     * mínimo (:maximo se reemplaza). "originales" son los elementos que trae la
     * landing hoy: se muestran mientras la lista no se toque y "Restaurar" los devuelve.
     * Las claves de los originales no se pueden repetir ni cambiar una vez publicadas.
     */
    'colecciones' => [
        'galeria' => [
            'titulo' => 'Fotos de la galería',
            'minimo' => 1,
            'maximo' => 12,
            'ideal' => '900 × 900 px (cuadrada; se recorta al mostrarla).',
            'textos' => [
                'maximo' => 'La galería puede tener como máximo :maximo fotos. Quita alguna para agregar otra.',
                'minimo' => 'La galería debe tener al menos una foto. Agrega otra antes de quitar esta.',
            ],
            'campos' => [
                'titulo' => ['etiqueta' => 'Título', 'max' => 60],
                'subtitulo' => ['etiqueta' => 'Subtítulo', 'max' => 80],
            ],
            'originales' => [
                'galeria-1' => ['imagen' => 'img/galeria-1.png', 'titulo' => 'Diseño de Sonrisa', 'subtitulo' => 'Tratamiento de carillas'],
                'galeria-2' => ['imagen' => 'img/galeria-2.png', 'titulo' => 'Ortodoncia', 'subtitulo' => 'Brackets y alineadores'],
                'galeria-3' => ['imagen' => 'img/galeria-3.png', 'titulo' => 'Blanqueamiento', 'subtitulo' => 'Resultados visibles'],
                'galeria-4' => ['imagen' => 'img/galeria-4.png', 'titulo' => 'Implantes', 'subtitulo' => 'Solución permanente'],
                'galeria-5' => ['imagen' => 'img/galeria-5.png', 'titulo' => 'Limpieza Dental', 'subtitulo' => 'Profunda y sin dolor'],
            ],
        ],

        // Opiniones reales de pacientes (#testimonios). Sin fotos y sin originales: la
        // sección solo aparece si hay opiniones con la autorización marcada
        'testimonios' => [
            'titulo' => 'Opiniones de pacientes',
            'imagenes' => false,
            'minimo' => 0,
            'maximo' => 12,
            'textos' => [
                'maximo' => 'Puede haber como máximo :maximo opiniones. Quita alguna para agregar otra.',
                'minimo' => 'No se puede quitar esta opinión.',
            ],
            'campos' => [
                'nombre' => ['etiqueta' => 'Nombre', 'max' => 40, 'requerido' => true,
                    'ayuda' => 'Puede ser solo el nombre y la inicial del apellido (por ejemplo, "María G.").'],
                'texto' => ['etiqueta' => 'Opinión', 'max' => 400, 'requerido' => true],
                'calificacion' => ['tipo' => 'calificacion', 'etiqueta' => 'Calificación'],
                'autorizado' => ['tipo' => 'casilla', 'etiqueta' => 'El paciente autorizó publicar esta opinión', 'obligatoria' => true],
            ],
            'originales' => [],
        ],
    ],
];

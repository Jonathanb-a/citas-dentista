import './bootstrap';
import Alpine from 'alpinejs'
import carruselAnuncios from './carrusel-anuncios'
import galeriaLightbox from './galeria-lightbox'
import iniciarAparecer from './aparecer'

window.Alpine = Alpine
Alpine.data('carruselAnuncios', carruselAnuncios)
Alpine.data('galeriaLightbox', galeriaLightbox)
Alpine.start()

iniciarAparecer()

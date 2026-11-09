import './bootstrap';
import Alpine from 'alpinejs'
import carruselAnuncios from './carrusel-anuncios'
import galeriaLightbox from './galeria-lightbox'
import chatWidget from './chat-widget'
import iniciarAparecer from './aparecer'

window.Alpine = Alpine
Alpine.data('carruselAnuncios', carruselAnuncios)
Alpine.data('galeriaLightbox', galeriaLightbox)
Alpine.data('chatWidget', chatWidget)
Alpine.start()

iniciarAparecer()

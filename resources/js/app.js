import './bootstrap';
import Alpine from 'alpinejs'
import carruselAnuncios from './carrusel-anuncios'

window.Alpine = Alpine
Alpine.data('carruselAnuncios', carruselAnuncios)
Alpine.start()

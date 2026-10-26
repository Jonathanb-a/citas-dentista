import './bootstrap';
import Alpine from 'alpinejs'
import carruselAnuncios from './carrusel-anuncios'
import iniciarAparecer from './aparecer'

window.Alpine = Alpine
Alpine.data('carruselAnuncios', carruselAnuncios)
Alpine.start()

iniciarAparecer()

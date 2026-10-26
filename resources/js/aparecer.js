// Elementos con data-aparecer que entran con una animación al llegar a ellos con el scroll.
// Si el navegador pide reducir movimiento o no tiene IntersectionObserver, no hace nada
// y todo queda visible como siempre.
export default function iniciarAparecer() {
    const elementos = [...document.querySelectorAll('[data-aparecer]')]
    const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (elementos.length === 0 || reducir || !('IntersectionObserver' in window)) {
        return
    }

    // Lo que ya se ve al cargar no se esconde: evita un parpadeo en la parte de arriba
    const alto = window.innerHeight
    const pendientes = elementos.filter((elemento) => elemento.getBoundingClientRect().top >= alto)

    const observador = new IntersectionObserver((entradas) => {
        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) {
                return
            }
            entrada.target.dataset.aparecer = 'animar'
            observador.unobserve(entrada.target)
        })
    }, { rootMargin: '0px 0px -10% 0px' })

    pendientes.forEach((elemento) => {
        elemento.dataset.aparecer = 'oculto'
        observador.observe(elemento)
    })
}

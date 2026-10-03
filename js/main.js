// Comportamientos comunes a todas las páginas: menú mobile, interruptor de tema, header,
// aparición al hacer scroll, contadores y videos de YouTube.

// ---------- Header al desplazarse ----------
// Suma una sombra al header cuando la página ya no está arriba de todo.
function iniciarHeader() {
  const header = document.querySelector('.header');
  if (!header) return;
  let pendiente = false;
  const actualizar = () => {
    header.classList.toggle('header--desplazado', window.scrollY > 8);
    pendiente = false;
  };
  window.addEventListener('scroll', () => {
    if (!pendiente) {
      pendiente = true;
      requestAnimationFrame(actualizar);
    }
  }, { passive: true });
  actualizar();
}

// ---------- Aparición al hacer scroll ----------
// Los elementos con la clase "revelar" aparecen con un movimiento suave al entrar en pantalla.
// La clase .revelado-activo recién se agrega acá: si este archivo falla, todo queda visible.
function iniciarRevelado() {
  const elementos = document.querySelectorAll('.revelar');
  const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!elementos.length || reducirMovimiento || !('IntersectionObserver' in window)) return;

  document.documentElement.classList.add('revelado-activo');
  const observador = new IntersectionObserver((entradas) => {
    entradas.forEach((entrada) => {
      if (!entrada.isIntersecting) return;
      entrada.target.classList.add('es-visible');
      observador.unobserve(entrada.target);
    });
  }, { rootMargin: '0px 0px -10% 0px' });

  elementos.forEach((elemento) => observador.observe(elemento));
}

// ---------- Menú mobile ----------
// Botón con aria-expanded; Escape cierra y devuelve el foco al botón.
function iniciarMenu() {
  const boton = document.querySelector('.boton-menu');
  const panel = boton && document.getElementById(boton.getAttribute('aria-controls'));
  if (!panel) return;

  const estaAbierto = () => boton.getAttribute('aria-expanded') === 'true';
  const cambiarMenu = (abrir) => {
    boton.setAttribute('aria-expanded', String(abrir));
    panel.classList.toggle('esta-abierto', abrir);
  };

  boton.addEventListener('click', () => cambiarMenu(!estaAbierto()));

  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && estaAbierto()) {
      cambiarMenu(false);
      boton.focus();
    }
  });

  // Al elegir un link del menú, se cierra
  panel.addEventListener('click', (evento) => {
    if (evento.target.closest('a')) cambiarMenu(false);
  });

  // En escritorio el menú siempre está visible: se resetea el estado mobile
  window.matchMedia('(min-width: 1024px)').addEventListener('change', (consulta) => {
    if (consulta.matches) cambiarMenu(false);
  });
}

// ---------- Tema claro / oscuro ----------
// El botón muestra el modo al que lleva: en oscuro dice "Modo claro" (con un sol) y en claro
// dice "Modo oscuro" (con una luna). Ese texto es también su nombre para lectores de pantalla.
// La elección se guarda para las demás páginas.
function iniciarTema() {
  const boton = document.querySelector('.boton-tema');
  if (!boton) return;

  const raiz = document.documentElement;
  const sistemaOscuro = window.matchMedia('(prefers-color-scheme: dark)');
  const temaActual = () => raiz.dataset.tema || (sistemaOscuro.matches ? 'oscuro' : 'claro');
  const texto = boton.querySelector('.boton-tema__texto');
  const luna = boton.querySelector('.icono-luna');
  const sol = boton.querySelector('.icono-sol');
  const actualizarBoton = () => {
    const esOscuro = temaActual() === 'oscuro';
    if (texto) texto.textContent = esOscuro ? 'Modo claro' : 'Modo oscuro';
    // Los íconos son SVG: no tienen la propiedad .hidden, por eso se usa el atributo
    luna?.toggleAttribute('hidden', esOscuro);
    sol?.toggleAttribute('hidden', !esOscuro);
  };

  boton.hidden = false;
  actualizarBoton();

  boton.addEventListener('click', () => {
    const nuevoTema = temaActual() === 'oscuro' ? 'claro' : 'oscuro';
    raiz.dataset.tema = nuevoTema;
    try {
      localStorage.setItem('tema', nuevoTema);
    } catch (error) {
      // Si no se puede guardar, el cambio vale solo para esta página
    }
    actualizarBoton();
  });

  sistemaOscuro.addEventListener('change', actualizarBoton);
}

// ---------- Contadores ("+100 asesorados") ----------
// El número final ya está en el HTML. La animación es solo visual (el elemento animado tiene
// aria-hidden) y no se ejecuta si la persona pidió reducir el movimiento.
function iniciarContadores() {
  const contadores = document.querySelectorAll('[data-contador]');
  const reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!contadores.length || reducirMovimiento || !('IntersectionObserver' in window)) return;

  const observador = new IntersectionObserver((entradas) => {
    entradas.forEach((entrada) => {
      if (!entrada.isIntersecting) return;
      animarContador(entrada.target);
      observador.unobserve(entrada.target);
    });
  }, { threshold: 0.5 });

  contadores.forEach((contador) => {
    contador.textContent = `${contador.dataset.prefijo || ''}0`;
    observador.observe(contador);
  });
}

function animarContador(elemento) {
  const valorFinal = Number(elemento.dataset.contador);
  const prefijo = elemento.dataset.prefijo || '';
  const duracion = 1200;
  let inicio = null;

  const paso = (tiempo) => {
    if (inicio === null) inicio = tiempo;
    const progreso = Math.min((tiempo - inicio) / duracion, 1);
    const suavizado = 1 - Math.pow(1 - progreso, 3);
    elemento.textContent = `${prefijo}${Math.round(valorFinal * suavizado)}`;
    if (progreso < 1) requestAnimationFrame(paso);
  };

  requestAnimationFrame(paso);
}

// ---------- Videos de YouTube ----------
// Cada video es un link a YouTube con su miniatura (funciona sin JavaScript). Con JavaScript,
// al tocarlo se reemplaza por el reproductor en la misma página: así los reproductores, que son
// pesados, solo se cargan si alguien quiere ver el video.
function iniciarVideos() {
  document.querySelectorAll('[data-video]').forEach((enlace) => {
    enlace.addEventListener('click', (evento) => {
      evento.preventDefault();
      const id = enlace.dataset.video;
      const titulo = enlace.querySelector('.video__titulo').textContent.trim();

      const reproductor = document.createElement('div');
      reproductor.className = 'video__reproductor';
      const iframe = document.createElement('iframe');
      iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?autoplay=1&rel=0`;
      iframe.title = `Video de YouTube: ${titulo}`;
      iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      iframe.allowFullscreen = true;
      const nombre = document.createElement('p');
      nombre.className = 'video__titulo';
      nombre.textContent = titulo;

      reproductor.append(iframe, nombre);
      enlace.replaceWith(reproductor);
      iframe.focus();
    });
  });
}

iniciarMenu();
iniciarTema();
iniciarHeader();
iniciarRevelado();
iniciarContadores();
iniciarVideos();

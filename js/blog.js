// Carga las tarjetas del blog (últimas entradas en la home y listado completo en blog/index.html).
// Las entradas salen de la base de datos a través de api/entradas.php (solo las publicadas).
// La página de cada entrada no se arma acá: la arma PHP en blog/entrada.php (mejor para Google y las redes).
// Todo el contenido se inserta con textContent/createElement (nunca innerHTML) para evitar
// que un texto cargado en el panel pueda inyectar código.

const FUENTE_ENTRADAS = 'api/entradas.php'; // desde la raíz del sitio
const CANAL_YOUTUBE = 'https://www.youtube.com/@doblebarra.strength';

// ---------- Lectura de datos ----------
async function obtenerEntradas(raiz, cantidad) {
  const direccion = raiz + FUENTE_ENTRADAS + (cantidad ? `?cantidad=${cantidad}` : '');
  const respuesta = await fetch(direccion, { headers: { Accept: 'application/json' } });
  if (!respuesta.ok) throw new Error(`La API respondió ${respuesta.status}`);
  return (await respuesta.json()).entradas;
}

// ---------- Ayudas ----------
function crearElemento(etiqueta, clase, texto) {
  const elemento = document.createElement(etiqueta);
  if (clase) elemento.className = clase;
  if (texto) elemento.textContent = texto;
  return elemento;
}

// Fechas "AAAA-MM-DD" → "28 de septiembre de 2026"
function crearFecha(fecha) {
  const tiempo = document.createElement('time');
  tiempo.dateTime = fecha;
  tiempo.textContent = new Intl.DateTimeFormat('es-AR', {
    day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC',
  }).format(new Date(`${fecha}T00:00:00Z`));
  return tiempo;
}

// Las rutas de la API son relativas a la raíz del sitio: se les agrega la de la página actual
function crearImagen(imagen, raiz, { clase, sizes, alt, carga }) {
  const elemento = crearElemento('img', clase);
  elemento.src = raiz + imagen.local;
  if (imagen.srcset) {
    elemento.srcset = imagen.srcset.split(', ').map((opcion) => raiz + opcion).join(', ');
    elemento.sizes = sizes;
  }
  elemento.width = imagen.ancho;
  elemento.height = imagen.alto;
  elemento.alt = alt;
  elemento.loading = carga;
  elemento.decoding = 'async';
  return elemento;
}

// ---------- Tarjetas (home y listado) ----------
function crearTarjeta(entrada, { rutaEntrada, nivelTitulo, raiz, carga }) {
  const item = document.createElement('li');
  const tarjeta = crearElemento('article', 'entrada-tarjeta');

  // En la tarjeta la imagen acompaña al título: alt vacío para no repetir información
  if (entrada.imagen?.local) {
    tarjeta.append(crearImagen(entrada.imagen, raiz, {
      clase: 'entrada-tarjeta__imagen',
      sizes: '(min-width: 1024px) 22rem, (min-width: 768px) 45vw, 100vw',
      alt: '',
      carga,
    }));
  }

  const cuerpo = crearElemento('div', 'entrada-tarjeta__cuerpo');
  const titulo = crearElemento(`h${nivelTitulo}`, 'entrada-tarjeta__titulo');
  const enlace = crearElemento('a', 'entrada-tarjeta__enlace', entrada.titulo);
  enlace.href = `${rutaEntrada}?slug=${encodeURIComponent(entrada.slug)}`;
  titulo.append(enlace);

  const meta = crearElemento('p', 'entrada-tarjeta__meta', `Por ${entrada.autor} · `);
  meta.append(crearFecha(entrada.fecha));

  cuerpo.append(titulo, meta);
  if (entrada.resumen) cuerpo.append(crearElemento('p', '', entrada.resumen));
  tarjeta.append(cuerpo);
  item.append(tarjeta);
  return item;
}

// Aviso cuando todavía no hay entradas publicadas
function crearAvisoVacio() {
  const aviso = crearElemento('div', 'blog-vacio');
  aviso.append(
    crearElemento('p', 'blog-vacio__titulo', 'Muy pronto'),
    crearElemento('p', '', 'Pronto vas a encontrar acá artículos sobre powerlifting escritos por el equipo.'),
  );
  const enlace = crearElemento('a', 'enlace-flecha', 'Mientras tanto, mirá nuestro canal de YouTube');
  enlace.href = CANAL_YOUTUBE;
  enlace.target = '_blank';
  enlace.rel = 'noopener noreferrer';
  enlace.append(crearElemento('span', 'oculto-accesible', ' (se abre en una pestaña nueva)'));
  aviso.append(enlace);
  return aviso;
}

async function mostrarLista(lista) {
  const raiz = document.body.dataset.raiz || '';
  const estado = lista.parentElement.querySelector('[data-blog-estado]');
  const verTodas = lista.closest('section')?.querySelector('[data-blog-ver-todas]');
  const cantidad = Number(lista.dataset.cantidad) || 0;
  // Las primeras tarjetas de una lista que se ve al abrir la página no esperan al scroll
  const cargaInmediata = Number(lista.dataset.cargaInmediata) || 0;

  lista.setAttribute('aria-busy', 'true');
  estado.textContent = 'Cargando entradas…';

  try {
    const entradas = await obtenerEntradas(raiz, cantidad);
    if (entradas.length) {
      estado.replaceChildren();
    } else {
      estado.replaceChildren(crearAvisoVacio());
      if (verTodas) verTodas.hidden = true;
    }
    lista.replaceChildren(...entradas.map((entrada, posicion) => crearTarjeta(entrada, {
      rutaEntrada: lista.dataset.rutaEntrada,
      nivelTitulo: Number(lista.dataset.nivelTitulo) || 3,
      raiz,
      carga: posicion < cargaInmediata ? 'eager' : 'lazy',
    })));
  } catch (error) {
    console.error('Error al cargar el blog:', error);
    estado.textContent = 'No pudimos cargar las entradas del blog. Probá de nuevo en unos minutos.';
    if (verTodas) verTodas.hidden = true;
  } finally {
    lista.setAttribute('aria-busy', 'false');
    lista.dataset.cargada = 'true';
  }
}

document.querySelectorAll('[data-blog="lista"]').forEach(mostrarLista);

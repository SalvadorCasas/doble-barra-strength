// Carga y muestra las entradas del blog desde Sanity (CMS headless).
// Mientras SANITY.proyecto esté vacío no hay entradas: se muestra el aviso "Muy pronto".
// Para probar el diseño con las entradas de ejemplo, agregar ?ejemplos a la dirección
// (por ejemplo, index.html?ejemplos o blog/index.html?ejemplos).
// Todo el contenido se inserta con textContent/createElement (nunca innerHTML) para evitar
// que un texto del CMS pueda inyectar código.

const SANITY = {
  proyecto: '', // COMPLETAR: ID del proyecto de Sanity (se ve en sanity.io/manage)
  dataset: 'production',
  versionApi: 'v2025-02-19',
};

const CAMPOS_TARJETA = 'titulo, "slug": slug.current, fecha, autor, resumen, imagen{alt, "ref": asset._ref}';
const CANAL_YOUTUBE = 'https://www.youtube.com/@doblebarra.strength';
const MOSTRAR_EJEMPLOS = !SANITY.proyecto && new URLSearchParams(window.location.search).has('ejemplos');

// ---------- Entradas de ejemplo (solo con ?ejemplos y sin Sanity configurado) ----------
// Tienen la misma forma que las de Sanity, así el diseño se prueba con datos realistas.
const parrafoEjemplo = (texto, extra = {}) => ({
  _type: 'block',
  style: 'normal',
  markDefs: [],
  children: [{ _type: 'span', text: texto, marks: [] }],
  ...extra,
});

const CUERPO_EJEMPLO = [
  parrafoEjemplo('[EJEMPLO] Este es un texto de ejemplo para ver cómo se leen los párrafos del blog. El contenido real lo cargan Ivan, Luca u otros administradores desde el panel de Sanity.'),
  parrafoEjemplo('Subtítulo de ejemplo', { style: 'h2' }),
  {
    _type: 'block',
    style: 'normal',
    markDefs: [{ _key: 'enlace1', _type: 'link', href: 'https://www.youtube.com/@doblebarra.strength' }],
    children: [
      { _type: 'span', text: 'Los párrafos pueden tener ', marks: [] },
      { _type: 'span', text: 'texto destacado', marks: ['strong'] },
      { _type: 'span', text: ', ', marks: [] },
      { _type: 'span', text: 'texto en cursiva', marks: ['em'] },
      { _type: 'span', text: ' y links, por ejemplo al ', marks: [] },
      { _type: 'span', text: 'canal de YouTube del equipo', marks: ['enlace1'] },
      { _type: 'span', text: '.', marks: [] },
    ],
  },
  parrafoEjemplo('Primer punto de una lista de ejemplo', { listItem: 'bullet', level: 1 }),
  parrafoEjemplo('Segundo punto de una lista de ejemplo', { listItem: 'bullet', level: 1 }),
  parrafoEjemplo('Otro subtítulo de ejemplo', { style: 'h2' }),
  parrafoEjemplo('Paso uno de una lista numerada', { listItem: 'number', level: 1 }),
  parrafoEjemplo('Paso dos de una lista numerada', { listItem: 'number', level: 1 }),
  parrafoEjemplo('[EJEMPLO] Una cita destacada se ve así.', { style: 'blockquote' }),
];

const ENTRADAS_EJEMPLO = [
  {
    titulo: '[EJEMPLO] Título de una entrada sobre técnica',
    slug: 'ejemplo-1',
    fecha: '2026-09-28',
    autor: 'Ivan Casas',
    resumen: '[EJEMPLO] Resumen breve de la entrada para mostrar en las tarjetas. Lo escribe el autor al publicarla.',
    imagen: { local: 'img/placeholders/placeholder-entrada-1.svg', ancho: 1200, alto: 675, alt: '[EJEMPLO] Descripción de la imagen principal' },
    cuerpo: CUERPO_EJEMPLO,
  },
  {
    titulo: '[EJEMPLO] Título de una entrada sobre planificación',
    slug: 'ejemplo-2',
    fecha: '2026-09-14',
    autor: 'Luca Bettinalio',
    resumen: '[EJEMPLO] Resumen breve de la entrada para mostrar en las tarjetas. Lo escribe el autor al publicarla.',
    imagen: { local: 'img/placeholders/placeholder-entrada-2.svg', ancho: 1200, alto: 675, alt: '[EJEMPLO] Descripción de la imagen principal' },
    cuerpo: CUERPO_EJEMPLO,
  },
  {
    titulo: '[EJEMPLO] Título de una entrada sobre competencia',
    slug: 'ejemplo-3',
    fecha: '2026-08-30',
    autor: 'Ivan Casas',
    resumen: '[EJEMPLO] Resumen breve de la entrada para mostrar en las tarjetas. Lo escribe el autor al publicarla.',
    imagen: { local: 'img/placeholders/placeholder-entrada-3.svg', ancho: 1200, alto: 675, alt: '[EJEMPLO] Descripción de la imagen principal' },
    cuerpo: CUERPO_EJEMPLO,
  },
];

// ---------- Lectura de datos ----------
async function consultarSanity(consulta, parametros = {}) {
  const url = new URL(`https://${SANITY.proyecto}.apicdn.sanity.io/${SANITY.versionApi}/data/query/${SANITY.dataset}`);
  url.searchParams.set('query', consulta);
  Object.entries(parametros).forEach(([nombre, valor]) => {
    url.searchParams.set(`$${nombre}`, JSON.stringify(valor));
  });

  const respuesta = await fetch(url);
  if (!respuesta.ok) throw new Error(`Sanity respondió ${respuesta.status}`);
  const datos = await respuesta.json();
  return datos.result;
}

async function obtenerEntradas(cantidad) {
  if (!SANITY.proyecto) {
    if (!MOSTRAR_EJEMPLOS) return [];
    return cantidad ? ENTRADAS_EJEMPLO.slice(0, cantidad) : ENTRADAS_EJEMPLO;
  }
  const limite = cantidad ? `[0...${cantidad}]` : '';
  return consultarSanity(`*[_type == "entrada" && defined(slug.current)] | order(fecha desc)${limite}{${CAMPOS_TARJETA}}`);
}

async function obtenerEntrada(slug) {
  if (!SANITY.proyecto) {
    return MOSTRAR_EJEMPLOS ? ENTRADAS_EJEMPLO.find((entrada) => entrada.slug === slug) || null : null;
  }
  return consultarSanity(`*[_type == "entrada" && slug.current == $slug][0]{${CAMPOS_TARJETA}, cuerpo}`, { slug });
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

// Devuelve src, srcset y medidas de una imagen local (ejemplo) o de Sanity.
// Sanity entrega WebP y el ancho pedido directamente desde la URL (?w=…&auto=format).
function datosImagen(imagen, raiz) {
  if (!imagen) return null;
  if (imagen.local) {
    return { src: raiz + imagen.local, ancho: imagen.ancho, alto: imagen.alto, alt: imagen.alt || '' };
  }
  if (!imagen.ref) return null;

  // Formato de la referencia: image-<id>-<ancho>x<alto>-<formato>
  const [, id, medidas, formato] = imagen.ref.split('-');
  const [ancho, alto] = medidas.split('x').map(Number);
  const base = `https://cdn.sanity.io/images/${SANITY.proyecto}/${SANITY.dataset}/${id}-${medidas}.${formato}`;
  const anchos = [400, 800, 1200, 1600].filter((medida) => medida <= ancho);
  if (!anchos.length) anchos.push(ancho);

  return {
    src: `${base}?w=${Math.min(800, ancho)}&auto=format`,
    srcset: anchos.map((medida) => `${base}?w=${medida}&auto=format ${medida}w`).join(', '),
    ancho,
    alto,
    alt: imagen.alt || '',
  };
}

function crearImagen(datos, { clase, sizes, alt = datos.alt, carga = 'lazy' }) {
  const imagen = crearElemento('img', clase);
  imagen.src = datos.src;
  if (datos.srcset) {
    imagen.srcset = datos.srcset;
    imagen.sizes = sizes;
  }
  imagen.width = datos.ancho;
  imagen.height = datos.alto;
  imagen.alt = alt;
  imagen.loading = carga;
  imagen.decoding = 'async';
  return imagen;
}

// Solo se permiten links web, de mail o de teléfono
function esUrlSegura(direccion) {
  try {
    return ['http:', 'https:', 'mailto:', 'tel:'].includes(new URL(direccion, window.location.href).protocol);
  } catch (error) {
    return false;
  }
}

function crearEnlace(direccion) {
  const enlace = crearElemento('a');
  enlace.href = direccion;
  const url = new URL(direccion, window.location.href);
  if (url.protocol.startsWith('http') && url.hostname !== window.location.hostname) {
    enlace.target = '_blank';
    enlace.rel = 'noopener noreferrer';
    enlace.dataset.externo = 'true';
  }
  return enlace;
}

// ---------- Texto enriquecido de Sanity (Portable Text) → HTML ----------
function agregarTexto(contenedor, bloque) {
  const definiciones = Object.fromEntries((bloque.markDefs || []).map((definicion) => [definicion._key, definicion]));

  (bloque.children || []).forEach((parte) => {
    let nodo = document.createTextNode(parte.text || '');
    (parte.marks || []).forEach((marca) => {
      let envoltorio = null;
      if (marca === 'strong') envoltorio = document.createElement('strong');
      else if (marca === 'em') envoltorio = document.createElement('em');
      else if (definiciones[marca]?._type === 'link' && esUrlSegura(definiciones[marca].href)) {
        envoltorio = crearEnlace(definiciones[marca].href);
      }
      if (envoltorio) {
        envoltorio.append(nodo);
        nodo = envoltorio;
      }
    });
    contenedor.append(nodo);
  });

  // Los links externos avisan que se abren en otra pestaña
  contenedor.querySelectorAll('a[data-externo]').forEach((enlace) => {
    enlace.append(crearElemento('span', 'oculto-accesible', ' (se abre en una pestaña nueva)'));
  });
}

function crearCuerpo(bloques, raiz) {
  const fragmento = document.createDocumentFragment();
  let lista = null;

  (bloques || []).forEach((bloque) => {
    if (bloque._type === 'block' && bloque.listItem) {
      const etiquetaLista = bloque.listItem === 'number' ? 'OL' : 'UL';
      if (!lista || lista.tagName !== etiquetaLista) {
        lista = document.createElement(etiquetaLista);
        fragmento.append(lista);
      }
      const item = document.createElement('li');
      agregarTexto(item, bloque);
      lista.append(item);
      return;
    }
    lista = null;

    if (bloque._type === 'block') {
      const etiqueta = { h2: 'h2', h3: 'h3', h4: 'h4', blockquote: 'blockquote' }[bloque.style] || 'p';
      const elemento = document.createElement(etiqueta);
      agregarTexto(elemento, bloque);
      fragmento.append(elemento);
    } else if (bloque._type === 'image') {
      const datos = datosImagen({ ...bloque, ref: bloque.asset?._ref }, raiz);
      if (!datos) return;
      const figura = document.createElement('figure');
      figura.append(crearImagen(datos, { sizes: '(min-width: 768px) 44rem, 100vw' }));
      if (bloque.leyenda) figura.append(crearElemento('figcaption', '', bloque.leyenda));
      fragmento.append(figura);
    }
  });

  return fragmento;
}

// ---------- Tarjetas (home y listado) ----------
function crearTarjeta(entrada, { rutaEntrada, nivelTitulo, raiz, carga }) {
  const item = document.createElement('li');
  const tarjeta = crearElemento('article', 'entrada-tarjeta');

  // En la tarjeta la imagen acompaña al título: alt vacío para no repetir información
  const imagen = datosImagen(entrada.imagen, raiz);
  if (imagen) {
    tarjeta.append(crearImagen(imagen, {
      clase: 'entrada-tarjeta__imagen',
      sizes: '(min-width: 1024px) 22rem, (min-width: 768px) 45vw, 100vw',
      alt: '',
      carga,
    }));
  }

  const cuerpo = crearElemento('div', 'entrada-tarjeta__cuerpo');
  const titulo = crearElemento(`h${nivelTitulo}`, 'entrada-tarjeta__titulo');
  const enlace = crearElemento('a', 'entrada-tarjeta__enlace', entrada.titulo);
  enlace.href = `${rutaEntrada}?slug=${encodeURIComponent(entrada.slug)}${MOSTRAR_EJEMPLOS ? '&ejemplos' : ''}`;
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
    const entradas = await obtenerEntradas(cantidad);
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
  } finally {
    lista.setAttribute('aria-busy', 'false');
    lista.dataset.cargada = 'true';
  }
}

// ---------- Página de una entrada ----------
async function mostrarEntrada(contenedor) {
  const raiz = document.body.dataset.raiz || '';
  const titulo = contenedor.querySelector('[data-entrada-titulo]');
  const meta = contenedor.querySelector('[data-entrada-meta]');
  const imagen = contenedor.querySelector('[data-entrada-imagen]');
  const cuerpo = contenedor.querySelector('[data-entrada-cuerpo]');
  const slug = new URLSearchParams(window.location.search).get('slug');

  const mostrarNoEncontrada = (mensaje) => {
    titulo.textContent = mensaje;
    document.title = `${mensaje} | Blog de Doble Barra Strength`;
    const parrafo = crearElemento('p');
    const enlace = crearElemento('a', '', 'Ver todas las entradas del blog');
    enlace.href = 'index.html';
    parrafo.append(enlace);
    cuerpo.replaceChildren(parrafo);
  };

  contenedor.setAttribute('aria-busy', 'true');
  try {
    const entrada = slug ? await obtenerEntrada(slug) : null;
    if (!entrada) {
      mostrarNoEncontrada('No encontramos esta entrada');
      return;
    }

    titulo.textContent = entrada.titulo;
    document.title = `${entrada.titulo} | Blog de Doble Barra Strength`;
    if (entrada.resumen) document.querySelector('meta[name="description"]')?.setAttribute('content', entrada.resumen);

    meta.replaceChildren(`Por ${entrada.autor} · `, crearFecha(entrada.fecha));

    const datos = datosImagen(entrada.imagen, raiz);
    if (datos) {
      const imagenPrincipal = crearImagen(datos, {
        clase: 'entrada__imagen',
        sizes: '(min-width: 768px) 44rem, 100vw',
        carga: 'eager',
      });
      imagenPrincipal.fetchPriority = 'high';
      imagen.replaceChildren(imagenPrincipal);
    }

    cuerpo.replaceChildren(crearCuerpo(entrada.cuerpo, raiz));
  } catch (error) {
    console.error('Error al cargar la entrada:', error);
    mostrarNoEncontrada('No pudimos cargar esta entrada');
  } finally {
    contenedor.setAttribute('aria-busy', 'false');
    contenedor.dataset.cargada = 'true';
  }
}

document.querySelectorAll('[data-blog="lista"]').forEach(mostrarLista);
document.querySelectorAll('[data-blog="entrada"]').forEach(mostrarEntrada);

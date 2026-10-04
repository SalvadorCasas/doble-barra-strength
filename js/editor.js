// Editor de entradas del panel (admin/entrada.php):
// - botones que agregan las marcas de formato al texto (**negrita**, ## Subtítulo, - lista…),
// - ventanas para agregar un link o subir una imagen al texto,
// - aviso al salir de la página con cambios sin guardar,
// - y la sesión no vence mientras se escribe (si se cerró, avisa antes de enviar y perder el texto).
// Sin JavaScript el formulario funciona igual: las marcas se pueden escribir a mano.

const MINUTOS_ENTRE_AVISOS_DE_SESION = 5;
const MENSAJE_SESION_CERRADA = 'Tu sesión se cerró por inactividad y no se puede guardar. Para no perder lo que escribiste: '
  + 'copiá el texto, ingresá de nuevo en otra pestaña, volvé a abrir la entrada y pegalo.';

const formulario = document.querySelector('[data-editor]');
const texto = document.getElementById('cuerpo');
const tokenCsrf = formulario?.querySelector('input[name="csrf"]').value;

// ---------- Insertar en el texto ----------
// execCommand('insertText') conserva el "deshacer" (Ctrl+Z); si el navegador no lo tiene, se usa setRangeText.
function reemplazarTexto(inicio, fin, nuevo, seleccion = [nuevo.length, nuevo.length]) {
  texto.focus();
  texto.setSelectionRange(inicio, fin);
  if (!document.execCommand('insertText', false, nuevo)) {
    texto.setRangeText(nuevo, inicio, fin, 'end');
    texto.dispatchEvent(new Event('input', { bubbles: true }));
  }
  texto.setSelectionRange(inicio + seleccion[0], inicio + seleccion[1]);
}

// ---------- Negrita y cursiva: envuelven lo seleccionado ----------
const ENVOLTURAS = {
  negrita: { marca: '**', ejemplo: 'texto en negrita', envuelto: /^\*\*(.+)\*\*$/ },
  cursiva: { marca: '*', ejemplo: 'texto en cursiva', envuelto: /^\*(?!\*)(.+?)(?<!\*)\*$/ },
};

function envolver({ marca, ejemplo, envuelto }) {
  const { selectionStart: inicio, selectionEnd: fin, value } = texto;
  const seleccion = value.slice(inicio, fin);

  if (!seleccion.trim()) {
    // Sin selección: se escribe un ejemplo seleccionado, listo para reemplazarlo
    reemplazarTexto(inicio, fin, marca + ejemplo + marca, [marca.length, marca.length + ejemplo.length]);
    return;
  }
  // Si ya tenía esa marca, se la saca (el botón funciona como interruptor)
  const sinMarca = seleccion.match(envuelto);
  if (sinMarca) {
    reemplazarTexto(inicio, fin, sinMarca[1], [0, sinMarca[1].length]);
    return;
  }
  // Los espacios de los bordes quedan afuera ("**texto **" no se reconoce) y cada línea se marca aparte
  const nuevo = seleccion.split('\n')
    .map((linea) => (linea.trim() ? linea.replace(/^(\s*)(.*?)(\s*)$/, `$1${marca}$2${marca}$3`) : linea))
    .join('\n');
  reemplazarTexto(inicio, fin, nuevo, [0, nuevo.length]);
}

// ---------- Subtítulo, listas y cita: marcan el principio de cada línea seleccionada ----------
const PREFIJOS = {
  subtitulo: { patron: /^#{1,4}\s+/, crear: () => '## ' },
  lista: { patron: /^[-*]\s+/, crear: () => '- ' },
  numerada: { patron: /^\d+[.)]\s+/, crear: (numero) => `${numero}. ` },
  cita: { patron: /^>\s*/, crear: () => '> ' },
};
const CUALQUIER_PREFIJO = /^(#{1,4}\s+|>\s*|[-*]\s+|\d+[.)]\s+)/;

function marcarLineas({ patron, crear }) {
  const { selectionStart, selectionEnd, value } = texto;
  // Se toman las líneas completas, aunque la selección empiece o termine en el medio
  const inicio = value.lastIndexOf('\n', selectionStart - 1) + 1;
  const finSeleccion = selectionEnd > selectionStart && value[selectionEnd - 1] === '\n' ? selectionEnd - 1 : selectionEnd;
  const finLinea = value.indexOf('\n', finSeleccion);
  const fin = finLinea === -1 ? value.length : finLinea;
  const lineas = value.slice(inicio, fin).split('\n');

  const conTexto = lineas.filter((linea) => linea.trim());
  if (!conTexto.length) {
    reemplazarTexto(inicio, fin, crear(1));
    return;
  }
  // Si todas ya tenían este formato, se lo saca; si no, se reemplaza el que tuvieran por este
  const quitar = conTexto.every((linea) => patron.test(linea));
  let numero = 0;
  const nuevo = lineas.map((linea) => {
    if (!linea.trim()) return linea;
    const limpia = linea.replace(CUALQUIER_PREFIJO, '');
    numero += 1;
    return quitar ? limpia : crear(numero) + limpia;
  }).join('\n');
  reemplazarTexto(inicio, fin, nuevo, lineas.length === 1 ? [nuevo.length, nuevo.length] : [0, nuevo.length]);
}

// ---------- Ventanas (link e imagen) ----------
function mostrarError(campo, mensaje) {
  const error = document.getElementById(`${campo.id}-error`);
  error.hidden = !mensaje;
  error.replaceChildren();
  if (mensaje) {
    const prefijo = document.createElement('span');
    prefijo.className = 'oculto-accesible';
    prefijo.textContent = 'Error: ';
    error.append(prefijo, mensaje);
    campo.setAttribute('aria-invalid', 'true');
  } else {
    campo.removeAttribute('aria-invalid');
  }
}

/** Muestra los errores de la ventana y lleva el foco al primero. Devuelve true si no hay errores. */
function validar(errores) {
  errores.forEach(([campo, mensaje]) => mostrarError(campo, mensaje));
  const primero = errores.find(([, mensaje]) => mensaje);
  primero?.[0].focus();
  return !primero;
}

// Selección del texto al abrir la ventana (al pasar el foco a la ventana el texto la "olvida")
let rango = { inicio: 0, fin: 0 };

function abrirDialogo(dialogo) {
  rango = { inicio: texto.selectionStart, fin: texto.selectionEnd };
  const formularioDialogo = dialogo.querySelector('form');
  formularioDialogo.reset();
  formularioDialogo.querySelectorAll('.campo__control').forEach((campo) => {
    if (document.getElementById(`${campo.id}-error`)) mostrarError(campo, '');
  });
  formularioDialogo.querySelectorAll('[data-dialogo-estado], [data-dialogo-alerta]').forEach((mensaje) => {
    mensaje.textContent = '';
  });
  dialogo.showModal();
  return formularioDialogo;
}

// Link: [texto](dirección)
const dialogoEnlace = document.getElementById('dialogo-enlace');

function abrirEnlace() {
  const formularioEnlace = abrirDialogo(dialogoEnlace);
  formularioEnlace.texto.value = texto.value.slice(rango.inicio, rango.fin).trim();
  (formularioEnlace.texto.value ? formularioEnlace.direccion : formularioEnlace.texto).focus();
}

function agregarEnlace(evento) {
  evento.preventDefault();
  const { texto: campoTexto, direccion: campoDireccion } = evento.target;
  // Corchetes y paréntesis romperían la marca del link
  const textoEnlace = campoTexto.value.trim().replace(/\[/g, '(').replace(/\]/g, ')');
  const direccion = campoDireccion.value.trim().replace(/ /g, '%20').replace(/\(/g, '%28').replace(/\)/g, '%29');
  const valido = validar([
    [campoTexto, textoEnlace ? '' : 'Escribí el texto del link.'],
    [campoDireccion, /^(https?:\/\/\S+\.\S+|mailto:\S+@\S+|tel:\S+)$/i.test(direccion)
      ? '' : 'Escribí la dirección completa, que empiece con https://'],
  ]);
  if (!valido) return;

  dialogoEnlace.close();
  const marca = `[${textoEnlace}](${direccion})`;
  reemplazarTexto(rango.inicio, rango.fin, marca);
}

// Imagen: se sube al servidor y se agrega en una línea propia como ![descripción](nombre "epígrafe")
const dialogoImagen = document.getElementById('dialogo-imagen');
const pesoMaximo = Number(formulario?.dataset.pesoMaximo) || 0;
let subiendo = false;

function abrirImagen() {
  abrirDialogo(dialogoImagen).archivo.focus();
}

async function agregarImagen(evento) {
  evento.preventDefault();
  if (subiendo) return;
  const formularioImagen = evento.target;
  const { archivo, alt, leyenda } = formularioImagen;
  const imagen = archivo.files[0];
  const megas = `${Math.round((pesoMaximo / 1024 / 1024) * 10) / 10} MB`;

  let errorArchivo = '';
  if (!imagen) errorArchivo = 'Elegí una imagen.';
  else if (!['image/jpeg', 'image/png', 'image/webp'].includes(imagen.type)) errorArchivo = 'Solo se aceptan imágenes JPG, PNG o WebP.';
  else if (pesoMaximo && imagen.size > pesoMaximo) errorArchivo = `La imagen pesa más de ${megas}. Elegí una más liviana.`;
  const valido = validar([
    [archivo, errorArchivo],
    [alt, alt.value.trim() ? '' : 'Escribí la descripción de la imagen.'],
  ]);
  if (!valido) return;

  const estado = formularioImagen.querySelector('[data-dialogo-estado]');
  const alerta = formularioImagen.querySelector('[data-dialogo-alerta]');
  const boton = formularioImagen.querySelector('[type="submit"]');
  const datos = new FormData();
  datos.append('csrf', tokenCsrf);
  datos.append('alt', alt.value.trim());
  datos.append('imagen', imagen);

  subiendo = true;
  boton.setAttribute('aria-disabled', 'true');
  alerta.textContent = '';
  estado.textContent = 'Subiendo la imagen…';
  let error = 'No pudimos subir la imagen. Revisá tu conexión y probá de nuevo.';
  try {
    const respuesta = await fetch('subir-imagen.php', { method: 'POST', body: datos, headers: { Accept: 'application/json' } });
    const resultado = await respuesta.json().catch(() => ({}));
    if (respuesta.ok && resultado.nombre) {
      error = '';
      // Si se cerró la ventana mientras subía, no se agrega nada (la imagen sin usar se borra sola)
      if (dialogoImagen.open) {
        dialogoImagen.close();
        insertarImagen(resultado.nombre, alt.value, leyenda.value);
      }
    } else if (resultado.error) {
      error = resultado.error;
    }
  } catch {
    // sin conexión: queda el mensaje de error por defecto
  } finally {
    subiendo = false;
    boton.removeAttribute('aria-disabled');
    estado.textContent = '';
    if (error) alerta.textContent = error;
  }
}

function insertarImagen(nombre, descripcion, epigrafe) {
  const alt = descripcion.trim().replace(/\[/g, '(').replace(/\]/g, ')');
  const leyenda = epigrafe.trim();
  const linea = `![${alt}](${nombre}${leyenda ? ` "${leyenda}"` : ''})`;
  // Va en una línea propia, después de lo seleccionado
  const posicion = rango.fin;
  const antes = posicion > 0 && texto.value[posicion - 1] !== '\n' ? '\n' : '';
  const despues = texto.value[posicion] && texto.value[posicion] !== '\n' ? '\n' : '';
  reemplazarTexto(posicion, posicion, antes + linea + despues);
}

// ---------- Cambios sin guardar y sesión ----------
// Después de una vista previa o de un error, lo que se ve en el formulario todavía no está guardado.
let hayCambios = formulario?.hasAttribute('data-sin-guardar');
let escribioDesdeElUltimoAviso = false;
let enviando = false;

async function sesionActiva() {
  try {
    const respuesta = await fetch('sesion-activa.php', { cache: 'no-store' });
    return respuesta.status !== 401;
  } catch {
    return true; // sin conexión no se sabe: se deja seguir
  }
}

function avisarSesionCerrada() {
  const alerta = formulario.querySelector('[data-editor-alerta]');
  alerta.textContent = MENSAJE_SESION_CERRADA;
}

function iniciarControlDeCambios() {
  formulario.addEventListener('input', () => {
    hayCambios = true;
    escribioDesdeElUltimoAviso = true;
  });
  formulario.addEventListener('change', () => {
    hayCambios = true;
  });

  window.addEventListener('beforeunload', (evento) => {
    if (hayCambios && !enviando) evento.preventDefault();
  });

  // Mientras se escribe, cada tantos minutos se avisa al servidor para que la sesión no venza
  setInterval(async () => {
    if (!escribioDesdeElUltimoAviso) return;
    escribioDesdeElUltimoAviso = false;
    if (!(await sesionActiva())) avisarSesionCerrada();
  }, MINUTOS_ENTRE_AVISOS_DE_SESION * 60 * 1000);

  // Antes de enviar se revisa la sesión: si se cerró, el envío llevaría al ingreso y se perdería el texto
  formulario.addEventListener('submit', async (evento) => {
    if (enviando) return;
    evento.preventDefault();
    const boton = evento.submitter;
    if (!(await sesionActiva())) {
      avisarSesionCerrada();
      return;
    }
    enviando = true;
    formulario.requestSubmit(boton);
  });
}

// ---------- Inicio ----------
function iniciarEditor() {
  if (!formulario || !texto) return;

  const barra = formulario.querySelector('[data-editor-barra]');
  barra.hidden = false;
  barra.addEventListener('click', (evento) => {
    const formato = evento.target.closest('[data-formato]')?.dataset.formato;
    if (ENVOLTURAS[formato]) envolver(ENVOLTURAS[formato]);
    else if (PREFIJOS[formato]) marcarLineas(PREFIJOS[formato]);
    else if (formato === 'enlace') abrirEnlace();
    else if (formato === 'imagen') abrirImagen();
  });

  // Atajos habituales: Ctrl+B negrita, Ctrl+I cursiva, Ctrl+K link
  texto.addEventListener('keydown', (evento) => {
    if (!(evento.ctrlKey || evento.metaKey) || evento.altKey || evento.shiftKey) return;
    const atajo = { b: () => envolver(ENVOLTURAS.negrita), i: () => envolver(ENVOLTURAS.cursiva), k: abrirEnlace }[evento.key.toLowerCase()];
    if (!atajo) return;
    evento.preventDefault();
    atajo();
  });

  document.querySelector('[data-dialogo="enlace"]').addEventListener('submit', agregarEnlace);
  document.querySelector('[data-dialogo="imagen"]').addEventListener('submit', agregarImagen);
  document.querySelectorAll('.dialogo [data-cerrar]').forEach((boton) => {
    boton.addEventListener('click', () => boton.closest('dialog').close());
  });

  iniciarControlDeCambios();
}

iniciarEditor();

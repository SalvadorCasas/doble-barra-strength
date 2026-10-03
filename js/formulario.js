// Validación y envío del formulario "Sumate al equipo".
// - Qué campos son obligatorios se define en el HTML con el atributo "required".
// - El envío va a FormSubmit por AJAX: la dirección sale del atributo "action" del formulario.
// - Sin JavaScript, el formulario se envía igual con la validación nativa del navegador.

// Mensajes de cada campo. "vacio": falta un campo obligatorio.
// "validar": recibe el valor escrito y devuelve true si sirve.
const REGLAS_FORMULARIO = {
  nombre: { vacio: 'Ingresá tu nombre completo.' },
  email: {
    vacio: 'Ingresá tu mail.',
    validar: (valor) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor),
    invalido: 'Ingresá un mail válido, por ejemplo nombre@ejemplo.com.',
  },
  edad: {
    vacio: 'Ingresá tu edad.',
    validar: (valor) => esNumeroEnRango(valor, { min: 10, max: 100 }),
    invalido: 'Ingresá tu edad en años, solo con números (por ejemplo, 28).',
  },
  altura: {
    vacio: 'Ingresá tu altura.',
    validar: (valor) => esNumeroEnRango(valor, { min: 100, max: 250 }),
    invalido: 'Ingresá tu altura en centímetros, solo con números (por ejemplo, 175).',
  },
  peso: {
    vacio: 'Ingresá tu peso.',
    validar: (valor) => esNumeroEnRango(valor, { min: 30, max: 300, decimales: true }),
    invalido: 'Ingresá tu peso en kilos, solo con números (por ejemplo, 72 o 72,5).',
  },
  como_nos_conociste: { vacio: 'Contanos cómo nos conociste.' },
  por_que: { vacio: 'Contanos por qué querés trabajar con nosotros.' },
  ocupacion: { vacio: 'Elegí si trabajás, estudiás, ambas o ninguna.' },
  profesion: { vacio: 'Contanos tu profesión o qué estás estudiando.' },
  objetivo: { vacio: 'Contanos cuál es tu objetivo principal.' },
  entrenador: { vacio: 'Elegí con quién te gustaría trabajar.' },
  telefono: {
    vacio: 'Ingresá tu teléfono.',
    validar: (valor) => /^[\d\s()+-]+$/.test(valor) && valor.replace(/\D/g, '').length >= 8,
    invalido: 'Ingresá un teléfono válido con código de área, por ejemplo +54 9 351 123 4567.',
  },
};

const MENSAJE_ERROR_ENVIO = 'No pudimos enviar tu solicitud. Revisá tu conexión y volvé a intentarlo en unos minutos. Si el problema sigue, escribinos por redes a @doblebarra.strength.';
// Se muestra mientras el "action" del formulario no tenga el alias real de FormSubmit (CLAUDE.md, sección 8)
const MENSAJE_SIN_CONECTAR = 'Todavía no podemos recibir solicitudes desde la página. Mientras tanto, escribinos por redes a @doblebarra.strength.';

function esNumeroEnRango(valor, { min, max, decimales = false }) {
  const normalizado = valor.replace(',', '.');
  const patron = decimales ? /^\d+(\.\d+)?$/ : /^\d+$/;
  if (!patron.test(normalizado)) return false;
  const numero = Number(normalizado);
  return numero >= min && numero <= max;
}

function iniciarFormulario() {
  const formulario = document.getElementById('formulario-sumate');
  if (!formulario) return;

  const resumen = document.getElementById('resumen-errores');
  const resumenTitulo = resumen.querySelector('.resumen-errores__titulo');
  const resumenLista = resumen.querySelector('.resumen-errores__lista');
  const estado = formulario.querySelector('.formulario__estado');
  const alerta = formulario.querySelector('.formulario__alerta');
  const botonEnviar = formulario.querySelector('[type="submit"]');
  const textoBoton = botonEnviar.textContent;
  const exito = document.getElementById('formulario-exito');
  const camposConError = new Set();
  let enviando = false;

  // Con JavaScript usamos nuestra validación (mensajes claros y anunciados) en lugar de la nativa
  formulario.noValidate = true;

  const obtenerControles = (nombre) => formulario.querySelectorAll(`[name="${nombre}"]`);

  const obtenerValor = (nombre) => {
    const controles = obtenerControles(nombre);
    if (controles[0].type === 'radio') {
      const elegido = Array.from(controles).find((control) => control.checked);
      return elegido ? elegido.value : '';
    }
    return controles[0].value.trim();
  };

  // Nombres de los campos a validar, en el orden en que aparecen
  const nombresDeCampos = () => {
    const nombres = Array.from(formulario.elements)
      .map((control) => control.name)
      .filter((nombre) => REGLAS_FORMULARIO[nombre]);
    return [...new Set(nombres)];
  };

  const mostrarError = (nombre, mensaje) => {
    const error = document.getElementById(`${nombre}-error`);
    error.replaceChildren();
    if (mensaje) {
      const prefijo = document.createElement('span');
      prefijo.className = 'oculto-accesible';
      prefijo.textContent = 'Error: ';
      error.append(prefijo, mensaje);
    }
    error.hidden = !mensaje;
    obtenerControles(nombre).forEach((control) => {
      if (mensaje) control.setAttribute('aria-invalid', 'true');
      else control.removeAttribute('aria-invalid');
    });
  };

  // Devuelve el mensaje de error del campo, o '' si está bien
  const validarCampo = (nombre) => {
    const reglas = REGLAS_FORMULARIO[nombre];
    const valor = obtenerValor(nombre);
    const obligatorio = Array.from(obtenerControles(nombre)).some((control) => control.required);
    let mensaje = '';

    if (!valor && obligatorio) mensaje = reglas.vacio;
    else if (valor && reglas.validar && !reglas.validar(valor)) mensaje = reglas.invalido;

    mostrarError(nombre, mensaje);
    if (mensaje) camposConError.add(nombre);
    else camposConError.delete(nombre);
    return mensaje;
  };

  const mostrarResumen = (errores) => {
    resumenTitulo.textContent = errores.length === 1
      ? 'Hay 1 error en el formulario'
      : `Hay ${errores.length} errores en el formulario`;
    resumenLista.replaceChildren(...errores.map(({ nombre, mensaje }) => {
      const item = document.createElement('li');
      const enlace = document.createElement('a');
      enlace.href = `#${obtenerControles(nombre)[0].id}`;
      enlace.textContent = mensaje;
      item.append(enlace);
      return item;
    }));
    resumen.hidden = false;
    resumen.focus();
  };

  // Vacía el mensaje y lo vuelve a escribir para que el lector de pantalla lo anuncie
  // aunque sea igual al anterior
  const anunciar = (elemento, texto) => {
    elemento.textContent = '';
    window.setTimeout(() => { elemento.textContent = texto; }, 100);
  };

  // Los links del resumen llevan el foco al campo con error
  resumenLista.addEventListener('click', (evento) => {
    const enlace = evento.target.closest('a');
    if (!enlace) return;
    evento.preventDefault();
    document.getElementById(enlace.hash.slice(1)).focus();
  });

  // Si un campo ya mostró un error, se vuelve a validar mientras se corrige
  formulario.addEventListener('input', (evento) => {
    if (camposConError.has(evento.target.name)) validarCampo(evento.target.name);
  });

  formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    if (enviando) return;
    alerta.textContent = '';

    const errores = nombresDeCampos()
      .map((nombre) => ({ nombre, mensaje: validarCampo(nombre) }))
      .filter(({ mensaje }) => mensaje);

    if (errores.length) {
      mostrarResumen(errores);
      return;
    }
    resumen.hidden = true;

    if (formulario.action.includes('COMPLETAR')) {
      anunciar(alerta, MENSAJE_SIN_CONECTAR);
      return;
    }

    enviando = true;
    botonEnviar.textContent = 'Enviando…';
    estado.textContent = 'Enviando tu solicitud…';

    try {
      const destino = formulario.action.replace('formsubmit.co/', 'formsubmit.co/ajax/');
      const respuesta = await fetch(destino, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(Object.fromEntries(new FormData(formulario))),
      });
      const resultado = await respuesta.json().catch(() => ({}));
      if (!respuesta.ok || String(resultado.success) !== 'true') {
        throw new Error(resultado.message || `FormSubmit respondió ${respuesta.status}`);
      }

      formulario.hidden = true;
      exito.hidden = false;
      exito.focus();
    } catch (error) {
      console.error('Error al enviar el formulario:', error);
      anunciar(alerta, MENSAJE_ERROR_ENVIO);
    } finally {
      enviando = false;
      botonEnviar.textContent = textoBoton;
      estado.textContent = '';
    }
  });
}

iniciarFormulario();

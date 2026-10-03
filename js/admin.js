// Comportamientos del panel de administración.

// ---------- Mostrar u ocultar la contraseña ----------
// Ayuda a quien escribe la contraseña a mano a revisar que esté bien (sin depender de la memoria).
function iniciarVerClave() {
  document.querySelectorAll('.boton-ver-clave').forEach((boton) => {
    const campo = document.getElementById(boton.getAttribute('aria-controls'));
    if (!campo) return;
    boton.hidden = false;
    boton.addEventListener('click', () => {
      const mostrar = campo.type === 'password';
      campo.type = mostrar ? 'text' : 'password';
      boton.setAttribute('aria-pressed', String(mostrar));
    });
    // Antes de enviar se vuelve a ocultar, para que el navegador la guarde como contraseña
    campo.form?.addEventListener('submit', () => {
      campo.type = 'password';
      boton.setAttribute('aria-pressed', 'false');
    });
  });
}

// ---------- Foco en los errores ----------
// Si la página vuelve con errores del servidor, el foco va al resumen para que se lea primero.
function enfocarErrores() {
  const resumen = document.querySelector('[data-enfocar]');
  if (!resumen) return;
  resumen.focus();
  // Los links del resumen llevan el foco al campo con error
  resumen.addEventListener('click', (evento) => {
    const enlace = evento.target.closest('a[href^="#"]');
    if (!enlace) return;
    evento.preventDefault();
    document.getElementById(enlace.hash.slice(1))?.focus();
  });
}

iniciarVerClave();
enfocarErrores();

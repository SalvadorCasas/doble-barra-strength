// Se carga en el <head> sin defer: aplica el tema guardado antes de pintar la página
// (evita un parpadeo de colores) y marca que hay JavaScript con la clase "js".
(function () {
  const raiz = document.documentElement;
  raiz.classList.add('js');

  try {
    const tema = localStorage.getItem('tema');
    if (tema === 'claro' || tema === 'oscuro') {
      raiz.dataset.tema = tema;
    }
  } catch (error) {
    // Sin acceso a localStorage (modo privado, bloqueos): se usa la preferencia del sistema
  }
})();

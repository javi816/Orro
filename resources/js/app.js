// Comportamiento general de la aplicación.
document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('submit', (event) => {
        if (! window.confirm(element.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

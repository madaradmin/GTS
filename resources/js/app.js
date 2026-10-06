

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/**
 * Eliminación rigurosa de memoria y autocompletado nativo del navegador en todo GTS.
 * Se asegura que ningún formulario ni campo de búsqueda retenga listas o historial previo,
 * respetando estrictamente la excepción del Login institucional donde el username puede recordarse.
 */
document.addEventListener('DOMContentLoaded', () => {
    const sanitizeAutocomplete = () => {
        // 1. Desactivar autocomplete en formularios que no sean de login
        document.querySelectorAll('form').forEach((form) => {
            const action = form.getAttribute('action') || '';
            if (!action.includes('/login')) {
                form.setAttribute('autocomplete', 'off');
            }
        });

        // 2. Desactivar autocomplete, autocorrect y spellcheck en campos de entrada
        document.querySelectorAll('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"])').forEach((input) => {
            const formAction = input.form?.getAttribute('action') || '';
            const isLogin = formAction.includes('/login') || (input.id === 'username' && formAction.includes('login'));
            const currentAuto = input.getAttribute('autocomplete');

            if (!isLogin && currentAuto !== 'username' && currentAuto !== 'current-password' && currentAuto !== 'new-password') {
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('autocorrect', 'off');
                input.setAttribute('autocapitalize', 'off');
                input.setAttribute('spellcheck', 'false');
            }
        });
    };

    sanitizeAutocomplete();

    // Vigilar la inserción de modales dinámicos (Alpine.js)
    if (window.MutationObserver) {
        const observer = new MutationObserver(() => {
            sanitizeAutocomplete();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
});

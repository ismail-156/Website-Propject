/* Accessible mobile navigation and stock toggle submission. */
(() => {
    const menuButton = document.querySelector('[data-hospital-menu-toggle]');
    const menu = document.querySelector('[data-hospital-menu]');

    menuButton?.addEventListener('click', () => {
        const expanded = menuButton.getAttribute('aria-expanded') === 'true';
        menuButton.setAttribute('aria-expanded', String(!expanded));
        menuButton.setAttribute('aria-label', expanded ? 'Open navigation' : 'Close navigation');
        menu?.classList.toggle('is-open', !expanded);
    });

    document.querySelectorAll('[data-stock-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const form = toggle.closest('form');
            const value = form?.querySelector('[name="in_stock"]');
            if (value) value.value = toggle.checked ? 'yes' : 'no';
            form?.requestSubmit();
        });
    });
})();

// Provide keyboard-accessible navigation, dialogs, and dismissible feedback.
(() => {
    const menuButton = document.querySelector('[data-admin-menu-toggle]');
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const scrim = document.querySelector('[data-admin-close]');

    // Keep the mobile drawer state synchronized with its button and scrim.
    const setMenuOpen = (open) => {
        sidebar?.classList.toggle('is-open', open);
        scrim?.classList.toggle('is-visible', open);
        menuButton?.setAttribute('aria-expanded', String(open));
        menuButton?.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        document.body.classList.toggle('admin-menu-open', open);
    };

    menuButton?.addEventListener('click', () => {
        setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true');
    });
    scrim?.addEventListener('click', () => setMenuOpen(false));
    sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuOpen(false)));

    // Native dialog elements provide focus handling and blurred backdrops.
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-open-modal]');
        if (opener) document.getElementById(opener.dataset.openModal)?.showModal();
        if (event.target.closest('[data-close-modal]')) event.target.closest('dialog')?.close();
        if (event.target.closest('[data-dismiss-alert]')) event.target.closest('[data-auto-dismiss]')?.remove();
    });

    // Escape closes the drawer as well as native open dialogs.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenuOpen(false);
    });

    // Keep the submitted day count in sync while allowing years in the editor.
    document.querySelectorAll('[data-age-converter]').forEach((control) => {
        const valueInput = control.querySelector('[data-age-value]');
        const unitSelect = control.querySelector('[data-age-unit]');
        const daysInput = control.querySelector('[data-age-days]');
        if (!valueInput || !unitSelect || !daysInput) return;

        let previousUnit = unitSelect.value;
        const syncDays = () => {
            const value = Number(valueInput.value);
            if (Number.isFinite(value) && value >= 0) {
                daysInput.value = String(Math.round(value * (unitSelect.value === 'years' ? 365.25 : 1)));
            }
        };
        unitSelect.addEventListener('change', () => {
            const value = Number(valueInput.value);
            if (Number.isFinite(value) && value >= 0 && previousUnit !== unitSelect.value) {
                const converted = unitSelect.value === 'years' ? value / 365.25 : value * 365.25;
                valueInput.value = unitSelect.value === 'years' ? converted.toFixed(3) : String(Math.round(converted));
            }
            previousUnit = unitSelect.value;
            valueInput.max = unitSelect.value === 'years' ? '100' : '36525';
            valueInput.step = unitSelect.value === 'years' ? '0.001' : '1';
            syncDays();
        });
        valueInput.addEventListener('input', syncDays);
        control.closest('form')?.addEventListener('submit', syncDays);
        syncDays();
    });
})();
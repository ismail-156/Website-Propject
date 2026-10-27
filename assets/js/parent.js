/* Mobile navigation, accessible confirmation dialogs, and lightweight form interactions. */
(() => {
    const menuButton = document.querySelector('[data-parent-menu-toggle]');
    const menu = document.querySelector('[data-parent-menu]');
    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    const confirmMessage = confirmDialog?.querySelector('[data-confirm-message]');
    let pendingForm = null;

    menuButton?.addEventListener('click', () => {
        const expanded = menuButton.getAttribute('aria-expanded') === 'true';
        menuButton.setAttribute('aria-expanded', String(!expanded));
        menuButton.setAttribute('aria-label', expanded ? 'Open navigation' : 'Close navigation');
        menu?.classList.toggle('is-open', !expanded);
    });

    document.querySelectorAll('[data-confirm-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }
            event.preventDefault();
            pendingForm = form;
            if (confirmMessage) {
                confirmMessage.textContent = form.getAttribute('data-confirm-form') || 'Are you sure?';
            }
            if (confirmDialog instanceof HTMLDialogElement) confirmDialog.showModal();
        });
    });

    confirmDialog?.querySelector('[data-confirm-continue]')?.addEventListener('click', () => {
        if (!pendingForm) return;
        const formToSubmit = pendingForm;
        pendingForm = null;
        confirmDialog.close();
        formToSubmit.dataset.confirmed = 'true';
        formToSubmit.requestSubmit();
    });

    document.querySelectorAll('[data-dismiss-alert]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-auto-dismiss]')?.remove());
    });

    document.querySelector('[data-book-child]')?.addEventListener('change', (event) => {
        const childId = event.currentTarget.value;
        const destination = new URL(window.location.href);
        destination.search = '';
        if (childId) destination.searchParams.set('child_id', childId);
        window.location.assign(destination);
    });

    document.querySelectorAll('[data-gender-choice]').forEach((choice) => {
        choice.addEventListener('click', () => {
            const input = choice.querySelector('input[type="radio"]');
            if (input) input.checked = true;
            choice.closest('[data-gender-group]')?.querySelectorAll('[data-gender-choice]').forEach((item) => {
                item.classList.toggle('is-selected', item === choice);
            });
        });
    });
})();

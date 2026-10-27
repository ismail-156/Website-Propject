document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (!input) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
        });
    });

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const emailField = form.querySelector('input[type="email"]');
            const passwordField = form.querySelector('input[name="password"]');
            const confirmField = form.querySelector('input[name="confirm_password"]');
            const phoneField = form.querySelector('input[name="phone"]');

            let isValid = true;

            form.querySelectorAll('input[required], textarea[required]').forEach(function (field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#d84b5b';
                } else {
                    field.style.borderColor = '#dfeae7';
                }
            });

            if (emailField && emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                isValid = false;
                emailField.style.borderColor = '#d84b5b';
            }

            if (passwordField && passwordField.value.length > 0 && passwordField.value.length < 8) {
                isValid = false;
                passwordField.style.borderColor = '#d84b5b';
            }

            if (passwordField && confirmField && passwordField.value && confirmField.value && passwordField.value !== confirmField.value) {
                isValid = false;
                confirmField.style.borderColor = '#d84b5b';
            }

            if (phoneField && phoneField.value && !/^[0-9+\-\s()]{8,20}$/.test(phoneField.value)) {
                isValid = false;
                phoneField.style.borderColor = '#d84b5b';
            }

            if (!isValid) {
                event.preventDefault();
                const alertBox = form.querySelector('.alert-danger');
                if (!alertBox) {
                    const context = form.querySelector('.auth-header') || form;
                    const message = document.createElement('div');
                    message.className = 'alert alert-danger';
                    message.textContent = 'Please correct the highlighted fields and try again.';
                    context.parentNode.insertBefore(message, context.nextSibling);
                }
            }
        });
    });
});

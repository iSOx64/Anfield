document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('[data-toggle]');
    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const targetSelector = toggle.getAttribute('data-toggle');
            if (!targetSelector) {
                return;
            }
            const target = document.querySelector(targetSelector);
            if (target) {
                target.classList.toggle('is-hidden');
            }
        });
    });

    const passwordButtons = document.querySelectorAll('[data-password-toggle]');
    passwordButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const inputId = button.getAttribute('data-password-toggle');
            if (!inputId) {
                return;
            }
            const input = document.getElementById(inputId);
            if (!input) {
                return;
            }
            const icon = button.querySelector('.icon-eye, .icon-eye-off');
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            if (icon) {
                if (isPassword) {
                    icon.classList.remove('icon-eye');
                    icon.classList.add('icon-eye-off');
                } else {
                    icon.classList.remove('icon-eye-off');
                    icon.classList.add('icon-eye');
                }
            }
        });
    });
});


        // Password Toggle
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });

        // Password Strength
        document.getElementById('password')?.addEventListener('input', function () {
            const val = this.value;
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');

            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) strength++;
            if (/\d/.test(val)) strength++;
            if (/[^a-zA-Z0-9]/.test(val)) strength++;

            fill.className = 'strength-fill';
            text.className = 'strength-text';

            if (strength <= 2) {
                fill.classList.add('weak');
                text.classList.add('weak');
                text.textContent = 'Lemah';
            } else if (strength <= 4) {
                fill.classList.add('medium');
                text.classList.add('medium');
                text.textContent = 'Sedang';
            } else {
                fill.classList.add('strong');
                text.classList.add('strong');
                text.textContent = 'Kuat ✓';
            }
        });

        // Form Validation
        document.querySelector('.auth-form')?.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        // Auto-focus
        document.addEventListener('DOMContentLoaded', function () {
            const firstEmpty = document.querySelector('.auth-form input:not([value]):not([type="checkbox"]):not([type="submit"])');
            if (firstEmpty) firstEmpty.focus();
        });
   
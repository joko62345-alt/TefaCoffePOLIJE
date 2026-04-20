
        // Auto-focus first empty field
        document.addEventListener('DOMContentLoaded', function () {
            const firstInput = document.querySelector('.form-control-custom:not([value]):not([value=""])');
            if (firstInput) firstInput.focus();
        });

        // Format phone number on input
        document.querySelector('input[name="telepon"]')?.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Auto-resize textarea
        const textarea = document.querySelector('textarea[name="alamat"]');
        if (textarea) {
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        }

        // Prevent double submit
        const form = document.querySelector('#profileForm');
        if (form) {
            form.addEventListener('submit', function () {
                const submitBtn = document.querySelector('.btn-modal-save');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                }
            });
        }
   
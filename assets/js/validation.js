/**
 * FixMate - Client-side Form Validation Helpers
 */
document.addEventListener('DOMContentLoaded', function () {
    // Form validation bindings
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                const val = field.value.trim();
                const errorSpan = field.parentElement.querySelector('.form-error') || createErrorElement(field);
                
                if (!val) {
                    isValid = false;
                    field.classList.add('input-error');
                    errorSpan.textContent = 'This field is required.';
                    errorSpan.style.display = 'block';
                } else if (field.type === 'email' && !validateEmail(val)) {
                    isValid = false;
                    field.classList.add('input-error');
                    errorSpan.textContent = 'Please enter a valid email address.';
                    errorSpan.style.display = 'block';
                } else if (field.name === 'password' && val.length < 6) {
                    isValid = false;
                    field.classList.add('input-error');
                    errorSpan.textContent = 'Password must be at least 6 characters.';
                    errorSpan.style.display = 'block';
                } else if (field.name === 'confirm_password') {
                    const pwd = form.querySelector('[name="password"]').value;
                    if (val !== pwd) {
                        isValid = false;
                        field.classList.add('input-error');
                        errorSpan.textContent = 'Passwords do not match.';
                        errorSpan.style.display = 'block';
                    } else {
                        field.classList.remove('input-error');
                        errorSpan.style.display = 'none';
                    }
                } else {
                    field.classList.remove('input-error');
                    errorSpan.style.display = 'none';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function createErrorElement(field) {
        const span = document.createElement('span');
        span.className = 'form-error text-danger text-xs mt-1';
        field.parentElement.appendChild(span);
        return span;
    }
});

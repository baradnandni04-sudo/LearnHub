document.querySelectorAll('.password-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.closest('.input-group')?.querySelector('input');
        if (!input) return;
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.innerHTML = `<i class="bi ${visible ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
    });
});

document.querySelectorAll('form[novalidate]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.checkValidity()) return;
        event.preventDefault();
        event.stopPropagation();
        form.classList.add('was-validated');
        form.querySelector(':invalid')?.focus();
    });
});

const revealItems = document.querySelectorAll('.course-card, .category-tile, .value-card');
if ('IntersectionObserver' in window && revealItems.length) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    revealItems.forEach((item) => {
        item.classList.add('reveal-on-scroll');
        revealObserver.observe(item);
    });
}

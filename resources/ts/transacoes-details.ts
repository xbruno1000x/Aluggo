document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll<HTMLAnchorElement>('.btn-details').forEach((btn) => {
        btn.addEventListener('click', (e: Event) => {
            e.preventDefault();
            if (btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';
            btn.classList.add('disabled');

            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm ms-2';
            spinner.setAttribute('role', 'status');
            spinner.setAttribute('aria-hidden', 'true');
            btn.appendChild(spinner);

            const href = btn.getAttribute('href') ?? '';
            setTimeout(() => { if (href) window.location.href = href; }, 60);
        });
    });
});

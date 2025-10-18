document.addEventListener('DOMContentLoaded', function () {
    const btnCopy = document.getElementById('btn-copy-secret') as HTMLButtonElement | null;
    const input = document.getElementById('tf-secret') as HTMLInputElement | null;
    
    if (btnCopy && input) {
        btnCopy.addEventListener('click', function () {
            navigator.clipboard.writeText(input.value).then(() => {
                const original = btnCopy.textContent || 'Copiar';
                btnCopy.textContent = 'Copiado';
                setTimeout(() => btnCopy.textContent = original, 1500);
            }).catch(() => alert('Não foi possível copiar o segredo.'));
        });
    }
});

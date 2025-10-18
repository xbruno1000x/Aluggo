document.addEventListener('DOMContentLoaded', function () {
    const btnCopy = document.getElementById('btn-copy-secret') as HTMLButtonElement | null;
    const input = document.getElementById('tf-secret') as HTMLInputElement | null;
    const btnOpen = document.getElementById('btn-open-qr') as HTMLButtonElement | null;

    if (btnCopy && input) {
        btnCopy.addEventListener('click', function () {
            navigator.clipboard.writeText(input.value).then(() => {
                const original = btnCopy.textContent || 'Copiar';
                btnCopy.textContent = 'Copiado';
                setTimeout(() => btnCopy.textContent = original, 1500);
            }).catch(() => alert('Não foi possível copiar o segredo.'));
        });
    }

    if (btnOpen) {
        btnOpen.addEventListener('click', function () {
            // tenta encontrar o elemento SVG ou IMG dentro do QR Code e abrir em nova aba
            const qrContainer = document.querySelector('.d-inline-block');
            if (!qrContainer) return alert('QR code não encontrado na página.');

            const img = qrContainer.querySelector('img') as HTMLImageElement | null;
            if (img && img.src) {
                window.open(img.src, '_blank');
                return;
            }

            const svg = qrContainer.querySelector('svg') as SVGElement | null;
            if (svg) {
                const serializer = new XMLSerializer();
                const svgStr = serializer.serializeToString(svg);
                const blob = new Blob([svgStr], { type: 'image/svg+xml' });
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
                return;
            }

            alert('Não foi possível abrir o QR em nova aba.');
        });
    }
});

import { showAlert } from './utils/alert';
declare const bootstrap: any;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('propriedade-form') as HTMLFormElement | null;
    const modalEl = document.getElementById('propriedadeModal') as HTMLElement | null;
    const select = document.getElementById('propriedade_id') as HTMLSelectElement | null;

    if (!form || !modalEl || !select) return;

    const endpoint = form.dataset.endpoint ?? '';
    const csrf = form.dataset.csrf ?? '';

    async function postForm(fd: FormData) {
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: fd
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);

        try {
            const res = await postForm(fd);

            if (!res.ok) {
                let body: any = null;
                try {
                    const ct = res.headers.get('content-type') ?? '';
                    if (ct.includes('application/json')) body = await res.json();
                } catch {}
                const msg = (body && body.message) || 'Erro ao criar propriedade';
                showAlert('error', msg);
                return;
            }

            let json: any = null;
            try {
                const ct = res.headers.get('content-type') ?? '';
                if (ct.includes('application/json')) {
                    json = await res.json();
                }
            } catch (err) {
                console.warn('[propriedade-modal] não foi possível parsear JSON da resposta', err);
                json = null;
            }

            if (json && json.id !== undefined) {
                const opt = document.createElement('option');
                opt.value = String(json.id);
                opt.text = String(json.nome ?? `Propriedade ${json.id}`);
                opt.selected = true;
                select.appendChild(opt);
            }

            try {
                const bsModal = (bootstrap?.Modal?.getOrCreateInstance && bootstrap.Modal.getOrCreateInstance(modalEl))
                    || (bootstrap?.Modal?.getInstance && bootstrap.Modal.getInstance(modalEl))
                    || new bootstrap.Modal(modalEl);
                if (bsModal && typeof bsModal.hide === 'function') {
                    bsModal.hide();
                }
            } catch (err) {
                console.warn('[propriedade-modal] fallback para fechar modal', err);
                const dismissBtn = modalEl.querySelector('[data-bs-dismiss="modal"]') as HTMLElement | null;
                if (dismissBtn) {
                    dismissBtn.click();
                } else {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                }
            }

            form.reset();
            showAlert('success', 'Propriedade criada com sucesso', 2000);

        } catch (err) {
            console.error('[propriedade-modal] submit error', err);
            showAlert('error', 'Erro de rede. Tente novamente.');
        }
    });
});
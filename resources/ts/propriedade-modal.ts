import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
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
                // tenta ler JSON de erro, mas com fallback
                let body: any = null;
                try {
                    const ct = res.headers.get('content-type') ?? '';
                    if (ct.includes('application/json')) body = await res.json();
                } catch {}
                const msg = (body && body.message) || 'Erro ao criar propriedade';
                showAlert('error', msg);
                return;
            }

            // Resposta OK: tenta parsear JSON, mas se falhar continua (não quebra fluxo)
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

            // Se tivermos dados, adiciona ao select
            if (json && json.id !== undefined) {
                const opt = document.createElement('option');
                opt.value = String(json.id);
                opt.text = String(json.nome ?? `Propriedade ${json.id}`);
                opt.selected = true;
                select.appendChild(opt);
            }

            // Fecha modal (pega ou cria instância para garantir funcionamento)
            try {
                // Prefer getOrCreateInstance quando disponível
                const bsModal = (bootstrap?.Modal?.getOrCreateInstance && bootstrap.Modal.getOrCreateInstance(modalEl))
                    || (bootstrap?.Modal?.getInstance && bootstrap.Modal.getInstance(modalEl))
                    || new bootstrap.Modal(modalEl);
                if (bsModal && typeof bsModal.hide === 'function') {
                    bsModal.hide();
                }
            } catch (err) {
                console.warn('[propriedade-modal] fallback para fechar modal', err);
                // Fallback: aciona botão de dismiss ou remove classes/backdrop manualmente
                const dismissBtn = modalEl.querySelector('[data-bs-dismiss="modal"]') as HTMLElement | null;
                if (dismissBtn) {
                    dismissBtn.click();
                } else {
                    // remove state de modal aberto
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                }
            }

            // Reseta formulário
            form.reset();
            
            // Mostra alerta de sucesso
            showAlert('success', 'Propriedade criada com sucesso', 2000);

        } catch (err) {
            console.error('[propriedade-modal] submit error', err);
            showAlert('error', 'Erro de rede. Tente novamente.');
        }
    });

    function showAlert(icon: 'success' | 'error' | 'info' | 'warning', message: string, timer = 0) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon,
            title: message,
            showConfirmButton: false,
            timer: timer || 3000,
            timerProgressBar: !!timer,
            customClass: { container: 'sa-container' },
            didOpen: () => {
                const c = document.querySelector('.sa-container') as HTMLElement | null;
                if (c) c.style.zIndex = '20000';
            }
        });
    }
});
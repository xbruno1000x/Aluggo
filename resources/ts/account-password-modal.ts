import { showAlert } from './utils/alert';
declare const bootstrap: any;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('password-form') as HTMLFormElement | null;
    const modalEl = document.getElementById('passwordModal') as HTMLElement | null;

    if (!form || !modalEl) return;

    async function postForm(fd: FormData) {
        return fetch(form!.action, { 
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': form!.querySelector<HTMLInputElement>('input[name="_token"]')?.value ?? '',
                'Accept': 'application/json',
            },
            body: fd
        });
    }

    form!.addEventListener('submit', async (e) => { // <--- "!" aqui também
        e.preventDefault();
        const fd = new FormData(form!);

        const submitBtn = document.getElementById('btn-submit-password') as HTMLButtonElement | null;
        const btnText = submitBtn?.querySelector('.btn-text') as HTMLElement | null;
        const spinner = submitBtn?.querySelector('.spinner-border') as HTMLElement | null;

        if (submitBtn && btnText && spinner) {
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');
        }

        try {
            const res = await postForm(fd);

            if (!res.ok) {
                let body: any = null;
                try {
                    const ct = res.headers.get('content-type') ?? '';
                    if (ct.includes('application/json')) body = await res.json();
                } catch {}
                const msg = (body && body.message) || 'Erro ao alterar senha';
                showAlert('error', msg);
                return;
            }

            let json: any = null;
            try {
                const ct = res.headers.get('content-type') ?? '';
                if (ct.includes('application/json')) json = await res.json();
            } catch (err) {
                console.warn('[password-modal] não foi possível parsear JSON da resposta', err);
            }

            // Fecha modal
            try {
                const bsModal =
                    (bootstrap?.Modal?.getOrCreateInstance && bootstrap.Modal.getOrCreateInstance(modalEl)) ||
                    (bootstrap?.Modal?.getInstance && bootstrap.Modal.getInstance(modalEl)) ||
                    new bootstrap.Modal(modalEl);
                bsModal?.hide();
            } catch (err) {
                console.warn('[password-modal] fallback para fechar modal', err);
            }

            form!.reset();
            showAlert('success', json?.status ?? 'Senha alterada com sucesso!', 2000);

        } catch (err) {
            console.error('[password-modal] submit error', err);
            showAlert('error', 'Erro de rede. Tente novamente.');
        } finally {
            if (submitBtn && btnText && spinner) {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        }
    });
});
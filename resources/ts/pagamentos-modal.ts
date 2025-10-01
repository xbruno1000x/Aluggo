import { showAlert, showConfirm } from './utils/alert';
import * as bootstrap from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('partialPaymentModal') as HTMLElement | null;
    const form = document.getElementById('partialPaymentForm') as HTMLFormElement | null;
    const valorInput = document.getElementById('valor_recebido_input') as HTMLInputElement | null;
    const observacaoInput = document.getElementById('observacao_input') as HTMLTextAreaElement | null;

    if (!modalEl || !form || !valorInput || !observacaoInput) return;

    const getModalInstance = () => {
        return (
            (bootstrap?.Modal?.getOrCreateInstance && bootstrap.Modal.getOrCreateInstance(modalEl)) ||
            (bootstrap?.Modal?.getInstance && bootstrap.Modal.getInstance(modalEl)) ||
            new bootstrap.Modal(modalEl)
        );
    };

    // Open modal buttons
    document.querySelectorAll('.open-partial-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const el = e.currentTarget as HTMLElement;
            const action = el.getAttribute('data-action') ?? '';
            const valorDevido = el.getAttribute('data-valor-devido') ?? '0';

            form.setAttribute('action', action);
            // pre-fill with BR format using comma
            const v = parseFloat(valorDevido) || 0;
            valorInput.value = v.toFixed(2).replace('.', ',');
            observacaoInput.value = '';

            try { getModalInstance().show(); } catch (err) { console.warn('[pagamentos-modal] show modal failed', err); }
        });
    });

    // Helper: normalize BR format to dot decimal
    function normalizeBrazilianNumber(input: string): string {
        let s = (input || '').trim();
        s = s.replace(/\s+/g, '');
        // remove thousand separators (dots) that appear before groups of 3 digits
        s = s.replace(/\.(?=\d{3}(?:[^\d]|$))/g, '');
        // replace comma with dot
        s = s.replace(/,/, '.');
        return s;
    }

    async function postForm(action: string, data: URLSearchParams) {
        if (!action) throw new Error('Form action not set');

        // Use fetch to submit so we can show inline errors without a full page reload
        const res = await fetch(action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''
            },
            body: data.toString()
        });
        return res;
    }

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const raw = valorInput.value || '';
        const norm = normalizeBrazilianNumber(raw);
        const num = parseFloat(norm);
        if (!isFinite(num) || num < 0) {
            showAlert('error', 'Informe um valor válido para o pagamento.');
            return;
        }

        // Prepare payload
        const payload = new URLSearchParams();
        payload.set('valor_recebido', num.toFixed(2));
        payload.set('observacao', observacaoInput.value || '');

        try {
            const action = form.getAttribute('action') ?? '';
            const res = await postForm(action, payload);
            if (!res.ok) {
                let body: any = null;
                try { body = await res.json(); } catch (err) { }
                const msg = (body && body.message) || 'Erro ao registrar pagamento';
                showAlert('error', msg);
                return;
            }

            // Success: hide modal, optionally reload to reflect changes
            try { getModalInstance().hide(); } catch (err) { }
            showAlert('success', 'Pagamento registrado com sucesso', 1500);

            // Small delay then reload page to reflect updated status (server-side pagination)
            setTimeout(() => { window.location.reload(); }, 800);

        } catch (err) {
            console.error('[pagamentos-modal] submit error', err);
            showAlert('error', 'Erro de rede. Tente novamente.');
        }
    });

    // SweetAlert confirm + AJAX submit for the "marcar todos como pagos" form
    const markAllForm = document.getElementById('mark-all-form') as HTMLFormElement | null;
    if (markAllForm) {
        markAllForm.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const confirmed = await showConfirm('Marcar todos como pagos', 'Deseja marcar TODOS os pagamentos visíveis como pagos? Essa ação não pode ser revertida facilmente.');
            if (!confirmed) return;

            const action = markAllForm.getAttribute('action') ?? '';
            const formData = new FormData(markAllForm);
            const payload = new URLSearchParams();
            for (const [k, v] of formData.entries()) payload.set(k, String(v));

            try {
                const res = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''
                    },
                    body: payload.toString()
                });

                if (!res.ok) {
                    let body: any = null;
                    try { body = await res.json(); } catch (err) { }
                    const msg = (body && body.message) || 'Erro ao marcar todos como pagos';
                    showAlert('error', msg);
                    return;
                }

                showAlert('success', 'Todos os pagamentos foram marcados como pagos.', 1500);
                setTimeout(() => window.location.reload(), 700);

            } catch (err) {
                console.error('[mark-all] error', err);
                showAlert('error', 'Erro de rede. Tente novamente.');
            }
        });
    }

});

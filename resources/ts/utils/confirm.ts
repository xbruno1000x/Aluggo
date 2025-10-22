/**
 * Utilitário para interceptar submits de formulários de exclusão e
 * mostrar a confirmação usando as funções do utils/alert.ts.
 *
 * Uso:
 *   import { initDeleteConfirm } from 'resources/ts/utils/confirm';
 *   initDeleteConfirm();
 */

import { showConfirm, showAlert } from './alert';

export interface DeleteConfirmOptions {
    selector?: string;
    buttonSelector?: string;
    defaultTitle?: string;
    defaultText?: string;
    confirmButtonText?: string;
    cancelButtonText?: string;
    canceledMessage?: string; // mensagem opcional ao cancelar
    canceledIcon?: 'success' | 'error' | 'info' | 'warning';
    canceledTimer?: number;
}

export function initDeleteConfirm(opts: DeleteConfirmOptions = {}): void {
    const {
        selector = 'form[data-confirm]',
        defaultTitle = 'Confirmação',
        defaultText = 'Deseja realmente excluir este item?',
        confirmButtonText = 'Confirmar',
        cancelButtonText = 'Cancelar',
        canceledMessage = 'Ação cancelada.',
        canceledIcon = 'info',
        canceledTimer = 1200
    } = opts;

    document.querySelectorAll<HTMLFormElement>(selector).forEach(form => {
        if ((form.dataset as any).__confirmInit) return;
        (form.dataset as any).__confirmInit = '1';

        const submitHandler = async (e: Event) => {
            e.preventDefault();
            e.stopPropagation();

            const submitBtn = form.querySelector<HTMLButtonElement>('button[type="submit"]');
            const btnText = submitBtn?.querySelector<HTMLElement>('.btn-text');
            const spinner = submitBtn?.querySelector<HTMLElement>('.spinner-border');

            const title = form.dataset.confirmTitle || defaultTitle;
            const text = form.dataset.confirmText || defaultText;

            const ok = await showConfirm(title, text, confirmButtonText, cancelButtonText);

            if (ok) {
                if (submitBtn && btnText && spinner) {
                    submitBtn.disabled = true;
                    btnText.classList.add('d-none');
                    spinner.classList.remove('d-none');
                }
                form.removeEventListener('submit', submitHandler);
                
                const newSubmitEvent = new Event('submit', { cancelable: false, bubbles: true });
                (newSubmitEvent as any).__fromConfirm = true;
                form.dispatchEvent(newSubmitEvent);
                
                form.submit();
            } else {
                if (submitBtn && btnText && spinner) {
                    submitBtn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                }
                try {
                    showAlert(
                        canceledIcon,
                        form.dataset.confirmCanceledText || canceledMessage,
                        canceledTimer
                    );
                } catch {
                }
            }
        };

        form.addEventListener('submit', submitHandler);
    });
}

export default initDeleteConfirm;
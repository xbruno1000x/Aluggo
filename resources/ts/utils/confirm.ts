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
        confirmButtonText = 'Sim, excluir',
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
            const title = form.dataset.confirmTitle || defaultTitle;
            const text = form.dataset.confirmText || defaultText;

            const ok = await showConfirm(title, text, confirmButtonText, cancelButtonText);

            if (ok) {
                form.submit();
            } else {
                try {
                    showAlert(
                        canceledIcon,
                        form.dataset.confirmCanceledText || canceledMessage,
                        canceledTimer
                    );
                } catch {
                    // no-op
                }
            }
        };

        form.addEventListener('submit', submitHandler);
    });
}

export default initDeleteConfirm;
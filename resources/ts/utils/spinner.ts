/**
 * Utilitário para adicionar spinner em botões de submit de formulários,
 * prevenindo duplicação de requisições.
 *
 * Uso:
 *   import { initFormSpinner } from 'resources/ts/utils/spinner';
 *   initFormSpinner();
 */

export interface FormSpinnerOptions {
    /**
     * Seletor CSS para os formulários que devem ter spinner.
     * @default 'form[data-spinner]'
     */
    selector?: string;

    /**
     * Seletor CSS do botão de submit dentro do formulário.
     * Se não especificado, procura por button[type="submit"] ou o ID do data-spinner-button.
     * @default 'button[type="submit"]'
     */
    buttonSelector?: string;

    /**
     * Classe do elemento que contém o texto do botão.
     * @default 'btn-text'
     */
    textClass?: string;

    /**
     * Classe do elemento spinner.
     * @default 'spinner-border'
     */
    spinnerClass?: string;

    /**
     * Se true, previne múltiplos submits do mesmo formulário.
     * @default true
     */
    preventMultipleSubmits?: boolean;
}

export function initFormSpinner(opts: FormSpinnerOptions = {}): void {
    const {
        selector = 'form[data-spinner]',
        buttonSelector = 'button[type="submit"]',
        textClass = 'btn-text',
        spinnerClass = 'spinner-border',
        preventMultipleSubmits = true
    } = opts;

    document.querySelectorAll<HTMLFormElement>(selector).forEach(form => {
        // Evita inicializar múltiplas vezes
        if ((form.dataset as any).__spinnerInit) return;
        (form.dataset as any).__spinnerInit = '1';

        // Determina o botão de submit
        const customButtonId = form.dataset.spinnerButton;
        let submitBtn: HTMLButtonElement | null = null;

        if (customButtonId) {
            submitBtn = document.getElementById(customButtonId) as HTMLButtonElement | null;
        } else {
            submitBtn = form.querySelector<HTMLButtonElement>(buttonSelector);
        }

        if (!submitBtn) return;

        const btnText = submitBtn.querySelector<HTMLElement>(`.${textClass}`);
        const spinner = submitBtn.querySelector<HTMLElement>(`.${spinnerClass}`);

        if (!btnText || !spinner) {
            console.warn('[FormSpinner] Botão deve conter elementos com classes:', textClass, spinnerClass);
            return;
        }

        const submitHandler = (e: Event) => {
            if (form.hasAttribute('data-confirm') && !(e as any).__fromConfirm) {
                return;
            }

            if (preventMultipleSubmits && submitBtn!.disabled) {
                e.preventDefault();
                return;
            }

            submitBtn!.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            const timeout = parseInt(form.dataset.spinnerTimeout || '0', 10);
            if (timeout > 0) {
                setTimeout(() => {
                    submitBtn!.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                }, timeout);
            }
        };

        form.addEventListener('submit', submitHandler);
    });
}

/**
 * Versão simplificada que aceita um ID de botão diretamente.
 * Útil para formulários customizados sem data-spinner.
 */
export function enableButtonSpinner(buttonId: string): void {
    const submitBtn = document.getElementById(buttonId) as HTMLButtonElement | null;
    if (!submitBtn) return;

    const btnText = submitBtn.querySelector<HTMLElement>('.btn-text');
    const spinner = submitBtn.querySelector<HTMLElement>('.spinner-border');

    if (!btnText || !spinner) {
        console.warn('[FormSpinner] Botão deve conter .btn-text e .spinner-border');
        return;
    }

    const form = submitBtn.closest('form');
    if (!form) return;

    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        spinner.classList.remove('d-none');
    });
}

export default initFormSpinner;

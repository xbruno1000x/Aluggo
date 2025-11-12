/**
 * Gerencia os modais de alteração de e-mail e telefone
 */

// Declaração do Bootstrap para TypeScript
declare const bootstrap: any;

document.addEventListener('DOMContentLoaded', () => {
    initEmailModal();
    initPhoneModal();
});

/**
 * Obtém o token CSRF da meta tag
 * O Laravel já fornece o token descriptografado na meta tag
 */
function getCsrfToken(): string {
    // Pega do meta tag (token já descriptografado pelo Laravel)
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';
}

/**
 * Inicializa o modal de alteração de e-mail
 */
function initEmailModal(): void {
    const form = document.getElementById('email-form') as HTMLFormElement | null;
    if (!form) return;

    const alert = document.getElementById('email-form-alert') as HTMLElement | null;
    const submitBtn = document.getElementById('btn-submit-email') as HTMLButtonElement | null;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!alert || !submitBtn) return;

        // Elementos do spinner
        const btnText = submitBtn.querySelector('.btn-text') as HTMLElement | null;
        const spinner = submitBtn.querySelector('.spinner-border') as HTMLElement | null;

        // Função para resetar o botão
        const resetButton = () => {
            if (submitBtn && btnText && spinner) {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        };

        // Limpa alertas anteriores
        alert.classList.add('d-none');
        alert.classList.remove('alert-success', 'alert-danger');
        alert.textContent = '';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Remove o _token e _method do objeto data pois serão enviados como headers
        delete data._token;
        delete data._method;

        try {
            const response = await fetch(form.action, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest', // Identifica como requisição AJAX
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (response.ok) {
                // Sucesso
                alert.classList.remove('d-none', 'alert-danger');
                alert.classList.add('alert-success');
                alert.textContent = result.status || 'E-mail alterado com sucesso!';

                // Atualiza o e-mail atual exibido
                const currentEmailInput = document.getElementById('current_email') as HTMLInputElement | null;
                if (currentEmailInput && result.email) {
                    currentEmailInput.value = result.email;
                }

                // Limpa o formulário
                form.reset();

                // Reseta o botão
                resetButton();

                // Fecha o modal após 2 segundos
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('emailModal')!);
                    modal?.hide();
                    
                    // Limpa o alerta ao fechar
                    setTimeout(() => {
                        alert.classList.add('d-none');
                    }, 300);
                }, 2000);
            } else {
                // Erro
                alert.classList.remove('d-none', 'alert-success');
                alert.classList.add('alert-danger');
                alert.textContent = result.message || 'Erro ao alterar e-mail.';
                
                // Reseta o botão
                resetButton();
            }
        } catch (error) {
            alert.classList.remove('d-none', 'alert-success');
            alert.classList.add('alert-danger');
            alert.textContent = 'Erro ao processar a solicitação.';
            
            // Reseta o botão
            resetButton();
        }
    });
}

/**
 * Inicializa o modal de alteração de telefone
 */
function initPhoneModal(): void {
    const form = document.getElementById('phone-form') as HTMLFormElement | null;
    if (!form) return;

    const alert = document.getElementById('phone-form-alert') as HTMLElement | null;
    const submitBtn = document.getElementById('btn-submit-phone') as HTMLButtonElement | null;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!alert || !submitBtn) return;

        // Elementos do spinner
        const btnText = submitBtn.querySelector('.btn-text') as HTMLElement | null;
        const spinner = submitBtn.querySelector('.spinner-border') as HTMLElement | null;

        // Função para resetar o botão
        const resetButton = () => {
            if (submitBtn && btnText && spinner) {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        };

        // Limpa alertas anteriores
        alert.classList.add('d-none');
        alert.classList.remove('alert-success', 'alert-danger');
        alert.textContent = '';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Remove o _token e _method do objeto data pois serão enviados como headers
        delete data._token;
        delete data._method;

        try {
            const response = await fetch(form.action, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest', // Identifica como requisição AJAX
                },
                credentials: 'same-origin', // Importante para enviar cookies
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (response.ok) {
                // Sucesso
                alert.classList.remove('d-none', 'alert-danger');
                alert.classList.add('alert-success');
                alert.textContent = result.status || 'Telefone alterado com sucesso!';

                // Atualiza o telefone atual exibido
                const currentPhoneInput = document.getElementById('current_phone') as HTMLInputElement | null;
                if (currentPhoneInput && result.telefone) {
                    currentPhoneInput.value = result.telefone;
                }

                // Limpa o formulário
                form.reset();

                // Reseta o botão
                resetButton();

                // Fecha o modal após 2 segundos
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('phoneModal')!);
                    modal?.hide();
                    
                    // Limpa o alerta ao fechar
                    setTimeout(() => {
                        alert.classList.add('d-none');
                    }, 300);
                }, 2000);
            } else {
                // Erro
                alert.classList.remove('d-none', 'alert-success');
                alert.classList.add('alert-danger');
                alert.textContent = result.message || 'Erro ao alterar telefone.';
                
                // Reseta o botão
                resetButton();
            }
        } catch (error) {
            alert.classList.remove('d-none', 'alert-success');
            alert.classList.add('alert-danger');
            alert.textContent = 'Erro ao processar a solicitação.';
            
            // Reseta o botão
            resetButton();
        }
    });
}

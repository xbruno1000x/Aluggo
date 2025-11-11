/**
 * Validador de força de senha
 * Mostra uma barra de progresso indicando a força da senha
 */

interface PasswordStrengthConfig {
    passwordInputId: string;
    confirmPasswordInputId?: string;
    showProgressBar?: boolean;
}

interface PasswordValidation {
    score: number;
    strength: 'weak' | 'fair' | 'good' | 'strong';
    color: string;
    percentage: number;
    messages: string[];
    isValid: boolean;
}

class PasswordStrength {
    private passwordInput!: HTMLInputElement;
    private confirmPasswordInput: HTMLInputElement | null = null;
    private progressContainer: HTMLElement | null = null;
    private progressBar: HTMLElement | null = null;
    private messagesContainer: HTMLElement | null = null;
    private showProgressBar: boolean = true;

    constructor(config: PasswordStrengthConfig) {
        const passwordInput = document.getElementById(config.passwordInputId) as HTMLInputElement;
        
        if (!passwordInput) {
            console.warn(`Password input with id "${config.passwordInputId}" not found`);
            return;
        }

        this.passwordInput = passwordInput;
        this.confirmPasswordInput = config.confirmPasswordInputId 
            ? document.getElementById(config.confirmPasswordInputId) as HTMLInputElement 
            : null;
        this.showProgressBar = config.showProgressBar !== false;

        this.init();
    }

    private init(): void {
        if (this.showProgressBar) {
            this.createProgressBar();
        }
        this.createMessagesContainer();
        this.attachEventListeners();
    }

    private createProgressBar(): void {
        this.progressContainer = document.createElement('div');
        this.progressContainer.className = 'password-strength-container mt-2';
        
        this.progressBar = document.createElement('div');
        this.progressBar.className = 'progress';
        this.progressBar.style.height = '10px';
        
        const progressBarFill = document.createElement('div');
        progressBarFill.className = 'progress-bar';
        progressBarFill.id = `${this.passwordInput.id}-strength-bar`;
        progressBarFill.setAttribute('role', 'progressbar');
        progressBarFill.style.width = '0%';
        
        this.progressBar.appendChild(progressBarFill);
        this.progressContainer.appendChild(this.progressBar);
        
        // Inserir após o input de senha
        this.passwordInput.parentElement?.insertBefore(
            this.progressContainer, 
            this.passwordInput.nextSibling
        );
    }

    private createMessagesContainer(): void {
        this.messagesContainer = document.createElement('div');
        this.messagesContainer.className = 'password-requirements mt-2';
        this.messagesContainer.id = `${this.passwordInput.id}-requirements`;
        
        // Inserir após a barra de progresso ou após o input
        const insertAfter = this.progressContainer || this.passwordInput;
        insertAfter.parentElement?.insertBefore(
            this.messagesContainer,
            insertAfter.nextSibling
        );
    }

    private attachEventListeners(): void {
        this.passwordInput.addEventListener('input', () => {
            this.validatePassword();
        });

        if (this.confirmPasswordInput) {
            this.confirmPasswordInput.addEventListener('input', () => {
                this.validatePasswordMatch();
            });
        }

        // Validação no submit do formulário
        const form = this.passwordInput.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                const validation = this.validatePassword();
                if (!validation.isValid) {
                    e.preventDefault();
                    this.showError('Por favor, corrija os requisitos de senha antes de continuar.');
                }
            });
        }
    }

    private validatePassword(): PasswordValidation {
        const password = this.passwordInput.value;
        const validation = this.checkPasswordStrength(password);
        
        this.updateUI(validation);
        
        return validation;
    }

    private checkPasswordStrength(password: string): PasswordValidation {
        const messages: string[] = [];
        let score = 0;

        // Requisitos mínimos
        const hasMinLength = password.length >= 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSymbol = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

        // Verificar requisitos
        if (!hasMinLength) {
            messages.push('Mínimo de 8 caracteres');
        } else {
            score += 20;
        }

        if (!hasUpperCase) {
            messages.push('Uma letra maiúscula');
        } else {
            score += 20;
        }

        if (!hasLowerCase) {
            messages.push('Uma letra minúscula');
        } else {
            score += 20;
        }

        if (!hasNumber) {
            messages.push('Um número');
        } else {
            score += 20;
        }

        if (!hasSymbol) {
            messages.push('Um símbolo (!@#$%^&* etc)');
        } else {
            score += 20;
        }

        // Bônus para senhas mais longas
        if (password.length >= 12) {
            score += 10;
        }

        // Determinar força
        let strength: 'weak' | 'fair' | 'good' | 'strong';
        let color: string;
        let percentage: number;

        if (score < 40) {
            strength = 'weak';
            color = 'bg-danger';
            percentage = 25;
        } else if (score < 70) {
            strength = 'fair';
            color = 'bg-warning';
            percentage = 50;
        } else if (score < 100) {
            strength = 'good';
            color = 'bg-info';
            percentage = 75;
        } else {
            strength = 'strong';
            color = 'bg-success';
            percentage = 100;
        }

        const isValid = hasMinLength && hasUpperCase && hasLowerCase && hasNumber && hasSymbol;

        return {
            score,
            strength,
            color,
            percentage,
            messages,
            isValid
        };
    }

    private updateUI(validation: PasswordValidation): void {
        // Atualizar barra de progresso
        if (this.progressBar) {
            const progressBarFill = this.progressBar.querySelector('.progress-bar') as HTMLElement;
            if (progressBarFill) {
                progressBarFill.style.width = `${validation.percentage}%`;
                progressBarFill.className = `progress-bar ${validation.color}`;
                
                const strengthText = {
                    weak: 'Fraca',
                    fair: 'Razoável',
                    good: 'Boa',
                    strong: 'Forte'
                };
                
                progressBarFill.setAttribute('aria-valuenow', validation.percentage.toString());
                progressBarFill.textContent = this.passwordInput.value ? strengthText[validation.strength] : '';
            }
        }

        // Atualizar mensagens de requisitos
        if (this.messagesContainer) {
            if (validation.messages.length > 0 && this.passwordInput.value) {
                this.messagesContainer.innerHTML = `
                    <small class="text-muted">
                        <strong>Requisitos faltantes:</strong>
                        <ul class="mb-0 ps-3">
                            ${validation.messages.map(msg => `<li>${msg}</li>`).join('')}
                        </ul>
                    </small>
                `;
            } else if (this.passwordInput.value) {
                this.messagesContainer.innerHTML = `
                    <small class="text-success">
                        <i class="bi bi-check-circle-fill"></i> Senha forte! Todos os requisitos atendidos.
                    </small>
                `;
            } else {
                this.messagesContainer.innerHTML = '';
            }
        }

        // Adicionar feedback visual no input
        if (this.passwordInput.value) {
            if (validation.isValid) {
                this.passwordInput.classList.remove('is-invalid');
                this.passwordInput.classList.add('is-valid');
            } else {
                this.passwordInput.classList.remove('is-valid');
                this.passwordInput.classList.add('is-invalid');
            }
        } else {
            this.passwordInput.classList.remove('is-valid', 'is-invalid');
        }
    }

    private validatePasswordMatch(): void {
        if (!this.confirmPasswordInput) return;

        const password = this.passwordInput.value;
        const confirmPassword = this.confirmPasswordInput.value;

        if (confirmPassword && password !== confirmPassword) {
            this.confirmPasswordInput.classList.add('is-invalid');
            this.confirmPasswordInput.classList.remove('is-valid');
            
            // Criar ou atualizar mensagem de erro
            let feedback = this.confirmPasswordInput.nextElementSibling;
            if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                this.confirmPasswordInput.parentElement?.insertBefore(
                    feedback,
                    this.confirmPasswordInput.nextSibling
                );
            }
            feedback.textContent = 'As senhas não coincidem';
        } else if (confirmPassword) {
            this.confirmPasswordInput.classList.remove('is-invalid');
            this.confirmPasswordInput.classList.add('is-valid');
            
            // Remover mensagem de erro
            const feedback = this.confirmPasswordInput.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = '';
            }
        } else {
            this.confirmPasswordInput.classList.remove('is-valid', 'is-invalid');
        }
    }

    private showError(message: string): void {
        // Criar alert temporário
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        const form = this.passwordInput.closest('form');
        if (form) {
            form.insertBefore(alertDiv, form.firstChild);
            
            // Auto remover após 5 segundos
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('password')) {
        new PasswordStrength({
            passwordInputId: 'password',
            confirmPasswordInputId: 'password_confirmation',
            showProgressBar: true
        });
    }
});

export default PasswordStrength;

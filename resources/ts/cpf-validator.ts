/**
 * Validador e formatador de CPF para formulários
 * 
 * Uso:
 * @vite(['resources/ts/cpf-validator.ts'])
 * 
 * Adicione o atributo data-cpf-input no campo de CPF
 */

/**
 * Valida um CPF
 */
export function validateCPF(cpf: string): boolean {
    const cleanCPF = cpf.replace(/[^\d]/g, '');
    
    if (cleanCPF.length !== 11) {
        return false;
    }
    
    if (/^(\d)\1{10}$/.test(cleanCPF)) {
        return false;
    }
    
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cleanCPF.charAt(i)) * (10 - i);
    }
    let remainder = sum % 11;
    const digit1 = remainder < 2 ? 0 : 11 - remainder;
    
    if (parseInt(cleanCPF.charAt(9)) !== digit1) {
        return false;
    }
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cleanCPF.charAt(i)) * (11 - i);
    }
    remainder = sum % 11;
    const digit2 = remainder < 2 ? 0 : 11 - remainder;
    
    if (parseInt(cleanCPF.charAt(10)) !== digit2) {
        return false;
    }
    
    return true;
}

/**
 * Formata CPF para o padrão XXX.XXX.XXX-XX
 */
export function formatCPF(cpf: string): string {
    const cleanCPF = cpf.replace(/[^\d]/g, '');
    
    if (cleanCPF.length <= 3) {
        return cleanCPF;
    } else if (cleanCPF.length <= 6) {
        return `${cleanCPF.slice(0, 3)}.${cleanCPF.slice(3)}`;
    } else if (cleanCPF.length <= 9) {
        return `${cleanCPF.slice(0, 3)}.${cleanCPF.slice(3, 6)}.${cleanCPF.slice(6)}`;
    } else {
        return `${cleanCPF.slice(0, 3)}.${cleanCPF.slice(3, 6)}.${cleanCPF.slice(6, 9)}-${cleanCPF.slice(9, 11)}`;
    }
}

/**
 * Inicializa a validação de CPF em campos marcados com data-cpf-input
 */
export function initCPFValidator(): void {
    document.querySelectorAll<HTMLInputElement>('[data-cpf-input]').forEach(input => {
        input.addEventListener('input', (e) => {
            const target = e.target as HTMLInputElement;
            const cursorPosition = target.selectionStart || 0;
            const oldValue = target.value;
            const oldLength = oldValue.length;
            
            target.value = formatCPF(target.value);
            
            const newLength = target.value.length;
            const diff = newLength - oldLength;
            target.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
        });
        
        input.addEventListener('blur', (e) => {
            const target = e.target as HTMLInputElement;
            const cpf = target.value.replace(/[^\d]/g, '');
            
            target.classList.remove('is-valid', 'is-invalid');
            
            if (cpf.length > 0) {
                const feedbackElement = target.parentElement?.querySelector('.invalid-feedback');
                
                if (!validateCPF(target.value)) {
                    target.classList.add('is-invalid');
                    
                    if (feedbackElement) {
                        feedbackElement.textContent = 'CPF inválido.';
                    } else {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = 'CPF inválido.';
                        target.parentElement?.appendChild(feedback);
                    }
                } else {
                    target.classList.add('is-valid');
                }
            }
        });

        input.addEventListener('invalid', (e) => {
            e.preventDefault();
            const target = e.target as HTMLInputElement;
            
            if (!target.value) {
                target.setCustomValidity('O campo CPF é obrigatório.');
            } else if (!validateCPF(target.value)) {
                target.setCustomValidity('CPF inválido.');
            } else {
                target.setCustomValidity('');
            }
        });
        
        input.addEventListener('input', (e) => {
            const target = e.target as HTMLInputElement;
            target.setCustomValidity('');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initCPFValidator();
});

export default initCPFValidator;

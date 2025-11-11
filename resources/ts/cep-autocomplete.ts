/**
 * Script para preenchimento automático de endereço com base no CEP
 * Utiliza a API ViaCEP (https://viacep.com.br/)
 * Funciona tanto para o modal (IDs com prefixo 'p_') quanto para formulários normais
 */

interface ViaCepResponse {
    cep: string;
    logradouro: string;
    complemento: string;
    bairro: string;
    localidade: string;
    uf: string;
    ibge: string;
    gia: string;
    ddd: string;
    siafi: string;
    erro?: boolean;
}

interface CepFields {
    cep: HTMLInputElement;
    endereco: HTMLInputElement | null;
    bairro: HTMLInputElement | null;
    cidade: HTMLInputElement | null;
    estado: HTMLInputElement | null;
}

/**
 * Inicializa o autopreenchimento de CEP para um formulário
 */
function initCepAutocomplete(prefix: string = ''): void {
    const cepId = prefix ? `${prefix}cep` : 'cep';
    const cepInput = document.getElementById(cepId) as HTMLInputElement;
    
    if (!cepInput) {
        return;
    }

    const fields: CepFields = {
        cep: cepInput,
        endereco: document.getElementById(prefix ? `${prefix}endereco` : 'endereco') as HTMLInputElement,
        bairro: document.getElementById(prefix ? `${prefix}bairro` : 'bairro') as HTMLInputElement,
        cidade: document.getElementById(prefix ? `${prefix}cidade` : 'cidade') as HTMLInputElement,
        estado: document.getElementById(prefix ? `${prefix}estado` : 'estado') as HTMLInputElement,
    };

    // Formatar CEP enquanto digita
    cepInput.addEventListener('input', (e) => {
        const target = e.target as HTMLInputElement;
        let value = target.value.replace(/\D/g, '');
        
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5, 8);
        }
        
        target.value = value;
        
        // Desbloquear campos quando o CEP for alterado
        desbloquearCampos(fields);
    });

    // Buscar endereço quando o CEP estiver completo
    cepInput.addEventListener('blur', async () => {
        const cep = cepInput.value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            return;
        }

        await buscarEnderecoPorCep(cep, fields, prefix);
    });
}

/**
 * Desbloqueia os campos de endereço para edição manual
 */
function desbloquearCampos(fields: CepFields): void {
    const campos = [fields.endereco, fields.bairro, fields.cidade, fields.estado];

    campos.forEach(campo => {
        if (campo) {
            campo.readOnly = false;
            campo.classList.remove('bg-light', 'text-muted');
        }
    });
}

/**
 * Busca os dados do endereço na API ViaCEP
 */
async function buscarEnderecoPorCep(cep: string, fields: CepFields, prefix: string): Promise<void> {
    // Adicionar indicador de carregamento
    if (fields.cep) {
        fields.cep.classList.add('is-loading');
        fields.cep.disabled = true;
    }

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        
        if (!response.ok) {
            throw new Error('Erro ao buscar CEP');
        }

        const data: ViaCepResponse = await response.json();

        if (data.erro) {
            mostrarErro('CEP não encontrado', prefix);
            return;
        }

        // Preencher os campos com os dados retornados e trancá-los
        if (fields.endereco && data.logradouro) {
            fields.endereco.value = data.logradouro;
            fields.endereco.readOnly = true;
            fields.endereco.classList.add('bg-light', 'text-muted');
        }

        if (fields.bairro && data.bairro) {
            fields.bairro.value = data.bairro;
            fields.bairro.readOnly = true;
            fields.bairro.classList.add('bg-light', 'text-muted');
        }

        if (fields.cidade && data.localidade) {
            fields.cidade.value = data.localidade;
            fields.cidade.readOnly = true;
            fields.cidade.classList.add('bg-light', 'text-muted');
        }

        if (fields.estado && data.uf) {
            fields.estado.value = data.uf;
            fields.estado.readOnly = true;
            fields.estado.classList.add('bg-light', 'text-muted');
        }

        // Adicionar feedback visual de sucesso
        if (fields.cep) {
            fields.cep.classList.add('is-valid');
            setTimeout(() => {
                fields.cep.classList.remove('is-valid');
            }, 2000);
        }

    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
        mostrarErro('Erro ao buscar CEP. Tente novamente.', prefix);
    } finally {
        // Remover indicador de carregamento
        if (fields.cep) {
            fields.cep.classList.remove('is-loading');
            fields.cep.disabled = false;
        }
    }
}

/**
 * Exibe mensagem de erro no formulário
 */
function mostrarErro(mensagem: string, prefix: string): void {
    // Para modal (com prefixo 'p_'), usar o alert específico
    if (prefix === 'p_') {
        const alertDiv = document.getElementById('propriedade-form-alert');
        
        if (alertDiv) {
            alertDiv.className = 'alert alert-warning';
            alertDiv.textContent = mensagem;
            alertDiv.classList.remove('d-none');

            setTimeout(() => {
                alertDiv.classList.add('d-none');
            }, 5000);
            return;
        }
    }

    // Para formulários normais, criar alert dinâmico
    const form = document.querySelector('form');
    
    if (!form) {
        return;
    }

    let alertDiv = form.querySelector('.alert-cep') as HTMLElement;
    
    if (!alertDiv) {
        alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning alert-cep';
        alertDiv.setAttribute('role', 'alert');
        form.insertBefore(alertDiv, form.firstChild);
    }

    alertDiv.textContent = mensagem;
    alertDiv.classList.remove('d-none');

    setTimeout(() => {
        alertDiv.classList.add('d-none');
    }, 5000);
}

// Inicializar para ambos os contextos quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Tentar inicializar para modal (com prefixo 'p_')
    initCepAutocomplete('p_');
    
    // Tentar inicializar para formulário normal (sem prefixo)
    initCepAutocomplete('');
});

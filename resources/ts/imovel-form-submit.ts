/**
 * Previne duplicação de requisições no formulário de cadastro/edição de imóveis
 * usando o utilitário genérico de spinner.
 */

import { enableButtonSpinner } from './utils/spinner';

document.addEventListener('DOMContentLoaded', () => {
    enableButtonSpinner('btn-submit-imovel');
});

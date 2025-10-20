/**
 * Inicializa spinners em formulários marcados com data-spinner.
 * 
 * Uso na view Blade:
 * <form data-spinner method="POST" action="...">
 *   <button type="submit" id="btn-submit">
 *     <span class="btn-text">Salvar</span>
 *     <span class="spinner-border spinner-border-sm d-none"></span>
 *   </button>
 * </form>
 * 
 * Opções via data attributes:
 * - data-spinner-button="id-do-botao" (opcional, se o botão não for type="submit")
 * - data-spinner-timeout="5000" (opcional, tempo em ms para restaurar o botão)
 */

import initFormSpinner from './utils/spinner';

document.addEventListener('DOMContentLoaded', () => {
    initFormSpinner();
});

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Mostra alerta rápido no canto superior direito
 */
export function showAlert(
    icon: 'success' | 'error' | 'info' | 'warning',
    message: string,
    timer = 0
): void {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title: message,
        showConfirmButton: false,
        timer: timer || 3000,
        timerProgressBar: !!timer,
        customClass: { container: 'sa-container' },
        didOpen: () => {
            const c = document.querySelector('.sa-container') as HTMLElement | null;
            if (c) c.style.zIndex = '20000';
        },
        theme: 'dark'
    });
}

/**
 * Mostra caixa de confirmação centralizada.
 * Retorna uma Promise indicando se o usuário confirmou ou cancelou.
 */
export function showConfirm(
    title: string,
    text: string,
    confirmButtonText = 'Continuar',
    cancelButtonText = 'Cancelar'
): Promise<boolean> {
    return Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
        focusCancel: true,
        theme: 'dark'
    }).then(result => result.isConfirmed);
}

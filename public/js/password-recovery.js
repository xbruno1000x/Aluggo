document.addEventListener('DOMContentLoaded', () => {
    const emailForm = document.getElementById('email-form');
    const faForm = document.getElementById('2fa-form');
    const emailButton = document.getElementById('email-method');
    const faButton = document.getElementById('2fa-method');

    if (!emailForm || !faForm || !emailButton || !faButton) return;

    emailButton.addEventListener('click', () => {
        emailForm.classList.remove('d-none');
        emailForm.classList.add('d-block');

        faForm.classList.remove('d-block');
        faForm.classList.add('d-none');

        emailButton.classList.add('active');
        faButton.classList.remove('active');
    });

    faButton.addEventListener('click', () => {
        faForm.classList.remove('d-none');
        faForm.classList.add('d-block');

        emailForm.classList.remove('d-block');
        emailForm.classList.add('d-none');

        faButton.classList.add('active');
        emailButton.classList.remove('active');
    });
});

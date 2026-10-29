import './bootstrap';
import 'virtual:svg-icons-register';
document.addEventListener('submit', async (e) => {
    const form = e.target;

    if (!form.dataset.confirmText) return;
    if (form.dataset.confirmed === '1') return;

    e.preventDefault();

    const resultado = await Swal.fire({
        icon: 'question',
        title: form.dataset.confirmTitle ?? '¿Estás seguro?',
        text: form.dataset.confirmText,
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton ?? 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    if (resultado.isConfirmed) {
        form.dataset.confirmed = '1';
        form.submit();
    }
});
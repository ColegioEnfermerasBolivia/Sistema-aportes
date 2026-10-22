<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });

    @if (session('success'))
        Toast.fire({ icon: 'success', title: @json(session('success')) });
    @endif

    @if (session('error'))
        Toast.fire({ icon: 'error', title: @json(session('error')) });
    @endif

    @if (session('warning'))
        Toast.fire({ icon: 'warning', title: @json(session('warning')) });
    @endif

    @if ($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Revisa los datos',
            html: {!! json_encode('<ul style="text-align:left">' . collect($errors->all())->map(fn($e) => '<li>' . e($e) . '</li>')->implode('') . '</ul>') !!},
        });
    @endif
</script>
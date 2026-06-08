<!-- Script Keamanan: Disable Back Button -->
<script>
    // Mencegah browser menyimpan halaman di cache (Back-Forward Cache)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // Memaksa browser untuk tidak bisa kembali ke halaman sebelumnya
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function () {
        window.history.go(1);
    };
</script>
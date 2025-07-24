<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Perangkat</title>
</head>
<body>
    <h3 id="status-message">Memverifikasi akses perangkat Anda...</h3>

    <script>
        const mac = "{{ $mac }}";
        const statusMessage = document.getElementById('status-message');

        fetch('/check-mac', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ mac: mac })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 1) {
                statusMessage.textContent = "Akses disetujui. Mengarahkan ke login...";
                window.location.href = "/login";
            } else {
                statusMessage.textContent = data.message;
            }
        })
        .catch(err => {
            statusMessage.textContent = "Terjadi kesalahan: " + err;
        });
    </script>
</body>
</html>

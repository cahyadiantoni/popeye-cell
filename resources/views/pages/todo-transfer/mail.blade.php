<!DOCTYPE html>
<html>
<head>
    <title>Status To do Transfer Diperbarui</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h3 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            font-weight: bold;
        }
        .bg-secondary { background-color: #6c757d; }
        .bg-primary { background-color: #007bff; }
        .bg-warning { background-color: #ffc107; color: #000; }
        .bg-danger { background-color: #dc3545; }
        .bg-info { background-color: #17a2b8; color: #000; }
        .bg-success { background-color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h3>Hai, {{ $todoTransfer->user->name ?? 'Pengguna' }}</h3>
        <p>Status permohonan transfer Anda telah diperbarui.</p>

        <table>
            <tr>
                <th>Kode Lokasi</th>
                <td>{{ $todoTransfer->kode_lok }}</td>
            </tr>
            <tr>
                <th>Nama Toko</th>
                <td>{{ $todoTransfer->nama_toko }}</td>
            </tr>
            <tr>
                <th>Tanggal</th>
                <td>{{ \Carbon\Carbon::parse($todoTransfer->tgl)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <th>Nominal</th>
                <td>Rp{{ number_format($todoTransfer->nominal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @php
                        $statusLabel = '';
                        $badgeClass = '';

                        switch ($todoTransfer->status) {
                            case 0:
                                $statusLabel = 'Draft';
                                $badgeClass = 'bg-secondary';
                                break;
                            case 1:
                                $statusLabel = 'Berhasil Terkirim';
                                $badgeClass = 'bg-primary';
                                break;
                            case 2:
                                $statusLabel = 'Perlu Revisi';
                                $badgeClass = 'bg-warning text-dark';
                                break;
                            case 3:
                                $statusLabel = 'Ditolak';
                                $badgeClass = 'bg-danger';
                                break;
                            case 4:
                                $statusLabel = 'Proses Transfer';
                                $badgeClass = 'bg-info text-dark';
                                break;
                            case 5:
                                $statusLabel = 'Sudah Ditransfer';
                                $badgeClass = 'bg-success';
                                break;
                            default:
                                $statusLabel = 'Unknown';
                                $badgeClass = 'bg-secondary';
                        }
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                </td>
            </tr>
        </table>

        <p>Silakan cek aplikasi untuk informasi lebih lanjut.</p>
        <p>Terima kasih,</p>
        <p><strong>Tim Admin</strong></p>
    </div>
</body>
</html>

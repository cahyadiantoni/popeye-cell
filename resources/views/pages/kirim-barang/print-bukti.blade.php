<!DOCTYPE html>
<html>
<head>
    <title>Bukti Kirim Barang</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 10px; }
        .line { border-bottom: 1px solid black; margin-bottom: 15px; }
        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table th, .table td { border: 1px solid black; padding: 5px; text-align: center; }
        .info-table .right-align { text-align: right; }
        .info-table .center-align { text-align: center; }
        .total { font-weight: bold; }
        .footer { font-style: italic; text-align: center; margin-top: 20px; }

        /* Styling badge */
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 400;
        }

        /* Badge untuk status "Dalam Proses" */
        .badge.bg-warning {
            background-color: #ffc107;
            color: #343a40;
        }

        /* Badge untuk status "Diterima" */
        .badge.bg-success {
            background-color: #28a745;
            color: white;
        }

        /* Badge untuk status "Ditolak" */
        .badge.bg-danger {
            background-color: #dc3545;
            color: white;
        }

        /* Badge untuk status default "Tidak Diketahui" */
        .badge.bg-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="title">Bukti Kirim Barang</div>
    <div class="line"></div>

    <div class="info">
        <table class="info-table">
            <tr>
                <td><strong>ID Kirim:</strong><br>{{ $kirim->id }}</td>
                <td class="center-align"><strong>Jumlah Barang:</strong><br>{{ $jumlahBarang }}</td>
                <td class="right-align">
                    <strong>Status:</strong>
                    <br>
                    @switch($kirim->status)
                        @case(0)
                            <span class="badge bg-warning text-dark">Dalam Proses</span>
                            @break
                        @case(1)
                            <span class="badge bg-success">Diterima</span>
                            @break
                        @case(2)
                            <span class="badge bg-danger">Ditolak</span>
                            @break
                        @default
                            <span class="badge bg-secondary">Status Tidak Diketahui</span>
                    @endswitch
                </td>
            </tr>
            <tr>
                <td><strong>Tgl Kirim:</strong><br>{{ \Carbon\Carbon::parse($kirim->dt_kirim)->translatedFormat('d F Y') }}</td>
                <td class="center-align"><strong>User Pengirim:</strong><br>{{ $kirim->pengirimUser->name }}</td>
                <td class="right-align"><strong>Gudang Pengirim:</strong><br>{{ $kirim->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Tgl Terima:</strong><br>{{ $kirim->dt_terima ? \Carbon\Carbon::parse($kirim->dt_terima)->translatedFormat('d F Y') : '-' }}</td>
                <td class="center-align"><strong>User Penerima:</strong><br>{{ $kirim->penerimaUser->name }}</td>
                <td class="right-align"><strong>Gudang Penerima:</strong><br>{{ $kirim->penerimaGudang->nama_gudang ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <h2 class="total">Bubuhkan tanda tangan di bawah ini!</h2>

</body>
</html>

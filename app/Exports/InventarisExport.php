<?php

namespace App\Exports;

use App\Models\Inventaris;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class InventarisExport implements FromQuery, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $gudangId;
    protected $kodeToko;

    public function __construct($startDate, $endDate, $gudangId, $kodeToko)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->gudangId  = $gudangId;
        $this->kodeToko  = $kodeToko;
    }

    /**
     * Metode ini membangun query yang sama persis dengan di controller index,
     * berdasarkan filter yang diterima.
     */
    public function query()
    {
        $query = Inventaris::query()->with('gudang')->latest();

        if ($this->startDate) {
            $query->where('tgl', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('tgl', '<=', $this->endDate);
        }
        if ($this->gudangId) {
            $query->where('gudang_id', $this->gudangId);
        }
        if ($this->kodeToko) {
            $query->where('kode_toko', $this->kodeToko);
        }

        return $query;
    }

    /**
     * Metode ini mendefinisikan baris header untuk file Excel.
     */
    public function headings(): array
    {
        return [
            'Tanggal Pengambilan',
            'Nama',
            'Kode Toko',
            'Nama Toko',
            'Lok SPK',
            'Jenis',
            'Tipe',
            'Kelengkapan',
            'Gudang',
            'Keterangan',
            'Status',
            'Tanggal Gantian',
            'Alasan Gantian',
        ];
    }

    /**
     * Metode ini memetakan setiap baris data ke format yang diinginkan.
     */
    public function map($inventaris): array
    {
        $statusText = '';
        switch ($inventaris->status) {
            case 1:
                $statusText = 'Pengambilan';
                break;
            case 2:
                $statusText = 'Gantian';
                break;
            default:
                $statusText = 'Lainnya';
                break;
        }

        return [
            $inventaris->tgl ? Carbon::parse($inventaris->tgl)->format('d-m-Y') : '-',
            $inventaris->nama,
            $inventaris->kode_toko,
            $inventaris->nama_toko,
            $inventaris->lok_spk,
            $inventaris->jenis,
            $inventaris->tipe,
            $inventaris->kelengkapan,
            optional($inventaris->gudang)->nama_gudang ?? '-',
            $inventaris->keterangan,
            $statusText,
            $inventaris->tgl_gantian ? Carbon::parse($inventaris->tgl_gantian)->format('d-m-Y') : '-',
            $inventaris->status == 2 ? $inventaris->alasan_gantian : '-',
        ];
    }
}
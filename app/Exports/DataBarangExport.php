<?php

namespace App\Exports;

use App\Models\Barang;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

use Carbon\Carbon;

class DataBarangExport implements FromQuery, WithHeadings, WithMapping
{
    private ?string $startDate;
    private ?string $endDate;
    private ?string $status;     // 'terjual' | 'belum' | null
    private ?string $gudangNama;

    public function __construct(?string $startDate, ?string $endDate, ?string $status, ?string $gudangNama)
    {
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->status     = $status;
        $this->gudangNama = $gudangNama;
    }

    public function query()
    {
        $q = Barang::query()->with('gudang');

        // Range tanggal upload
        if ($this->startDate || $this->endDate) {
            $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : Carbon::minValue();
            $end   = $this->endDate   ? Carbon::parse($this->endDate)->endOfDay()   : Carbon::maxValue();
            $q->whereBetween('created_at', [$start, $end]);
        }

        // Status
        if ($this->status === 'terjual') {
            $q->where('status_barang', 2);
        } elseif ($this->status === 'belum') {
            $q->where('status_barang', '!=', 2);
        }

        // Gudang
        if (!empty($this->gudangNama)) {
            $q->whereHas('gudang', function ($sub) {
                $sub->where('nama_gudang', $this->gudangNama);
            });
        }

        return $q->select([
            'lok_spk', 'created_at', 'jenis', 'tipe', 'imei', 'grade', 'kelengkapan', 'gudang_id'
        ])->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        // Sesuai kolom di tabel (kecuali Action)
        return [
            'LOK_SPK',
            'Tgl Upload',
            'Jenis',
            'Tipe',
            'Imei',
            'Grade',
            'Kel',
            'Gudang',
        ];
    }

    public function map($barang): array
    {
        return [
            $barang->lok_spk,
            Carbon::parse($barang->created_at)->translatedFormat('d F Y'),
            $barang->jenis,
            $barang->tipe,
            $barang->imei,
            $barang->grade,
            $barang->kelengkapan,
            optional($barang->gudang)->nama_gudang ?? 'N/A',
        ];
    }
}

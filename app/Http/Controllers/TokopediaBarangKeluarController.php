<?php

// app/Http/Controllers/TokopediaBarangKeluarController.php
namespace App\Http\Controllers;

use App\Exports\TokopediaBarangKeluarTemplateExport;
use App\Models\TokopediaBarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TokopediaBarangKeluarController extends Controller
{
    public function index(Request $request)
    {
        // Opsi filter (dropdown)
        $kodeTokos   = TokopediaBarangKeluar::select('kode_toko')->distinct()->orderBy('kode_toko')->pluck('kode_toko');
        $namaBarangs = TokopediaBarangKeluar::select('nama_barang')->distinct()->orderBy('nama_barang')->pluck('nama_barang');

        $q = TokopediaBarangKeluar::query();

        // Filters
        if ($request->filled('start_date')) $q->where('tgl_keluar','>=',$request->start_date);
        if ($request->filled('end_date'))   $q->where('tgl_keluar','<=',$request->end_date);
        if ($request->filled('kode_toko'))  $q->where('kode_toko', $request->kode_toko);
        if ($request->filled('nama_barang'))$q->where('nama_barang',$request->nama_barang);
        if ($request->filled('search')) {
            $s = '%'.trim($request->search).'%';
            $q->where(function($x) use ($s){
                $x->where('nama_toko','like',$s)
                  ->orWhere('nama_am','like',$s)
                  ->orWhere('alasan','like',$s);
            });
        }

        $items = $q->orderBy('tgl_keluar','desc')->orderBy('id','desc')->get();

        return view('pages.tokopedia-barang-keluar.index', [
            'items'       => $items,
            'kodeTokos'   => $kodeTokos,
            'namaBarangs' => $namaBarangs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRow($request->all());

        // Cek duplikat
        $exists = TokopediaBarangKeluar::where('tgl_keluar',$data['tgl_keluar'])
            ->where('kode_toko',$data['kode_toko'])
            ->where('nama_barang',$data['nama_barang'])
            ->where('quantity',$data['quantity'])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['form' => 'Data duplikat (tgl_keluar, kode_toko, nama_barang, quantity) sudah ada.']);
        }

        TokopediaBarangKeluar::create($data);
        return redirect()->route('tokopedia-barang-keluar.index')->with('success','Data berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = TokopediaBarangKeluar::findOrFail($id);
        $data = $this->validateRow($request->all());

        $exists = TokopediaBarangKeluar::where('tgl_keluar',$data['tgl_keluar'])
            ->where('kode_toko',$data['kode_toko'])
            ->where('nama_barang',$data['nama_barang'])
            ->where('quantity',$data['quantity'])
            ->where('id','<>',$item->id)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['form' => 'Update gagal: kombinasi unik sudah ada.']);
        }

        $item->update($data);
        return redirect()->route('tokopedia-barang-keluar.index')->with('success','Data berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = TokopediaBarangKeluar::findOrFail($id);
        $item->delete();
        return response()->json(['status'=>'success','message'=>'Data terhapus']);
    }

    public function exportTemplate()
    {
        $fileName = 'template-tokopedia-barang-keluar-'.now()->format('Ymd').'.xlsx';
        return Excel::download(new TokopediaBarangKeluarTemplateExport, $fileName);
    }

    /**
     * Batch Paste (copy dari Excel/Spreadsheet)
     * Header wajib: tgl_keluar,kode_toko,nama_am,nama_toko,nama_barang,quantity,alasan
     * Jika ada 1 error -> batal semua (rollback).
     */
    public function batchPaste(Request $request)
    {
        $request->validate(['pasted_table' => 'required|string']);
        $raw = trim($request->pasted_table);
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        if (count($lines) < 2) {
            return back()->with('error','Format paste tidak valid (butuh header + data).');
        }

        $expected = ['tgl_keluar','kode_toko','nama_am','nama_toko','nama_barang','quantity','alasan'];
        $header = $this->normalizeHeader($this->splitRow($lines[0]));
        if ($header !== $expected) {
            return back()->with('error','Header harus persis: '.implode(',', $expected));
        }

        $rows = [];
        $errors = [];
        $seen = []; // key: tgl|kode|nama_barang|qty

        for ($i=1; $i<count($lines); $i++) {
            if (trim($lines[$i])==='') continue;
            $cols = $this->splitRow($lines[$i]);
            for ($c=count($cols); $c<count($expected); $c++) $cols[] = null;
            $row = array_combine($expected, array_map(fn($v)=>is_string($v)?trim($v):$v, $cols));

            // Normalisasi
            $row['nama_barang'] = strtoupper(trim((string)($row['nama_barang'] ?? '')));
            $row['kode_toko']   = (string)($row['kode_toko'] ?? '');
            $row['quantity']    = (int)($row['quantity'] ?? 0);

            // Validasi
            $validator = Validator::make($row, [
                'tgl_keluar'   => ['required','date','before_or_equal:today'],
                'kode_toko'    => ['required','regex:/^[0-9]+$/'],
                'nama_am'      => ['nullable','max:255'],
                'nama_toko'    => ['nullable','max:255'],
                'nama_barang'  => ['required','max:255','regex:/^(?!\s)(.*\S)?$/'],
                'quantity'     => ['required','integer','min:1'],
                'alasan'       => ['nullable'],
            ],[
                'kode_toko.regex'   => 'kode_toko hanya boleh angka.',
                'nama_barang.regex' => 'nama_barang tidak boleh diawali/diakhiri spasi.',
                'tgl_keluar.before_or_equal' => 'tgl_keluar tidak boleh lebih dari hari ini.',
            ]);

            if ($validator->fails()) {
                $errors[] = "Baris #".($i+1).": ".implode(' | ', $validator->errors()->all());
                continue;
            }

            // Duplikat dalam batch
            $key = ($row['tgl_keluar']??'').'|'.$row['kode_toko'].'|'.$row['nama_barang'].'|'.$row['quantity'];
            if (isset($seen[$key])) {
                $errors[] = "Baris #".($i+1).": Duplikat di batch (tgl_keluar, kode_toko, nama_barang, quantity).";
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'tgl_keluar'  => Carbon::parse($row['tgl_keluar'])->format('Y-m-d'),
                'kode_toko'   => $row['kode_toko'],
                'nama_am'     => $row['nama_am'] ?: null,
                'nama_toko'   => $row['nama_toko'] ?: null,
                'nama_barang' => $row['nama_barang'],
                'quantity'    => $row['quantity'],
                'alasan'      => $row['alasan'] ?: null,
            ];
        }

        if (!empty($errors)) {
            return back()->with('error','Upload Gagal:<br>'.implode('<br>', $errors));
        }
        if (empty($rows)) {
            return back()->with('error','Tidak ada baris data valid yang diproses.');
        }

        // Cek duplikat vs DB (massal)
        $dupDbErrors = [];
        foreach (array_chunk($rows, 300) as $chunk) {
            $query = TokopediaBarangKeluar::query();
            foreach ($chunk as $r) {
                $query->orWhere(function($x) use ($r){
                    $x->where('tgl_keluar',$r['tgl_keluar'])
                      ->where('kode_toko',$r['kode_toko'])
                      ->where('nama_barang',$r['nama_barang'])
                      ->where('quantity',$r['quantity']);
                });
            }
            $dups = $query->get(['tgl_keluar','kode_toko','nama_barang','quantity']);
            foreach ($dups as $d) {
                $dupDbErrors[] = "Sudah ada di DB: {$d->tgl_keluar} | {$d->kode_toko} | {$d->nama_barang} | {$d->quantity}";
            }
        }
        if (!empty($dupDbErrors)) {
            return back()->with('error','Upload Gagal karena duplikat vs DB:<br>'.implode('<br>', $dupDbErrors));
        }

        // Insert all-or-nothing
        DB::transaction(function() use ($rows){
            $now = now();
            foreach ($rows as &$r) { $r['created_at'] = $now; $r['updated_at'] = $now; }
            TokopediaBarangKeluar::insert($rows);
        });

        return redirect()->route('tokopedia-barang-keluar.index')->with('success','Batch data berhasil diunggah.');
    }

    /** Helpers */
    private function splitRow(string $line): array
    {
        return str_contains($line, "\t") ? explode("\t",$line) : array_map('trim', explode(',',$line));
    }
    private function normalizeHeader(array $hdr): array
    {
        return array_map(fn($h)=>strtolower(trim($h)), $hdr);
    }
    private function validateRow(array $in): array
    {
        $in['nama_barang'] = isset($in['nama_barang']) ? strtoupper(trim((string)$in['nama_barang'])) : null;
        $in['kode_toko']   = isset($in['kode_toko'])   ? trim((string)$in['kode_toko']) : null;

        $data = validator($in, [
            'tgl_keluar'   => ['required','date','before_or_equal:today'],
            'kode_toko'    => ['required','regex:/^[0-9]+$/'],
            'nama_am'      => ['nullable','max:255'],
            'nama_toko'    => ['nullable','max:255'],
            'nama_barang'  => ['required','max:255','regex:/^(?!\s)(.*\S)?$/'],
            'quantity'     => ['required','integer','min:1'],
            'alasan'       => ['nullable'],
        ],[
            'kode_toko.regex'   => 'kode_toko hanya boleh angka.',
            'nama_barang.regex' => 'nama_barang tidak boleh diawali/diakhiri spasi.',
            'tgl_keluar.before_or_equal' => 'tgl_keluar tidak boleh lebih dari hari ini.',
        ])->validate();

        $data['tgl_keluar'] = Carbon::parse($data['tgl_keluar'])->format('Y-m-d');
        return $data;
    }
}

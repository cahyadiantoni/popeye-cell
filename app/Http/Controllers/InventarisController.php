<?php

namespace App\Http\Controllers;

use App\Models\Inventaris;
use Illuminate\Http\Request;

class InventarisController extends Controller
{
    /**
     * Menampilkan daftar resource.
     */
    public function index()
    {
        $inventaris = Inventaris::latest()->get(); // Ambil data terbaru dulu
        return view('pages.data-inventaris.index', compact('inventaris'));
    }

    /**
     * Menyimpan resource yang baru dibuat.
     */
    public function store(Request $request)
    {
        // Validasi, semua boleh null
        $request->validate([
            'tgl' => 'nullable|date',
            'nama' => 'nullable|string|max:255',
            'kode_toko' => 'nullable|string|max:255',
            'nama_toko' => 'nullable|string|max:255',
            'lok_spk' => 'nullable|string|max:255',
            'jenis' => 'nullable|string|in:TAB,HP,LP,LAIN LAIN', // Tetap gunakan 'in'
            'tipe' => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string|in:BOX,BTG', // Tetap gunakan 'in'
            'keterangan' => 'nullable|string',
        ]);

        Inventaris::create($request->all());

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil ditambahkan!');
    }

    /**
     * Memperbarui resource yang ada di storage.
     */
    public function update(Request $request, $id)
    {
        // Validasi, semua boleh null
        $request->validate([
            'tgl' => 'nullable|date',
            'nama' => 'nullable|string|max:255',
            'kode_toko' => 'nullable|string|max:255',
            'nama_toko' => 'nullable|string|max:255',
            'lok_spk' => 'nullable|string|max:255',
            'jenis' => 'nullable|string|in:TAB,HP,LP,LAIN LAIN', // Tetap gunakan 'in'
            'tipe' => 'nullable|string|max:255',
            'kelengkapan' => 'nullable|string|in:BOX,BTG', // Tetap gunakan 'in'
            'keterangan' => 'nullable|string',
        ]);

        $inventaris = Inventaris::findOrFail($id);
        $inventaris->update($request->all());

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil diperbarui!');
    }

    /**
     * Menghapus resource dari storage.
     */
    public function destroy($id)
    {
        $inventaris = Inventaris::findOrFail($id);
        $inventaris->delete();

        return redirect()->route('data-inventaris.index')->with('success', 'Data inventaris berhasil dihapus!');
    }
}
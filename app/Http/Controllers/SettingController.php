<?php

namespace App\Http\Controllers;

use App\Models\Setting; // Pastikan import model
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Import Rule untuk validasi unique

class SettingController extends Controller
{
    /**
     * Menampilkan daftar resource.
     */
    public function index()
    {
        // Ambil semua data, urutkan dari yang terbaru
        $settings = Setting::latest()->get();

        return view('pages.data-setting.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Tampilkan form untuk membuat resource baru.
     * (Tidak dipakai, kita pakai modal)
     */
    public function create()
    {
        //
    }

    /**
     * Simpan data baru.
     */
    public function store(Request $request)
    {
        // Validasi: name dan value wajib diisi
        $validatedData = $request->validate([
            'name'  => 'required|string|max:255|unique:t_settings,name',
            'value' => 'required|string',
            'keterangan' => 'nullable|string',
            // 'is_active' akan ditangani di bawah
        ]);

        // Tambahkan status 'is_active' berdasarkan centang
        // Jika checkbox dicentang, $request->has('is_active') akan true
        $validatedData['is_active'] = $request->has('is_active');

        Setting::create($validatedData);

        return redirect()->route('data-setting.index')->with('success', 'Settingan baru berhasil ditambahkan!');
    }

    /**
     * Tampilkan resource spesifik.
     * (Tidak dipakai)
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Tampilkan form untuk mengedit resource.
     * (Tidak dipakai, kita pakai modal)
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update data.
     */
    public function update(Request $request, string $id)
    {
        $setting = Setting::findOrFail($id);

        $validatedData = $request->validate([
            'name'  => ['required', 'string', 'max:255', Rule::unique('t_settings', 'name')->ignore($setting->id)],
            'value' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);

        $validatedData['is_active'] = $request->has('is_active');

        $setting->update($validatedData);

        return redirect()->route('data-setting.index')->with('success', 'Settingan berhasil diperbarui!');
    }

    /**
     * Hapus resource.
     * (Tidak dipakai, kita ganti dengan toggleActive)
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Metode kustom untuk toggle status aktif/nonaktif.
     * Mirip dengan 'gantian' di InventarisController Anda.
     */
    public function toggleActive(Request $request, Setting $setting)
    {
        try {
            // Ubah status: jika true jadi false, jika false jadi true
            $setting->update([
                'is_active' => !$setting->is_active,
            ]);
            
            $newState = $setting->is_active ? "diaktifkan" : "dinonaktifkan";

            return response()->json([
                'status'  => 'success', 
                'message' => "Settingan '{$setting->name}' berhasil $newState."
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan.'], 500);
        }
    }
}
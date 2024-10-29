<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\User;
use Illuminate\Http\Request;

class DataGudangController extends Controller
{
    public function index()
    {
        // Mengambil semua data gudang beserta nama penanggung jawab dari user
        $gudangs = Gudang::with('penanggungJawab')->get();
        return view('pages.data-gudang.index', compact('gudangs'));
    }

    public function create()
    {
        $users = User::all(); // Ambil semua data user
        return view('pages.data-gudang.create', compact('users')); // Kirim data user ke view
    }

    public function store(Request $request)
    {
        // Validasi data
        $request->validate([
            'nama_gudang' => 'required|string|max:255',
        ]);

        // Menyimpan data gudang
        Gudang::create([
            'nama_gudang' => $request->nama_gudang,
            'pj_gudang' => $request->pj_gudang,
        ]);

        return redirect()->route('data-gudang.index')->with('success', 'Gudang added successfully!');
    }

    public function destroy($id)
    {
        // Cari gudang berdasarkan ID dan hapus
        $gudang = Gudang::findOrFail($id);
        $gudang->delete();

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('data-gudang.index')->with('success', 'Gudang deleted successfully!');
    }

    public function edit($id)
    {
        // Mencari pengguna berdasarkan ID
        $gudang = Gudang::findOrFail($id);
        $users = User::all(); // Ambil semua data user
        return view('pages.data-gudang.edit', compact('gudang', 'users'));
    }

    // Menyimpan pembaruan pengguna
    public function update(Request $request, $id)
    {
        // Validasi data
        $request->validate([
            'nama_gudang' => 'required|string|max:255',
            'pj_gudang' => 'required',
        ]);

        // Mencari pengguna berdasarkan ID
        $gudang = Gudang::findOrFail($id);
        $gudang->update($request->only('nama_gudang', 'pj_gudang'));

        return redirect()->route('data-gudang.index')->with('success', 'Gudang updated successfully!');
    }
}

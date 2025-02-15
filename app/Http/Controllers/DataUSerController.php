<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Gudang;
use Illuminate\Http\Request;

class DataUSerController extends Controller
{
    // Menampilkan daftar pengguna
    public function index()
    {
        // Mengambil semua pengguna dari database
        $users = User::with('gudang')->get(); 
        return view('pages.data-user.index', compact('users'));
    }

    // Menampilkan form edit pengguna
    public function edit($id)
    {
        // Mencari pengguna berdasarkan ID
        $user = User::findOrFail($id);

        $gudangs = Gudang::all();

        return view('pages.data-user.edit', compact('user', 'gudangs'));
    }

    // Menyimpan pembaruan pengguna
    public function update(Request $request, $id)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'nullable',
            'gudang_id' => 'required',
        ]);

        // Mencari pengguna berdasarkan ID
        $user = User::findOrFail($id);
        $user->update($request->only('name', 'email', 'role', 'gudang_id'));

        return redirect()->route('data-user.index')->with('success', 'User updated successfully!');
    }


    // Menampilkan form untuk menambah pengguna
    public function create()
    {
        $gudangs = Gudang::all();

        return view('pages.data-user.create', compact('gudangs'));
    }

    // Menyimpan pengguna baru
    public function store(Request $request)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'nullable',
            'gudang_id' => 'required',
            'password' => 'required|string|min:8', // Validasi password
        ]);

        // Menyimpan data pengguna baru dengan password di-hash
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'gudang_id' => $request->gudang_id,
            'password' => bcrypt($request->password), // Hash password
        ]);

        return redirect()->route('data-user.index')->with('success', 'User added successfully!');
    }


    public function destroy($id)
    {
        // Cari user berdasarkan ID dan hapus
        $user = User::findOrFail($id);
        $user->delete();

        // Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('data-user.index')->with('success', 'User deleted successfully!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User; // Pastikan model User sudah ada
use Illuminate\Http\Request;

class DataUSerController extends Controller
{
    // Menampilkan daftar pengguna
    public function index()
    {
        // Mengambil semua pengguna dari database
        $users = User::all(); // Ganti dengan metode sesuai kebutuhan
        return view('pages.data-user.index', compact('users'));
    }

    // Menampilkan form edit pengguna
    public function edit($id)
    {
        // Mencari pengguna berdasarkan ID
        $user = User::findOrFail($id);
        return view('pages.data-user.edit', compact('user'));
    }

    // Menyimpan pembaruan pengguna
    public function update(Request $request, $id)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        // Mencari pengguna berdasarkan ID
        $user = User::findOrFail($id);
        $user->update($request->only('name', 'email'));

        return redirect()->route('data-user.index')->with('success', 'User updated successfully!');
    }


    // Menampilkan form untuk menambah pengguna
    public function create()
    {
        return view('data-user.create');
    }

    // Menyimpan pengguna baru
    public function store(Request $request)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
        ]);

        // Menyimpan data pengguna baru
        User::create($request->only('name', 'email'));

        return redirect()->route('data-user.index')->with('success', 'User added successfully!');
    }
}

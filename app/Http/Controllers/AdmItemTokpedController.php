<?php

namespace App\Http\Controllers;
use App\Models\AdmItemTokped;
use Illuminate\Http\Request;

class AdmItemTokpedController extends Controller
{
    public function index()
    {
        $settings = AdmItemTokped::all();
        return view('pages.req-tokped.item', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);
        
        AdmItemTokped::create($request->all());
        return redirect()->back()->with('success', 'Item Tokped berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'keterangan' => 'nullable|string',
        ]);
        
        $setting = AdmItemTokped::findOrFail($id);
        $setting->update($request->all());
        return redirect()->back()->with('success', 'Item Tokped berhasil diperbarui.');
    }

    public function destroy($id)
    {
        AdmItemTokped::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Item Tokped berhasil dihapus.');
    }
}
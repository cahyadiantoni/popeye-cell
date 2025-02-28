<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AdmSetting;

class AdmSettingController extends Controller
{
    public function index()
    {
        $settings = AdmSetting::all();
        return view('pages.adm-setting.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'is_active' => 'required|boolean',
            'keterangan' => 'nullable|string',
        ]);
        
        AdmSetting::create($request->all());
        return redirect()->back()->with('success', 'Setting berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'is_active' => 'required|boolean',
            'keterangan' => 'nullable|string',
        ]);
        
        $setting = AdmSetting::findOrFail($id);
        $setting->update($request->all());
        return redirect()->back()->with('success', 'Setting berhasil diperbarui.');
    }

    public function destroy($id)
    {
        AdmSetting::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Setting berhasil dihapus.');
    }
}
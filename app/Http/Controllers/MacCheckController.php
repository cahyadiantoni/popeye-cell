<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use App\Models\MacAddress;

class MacCheckController extends Controller
{
    public function index()
    {
        $macs = MacAddress::orderBy('updated_at', 'desc')->get();
        return view('pages.mac-address.index', compact('macs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'mac' => 'required|string|max:255|unique:mac_addresses,mac',
            'status' => 'required|integer'
        ]);

        MacAddress::create($request->all());
        return redirect()->back()->with('success', 'MAC address berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'mac' => 'required|string|max:255|unique:mac_addresses,mac,' . $id,
            'status' => 'required|integer'
        ]);

        $mac = MacAddress::findOrFail($id);
        $mac->update($request->all());

        return redirect()->back()->with('success', 'MAC address berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $mac = MacAddress::findOrFail($id);
        $mac->delete();

        return redirect()->back()->with('success', 'MAC address berhasil dihapus.');
    }

    public function check(Request $request)
    {
        $mac = $request->input('mac');

        if (!$mac) {
            return response()->json(['message' => 'MAC address tidak ditemukan!'], 400);
        }

        $macEntry = MacAddress::where('mac', $mac)->first();

        if (!$macEntry) {
            MacAddress::create([
                'mac' => $mac,
                'status' => 0
            ]);

            return response()
                ->json(['status' => 0, 'message' => 'Pending approval'])
                ->cookie('mac_address', $mac, 60 * 24 * 30); // disimpan selama 30 hari
        }

        if ($macEntry->status == 1) {
            return response()
                ->json(['status' => 1, 'message' => 'Access diberikan'])
                ->cookie('mac_address', $mac, 60 * 24 * 30);
        }

        if ($macEntry->status == 2) {
            return response()
                ->json(['status' => 2, 'message' => 'Access ditolak'])
                ->cookie('mac_address', $mac, 60 * 24 * 30);
        }

        return response()
            ->json(['status' => 0, 'message' => 'Pending approval'])
            ->cookie('mac_address', $mac, 60 * 24 * 30);
    }
}


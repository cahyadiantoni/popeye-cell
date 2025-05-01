<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use App\Models\MacAddress;

class MacCheckController extends Controller
{

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


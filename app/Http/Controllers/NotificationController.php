<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Negoan;
use App\Models\NegoanChat;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('user')
        ->where('penerima_id', Auth::id())
        ->orderBy('status', 'asc')
        ->orderBy('created_at', 'asc')
        ->get();
    
        return view('pages.notification.index', compact('notifications'));
    }    


    public function show($id)
    {
        // Find the notification by ID
        $notification = Notification::findOrFail($id);

        // Update the status to 1
        $notification->status = 1;
        $notification->save();

        // Redirect to the link associated with the notification
        return redirect($notification->link);
    }
}

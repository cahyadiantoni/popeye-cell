<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Kirim;
use App\Models\Notification;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $authId = Auth::id();
            $notifCount = Notification::where('penerima_id', $authId)
                ->where('status', 0)
                ->count();

            // Fetch notifications with user relation
            $notifications = Notification::with('user')
                ->where('penerima_id', Auth::id())
                ->orderBy('status', 'asc')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($notification) {
                    // Calculate the difference in minutes from the current time
                    $notification->minutes_ago = round(abs(Carbon::now()->diffInMinutes($notification->created_at)));
                    return $notification;
                });
                                
            $view->with(compact('notifCount', 'notifications'));
        });
    }
}

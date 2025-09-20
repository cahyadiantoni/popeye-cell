<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\Kirim;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Models\Setting;
use App\Observers\SettingObserver;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, function ($app) {
            return new SettingsService();
        });
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
        Carbon::setLocale('id');
        App::setLocale('id');

        // 1. Daftarkan Observer kita
        Setting::observe(SettingObserver::class);

        // 2. Bagikan data setting ke SEMUA view
        
        // Kita cek dulu apakah tabel 't_settings' sudah ada
        // Ini untuk mencegah error saat pertama kali 'php artisan migrate'
        if (Schema::hasTable('t_settings')) {
            try {
                // Ambil service (efisien karena singleton)
                $settingsService = $this->app->make(SettingsService::class);
                
                // Ambil data (ini akan ter-cache)
                $settings = $settingsService->getAllSettings();
                
                // 'View::share' akan membuat variabel $settings
                // tersedia di semua file .blade.php
                View::share('settings', $settings);

            } catch (\Exception $e) {
                // Jika ada error (misal DB belum siap), bagikan koleksi kosong
                View::share('settings', collect());
            }
        } else {
            // Jika tabel belum ada
            View::share('settings', collect());
        }
    }
}

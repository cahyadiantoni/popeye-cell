<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\SettingsService; // Import service kita

class SettingObserver
{
    /**
     * Helper untuk memanggil service dan membersihkan cache.
     */
    protected function clearCache()
    {
        // 'app()' adalah helper untuk memanggil service dari container Laravel
        // Ini akan memanggil method forgetCache() dari SettingsService
        app(SettingsService::class)->forgetCache();
    }

    /**
     * Handle event "saved" (terjadi setelah create dan update).
     */
    public function saved(Setting $setting): void
    {
        $this->clearCache();
    }

    /**
     * Handle event "deleted".
     */
    public function deleted(Setting $setting): void
    {
        $this->clearCache();
    }
    
    // Kita tidak perlu 'updated' atau 'created' secara spesifik
    // karena 'saved' sudah mencakup keduanya.
}
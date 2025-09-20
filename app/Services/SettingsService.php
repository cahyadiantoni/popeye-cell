<?php

namespace App\Services;

use App\Models\Setting; // Pastikan import model Setting Anda
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class SettingsService
{
    /**
     * Kunci unik untuk cache
     */
    protected string $cacheKey = 'app_settings';

    /**
     * Method untuk mengambil semua setting.
     * Data akan diambil dari cache. Jika cache kosong, akan query ke DB lalu simpan ke cache.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllSettings(): Collection
    {
        // 'rememberForever' akan menyimpan data di cache selamanya
        // sampai kita hapus secara manual (via forgetCache)
        return Cache::rememberForever($this->cacheKey, function () {
            // Kita hanya ambil yang aktif
            // pluck('value', 'name') akan mengubahnya jadi format:
            // [ 'NAMA_SITUS' => 'Website Keren', 'EMAIL_ADMIN' => 'admin@email.com' ]
            return Setting::where('is_active', true)->pluck('value', 'name');
        });
    }

    /**
     * Method untuk mengambil satu nilai setting berdasarkan key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->getAllSettings()->get($key, $default);
    }

    /**
     * Method untuk menghapus cache setting.
     * Kita akan panggil ini secara otomatis saat ada update.
     */
    public function forgetCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
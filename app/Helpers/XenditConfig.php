<?php

namespace App\Helpers;

use Xendit\Configuration;

class XenditConfig
{
    public static function init()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }
}
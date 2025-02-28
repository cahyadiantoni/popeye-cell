<?php

namespace App\Mail;

use App\Models\AdmReqTokped;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReqTokpedStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $reqTokped;

    public function __construct(AdmReqTokped $reqTokped)
    {
        $this->reqTokped = $reqTokped;
    }

    public function build()
    {
        return $this->subject('Status Request Barang Tokped Diperbarui')
                    ->view('pages.req-tokped.mail');
    }

}

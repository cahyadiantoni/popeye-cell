<?php

namespace App\Mail;

use App\Models\AdmTodoTf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TodoTransferStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $todoTransfer;

    public function __construct(AdmTodoTf $todoTransfer)
    {
        $this->todoTransfer = $todoTransfer;
    }

    public function build()
    {
        return $this->subject('Status To do Transfer Diperbarui')
                    ->view('pages.todo-transfer.mail');
    }

}

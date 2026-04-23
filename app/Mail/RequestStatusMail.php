<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $req;
    public $user;

    public function __construct($req, $user)
    {
        //
        $this->req = $req;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Status Pengajuan Admin Gunung')
                    ->view('emails.request_status');
    }

}

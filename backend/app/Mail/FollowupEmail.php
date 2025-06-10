<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FollowupEmail extends Mailable
{
    use Queueable, SerializesModels;

    public Company $company;
    public Document $document;
    public string $emailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(Company $company, Document $document, string $subject)
    {
        $this->company = $company;
        $this->document = $document;
        $this->emailSubject = $subject;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->emailSubject)
            ->view('emails.followup')
            ->with([
                'companyName' => $this->company->name,
                'documentTitle' => $this->document->title,
                'bookingLink' => $this->company->booking_link,
                'companyId' => $this->company->id,
            ]);
    }
}

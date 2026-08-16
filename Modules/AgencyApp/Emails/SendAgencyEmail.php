<?php

namespace Modules\AgencyApp\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAgencyEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $agencyWithAdditionalInfo;
    public function __construct($agencyWithAdditionalInfo)
    {
        $this->agencyWithAdditionalInfo = $agencyWithAdditionalInfo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('gmail',[
            'agency' => $this->agencyWithAdditionalInfo,
        ])->subject('انشاء وكاله جديدة');
    }
}

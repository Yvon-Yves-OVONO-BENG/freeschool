<?php

namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private $twilioClient;

    public function __construct(Client $twilioClient)
    {
        $this->twilioClient = $twilioClient;
    }

    public function sendSms($to, $message)
    {
        $this->twilioClient->messages->create(
            $to,
            [
                'from' => '+19712326852',
                'body' => $message
            ]
        );
    }
}

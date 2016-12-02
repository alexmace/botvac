<?php

namespace AlexMace\NeatoBotvac\Service;

use DateTime;
use GuzzleHttp\Client;

class RobotApi
{
    private $client;
    private $serial;
    private $secret;

    public function __construct(Client $client, $serial, $secret)
    {
        $this->client = $client;
        $this->serial = $serial;
        $this->secret = $secret;
    }

    public function calculateAuthorizationHeader(DateTime $dateTime, $payload)
    {
        $date = $dateTime->format('D, d M Y H:i:s e');
        $data = implode("\n", [strtolower($this->serial), $date, $payload]);
        $hmac = hash_hmac("sha256", $data, $this->secret);
        return 'NEATOAPP ' . $hmac;
    }

    public function getRobotState()
    {
        $response = $this->client->request(
            'POST',
            'https://nucleo.neatocloud.com/vendors/neato/robots/' . $this->serial . '/messages',
            [
                'headers' => [
                    'Accept'        => 'application/vnd.neato.nucleo.v1',
                    'Date'          => 'Sun, 27 Nov 2016 14:30:27 GMT',
                    'Authorization' => 'NEATOAPP 01c966b5f37af4c156da3522fba85c12026cf8fbb6031e56d386d1734f8bd510',
                    'X-Agent'       => 'ios-7|iPhone 4|0.11.3-142',
                ],
                'json' => [
                    'reqId' => 1,
                    'cmd' => 'getRobotState',
                ]
            ]
        );
    }
}

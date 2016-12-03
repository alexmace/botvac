<?php

namespace AlexMace\NeatoBotvac\Service;

use DateTime;
use DateTimeZone;
use GuzzleHttp\Client;

class RobotApi
{
    const DATE_FORMAT = 'D, d M Y H:i:s e';
    private $client;
    private $serial;
    private $secret;

    public function __construct(Client $client, $serial, $secret)
    {
        $this->client = $client;
        $this->serial = $serial;
        $this->secret = $secret;
    }

    public function calculateAuthorizationHeader(DateTime $dateTime, array $payload)
    {
        $date = $dateTime->format(self::DATE_FORMAT);
        $data = implode("\n", [strtolower($this->serial), $date, json_encode($payload)]);
        $hmac = hash_hmac("sha256", $data, $this->secret);
        return 'NEATOAPP ' . $hmac;
    }

    private function makeRequest($cmd)
    {
        $parameters = [
            'reqId' => 1,
            'cmd' => $cmd,
        ];
        $dateTime = new DateTime();
        $dateTime->setTimezone(new DateTimeZone('GMT'));
        $response = $this->client->request(
            'POST',
            'https://nucleo.neatocloud.com/vendors/neato/robots/' . $this->serial . '/messages',
            [
                'headers' => [
                    'Accept'        => 'application/vnd.neato.nucleo.v1',
                    'Date'          => $dateTime->format(self::DATE_FORMAT),
                    'Authorization' => $this->calculateAuthorizationHeader($dateTime, $parameters),
                    'X-Agent'       => 'AlexMace|RobotApi|0.0.1',
                ],
                'verify' => false, // :(
                'json' => $parameters
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    public function getRobotState()
    {
        return $this->makeRequest('getRobotState');
    }

    public function dismissCurrentAlert()
    {
        return $this->makeRequest('dismissCurrentAlert');
    }
}

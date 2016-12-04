<?php

namespace AlexMace\NeatoBotvac\Service;

use DateTime;
use DateTimeZone;
use GuzzleHttp\Client;

class RobotApi
{
    // Date format required for the date header and part of the hmac.
    const DATE_FORMAT = 'D, d M Y H:i:s e';

    // Cleaning categories
    const CLEAN_HOUSE = 2;
    const CLEAN_SPOT = 3;

    // Cleaning modes
    const MODE_TURBO = 2;
    const MODE_ECO = 1;

    // Cleaning modifiers
    const SINGLE_PASS = 1;
    const DOUBLE_PASS = 2;

    // Navigation modes
    const NAVIGATION_NORMAL = 1;
    const NAVIGATION_EXTRA_CARE = 2;

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

    private function makeRequest($cmd, $params = null)
    {
        $parameters = [
            'reqId' => 1,
            'cmd' => $cmd,
        ];

        if (!is_null($params)) {
            $parameters['params'] = $params;
        }
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

    public function getRobotInfo()
    {
        return $this->makeRequest('getRobotInfo');
    }

    public function findMe()
    {
        // Only available on basic-1 models, check available services to know
        return $this->makeRequest('findMe');
    }

    public function getGeneralInfo()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // Responses are the same, aside from advanced-1 includes a language
        // value
        return $this->makeRequest('getGeneralInfo');
    }

    public function startCleaning(
        $category,
        $mode = null,
        $modifier = null,
        $navigationMode = null,
        $spotWidth = null,
        $spotHeight = null
    ) {
        $params = [
            'category' => $category,
        ];

        foreach (['mode', 'modifier', 'navigationMode', 'spotWidth', 'spotHeight'] as $param) {
            if (!is_null($$param)) {
                $params[$param] = $$param;
            }
        }
        return $this->makeRequest('startCleaning', $params);
    }

    public function stopCleaning()
    {
        return $this->makeRequest('stopCleaning');
    }

    public function pauseCleaning()
    {
        return $this->makeRequest('pauseCleaning');
    }

    public function resumeCleaning()
    {
        return $this->makeRequest('resumeCleaning');
    }
}

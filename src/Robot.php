<?php

namespace AlexMace\NeatoBotvac;

use stdClass;

class Robot
{

    const SERVICE_FIND_ME           = 'findMe';
    const SERVICE_GENERAL_INFO      = 'generalInfo';
    const SERVICE_HOUSE_CLEANING    = 'houseCleaning';
    const SERVICE_LOCAL_STATS       = 'localStats';
    const SERVICE_MANUAL_CLEANING   = 'manualCleaning';
    const SERVICE_PREFERENCES       = 'preferences';
    const SERVICE_SCHEDULE          = 'schedule';
    const SERVICE_SPOT_CLEANING     = 'spotCleaning';

    // Don't know what this one does, because it is not documented in Neato's
    // documentation.
    const SERVICE_EASY_CONNECT      = 'easyConnect';

    private $api;
    private $apiVersion;
    private $servicesAvailable = [
        self::SERVICE_FIND_ME           => false,
        self::SERVICE_GENERAL_INFO      => false,
        self::SERVICE_HOUSE_CLEANING    => false,
        self::SERVICE_LOCAL_STATS       => false,
        self::SERVICE_MANUAL_CLEANING   => false,
        self::SERVICE_PREFERENCES       => false,
        self::SERVICE_SCHEDULE          => false,
        self::SERVICE_SPOT_CLEANING     => false,
        self::SERVICE_EASY_CONNECT      => false,
    ];

    public function __construct(Service\RobotApi $robotApi)
    {
        $this->api = $robotApi;
        $this->processStateResponse($this->api->getRobotState());
    }

    public function isServiceAvailable($service)
    {
        if (!in_array($service, array_keys($this->servicesAvailable))) {
            throw new Exception('Unable to determine if service ' . $service . ' is available, because it is unknown.');
        }

        return $this->servicesAvailable[$service];
    }

    private function processStateResponse($stateResponse)
    {
        if ( ! ($stateResponse instanceof stdClass)
            || !isset($stateResponse->availableServices)
            || ! ($stateResponse->availableServices instanceof stdClass)
        ) {
            throw new Exception('Unable to process state response received');
        }

        foreach ($stateResponse->availableServices as $service => $version) {
            if (!in_array($service, array_keys($this->servicesAvailable))) {
                throw new Exception('Unexpected service found in availableServices: ' . $service);
            }

            $this->servicesAvailable[$service] = true;

            // Neato's documentation states that each service will state what
            // API version for that service the robot supports. Arguments differ
            // between versions.
            // I am making an assumption here that the same version will be
            // reported for every service the robot supports.
            $this->apiVersion = $version;
        }

    }
}

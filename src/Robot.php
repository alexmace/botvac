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

    const STATE_INVALID = 0;
    const STATE_IDLE    = 1;
    const STATE_BUSY    = 2;
    const STATE_PAUSED  = 3;
    const STATE_ERROR   = 4;

    const ACTION_INVALID                = 0;
    const ACTION_HOUSE_CLEANING         = 1;
    const ACTION_SPOT_CLEANING          = 2;
    const ACTION_MANUAL_CLEANING        = 3;
    const ACTION_DOCKING                = 4;
    const ACTION_USER_MENU_ACTIVE       = 5;
    const ACTION_SUSPENDED_CLEANING     = 6;
    const ACTION_UPDATING               = 7;
    const ACTION_COPYING_LOGS           = 8;
    const ACTION_RECOVERING_LOCATION    = 9;
    const ACTION_IEC_TEST               = 10;

    private $api;
    private $apiVersion;
    private $isCharging;
    private $isDocked;
    private $isScheduleEnabled;
    private $hasSeenDock;
    private $batteryCharge;
    private $state;
    private $action;
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

    public function isCharging()
    {
        return $this->isCharging;
    }

    public function isDocked()
    {
        return $this->isDocked;
    }

    public function isScheduleEnabled()
    {
        return $this->isScheduleEnabled;
    }
    public function hasSeenDock()
    {
        return $this->hasSeenDock;
    }

    public function getBatteryCharge()
    {
        return $this->batteryCharge;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getAction()
    {
        return $this->action;
    }

    private function processStateResponse($stateResponse)
    {
        if ( ! ($stateResponse instanceof stdClass)
            || !isset($stateResponse->availableServices)
            || !isset($stateResponse->state)
            || !isset($stateResponse->action)
            || ! ($stateResponse->availableServices instanceof stdClass)
            || !isset($stateResponse->details)
            || ! ($stateResponse->details instanceof stdClass)
            || !isset($stateResponse->details->isCharging)
            || !isset($stateResponse->details->isDocked)
            || !isset($stateResponse->details->isScheduleEnabled)
            || !isset($stateResponse->details->dockHasBeenSeen)
            || !isset($stateResponse->details->charge)
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

        $this->isCharging = $stateResponse->details->isCharging;
        $this->isDocked = $stateResponse->details->isDocked;
        $this->isScheduleEnabled = $stateResponse->details->isScheduleEnabled;
        $this->hasSeenDock = $stateResponse->details->dockHasBeenSeen;
        $this->batteryCharge = $stateResponse->details->charge;

        $this->state = $stateResponse->state;
        $this->action = $stateResponse->action;

    }
}

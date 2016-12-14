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

    const COMMAND_START         = 'start';
    const COMMAND_STOP          = 'stop';
    const COMMAND_PAUSE         = 'pause';
    const COMMAND_RESUME        = 'resume';
    const COMMAND_GO_TO_BASE    = 'goToBase';

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

    private $commandsAvailable = [
        self::COMMAND_START         => false,
        self::COMMAND_STOP          => false,
        self::COMMAND_PAUSE         => false,
        self::COMMAND_RESUME        => false,
        self::COMMAND_GO_TO_BASE    => false,
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

    public function ecoCleanHouse()
    {
        // Eco mode is only supported on basic-1 and basic-2
        if (!in_array($this->apiVersion, ['basic-1', 'basic-2'])) {
            throw new Exception('Eco cleaning mode is not available on this robot.');
        }

        $this->startCleaning(
            Service\RobotApi::CLEAN_HOUSE,
            Service\RobotApi::MODE_ECO,
            Service\RobotApi::SINGLE_PASS
        );

    }

    public function cleanHouse()
    {
        $this->startCleaning(
            Service\RobotApi::CLEAN_HOUSE,
            Service\RobotApi::MODE_TURBO,
            Service\RobotApi::SINGLE_PASS
        );
    }

    public function cleanSpot()
    {
        $this->startCleaning(
            Service\RobotApi::CLEAN_SPOT,
            Service\RobotApi::MODE_TURBO,
            Service\RobotApi::SINGLE_PASS
        );
    }

    public function deepCleanSpot()
    {
        $this->startCleaning(
            Service\RobotApi::CLEAN_SPOT,
            Service\RobotApi::MODE_TURBO,
            Service\RobotApi::DOUBLE_PASS
        );
    }

    public function ecoCleanSpot()
    {
        // Eco mode is only supported on basic-1 and basic-2
        if (!in_array($this->apiVersion, ['basic-1', 'basic-2'])) {
            throw new Exception('Eco cleaning mode is not available on this robot.');
        }

        $this->startCleaning(
            Service\RobotApi::CLEAN_SPOT,
            Service\RobotApi::MODE_ECO,
            Service\RobotApi::SINGLE_PASS
        );
    }

    public function ecoDeepCleanSpot()
    {
        // Eco mode is only supported on basic-1 and basic-2
        if (!in_array($this->apiVersion, ['basic-1', 'basic-2'])) {
            throw new Exception('Eco cleaning mode is not available on this robot.');
        }

        $this->startCleaning(
            Service\RobotApi::CLEAN_SPOT,
            Service\RobotApi::MODE_ECO,
            Service\RobotApi::DOUBLE_PASS
        );
    }

    public function stopCleaning()
    {
        if (!$this->isServiceAvailable(self::SERVICE_HOUSE_CLEANING)
            && !$this->isServiceAvailable(self::SERVICE_SPOT_CLEANING)
        ) {
            throw new Exception('Cleaning is not available on this robot.');
        }

        // If the start cleaning command is not available, then throw an
        // exception
        if (!$this->isCommandAvailable(self::COMMAND_STOP)
        ) {
            throw new Exception('Robot is not able to stop cleaning at this time.');
        }

        $this->processStateResponse($this->api->stopCleaning());
    }

    public function pauseCleaning()
    {
        if (!$this->isServiceAvailable(self::SERVICE_HOUSE_CLEANING)
            && !$this->isServiceAvailable(self::SERVICE_SPOT_CLEANING)
        ) {
            throw new Exception('Cleaning is not available on this robot.');
        }

        // If the start cleaning command is not available, then throw an
        // exception
        if (!$this->isCommandAvailable(self::COMMAND_PAUSE)
        ) {
            throw new Exception('Robot is not able to pause cleaning at this time.');
        }

        $this->processStateResponse($this->api->pauseCleaning());
    }

    public function returnToBase()
    {
        if (!$this->isServiceAvailable(self::SERVICE_HOUSE_CLEANING)
            && !$this->isServiceAvailable(self::SERVICE_SPOT_CLEANING)
        ) {
            throw new Exception('Cleaning is not available on this robot.');
        }

        // Returning to the base is only available when the robot is paused and
        // it has seen the dock. So first off we should check if the robot has
        // seen the dock.
        if (!$this->hasSeenDock) {
            throw new Exception('Cannot return to base because base has not been seen.');
        }

        // If the command is already available, just call it now.
        if ($this->isCommandAvailable(self::COMMAND_GO_TO_BASE)) {
            $this->processStateResponse($this->api->sendToBase());
            return;
        }

        // If the pause command is available, call that first, then we should
        // be able to return.
        if ($this->isCommandAvailable(self::COMMAND_PAUSE)) {
            $this->processStateResponse($this->api->pauseCleaning());
        }

        // If the command is not available at this point, throw an exception.
        if (!$this->isCommandAvailable(self::COMMAND_GO_TO_BASE)) {
            throw new Exception('Unable to return to base at this time.');
        }

        $this->processStateResponse($this->api->sendToBase());

    }

    public function enableSchedule()
    {
        $this->api->enableSchedule();
    }

    public function disableSchedule()
    {
        $this->api->disableSchedule();
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

        foreach ($stateResponse->availableCommands as $command => $availability) {
            if (!in_array($command, array_keys($this->commandsAvailable))) {
                throw new Exception('Unexpected command found in availableCommands: ' . $command);
            }

            $this->commandsAvailable[$command] = $availability;
        }

        $this->isCharging = $stateResponse->details->isCharging;
        $this->isDocked = $stateResponse->details->isDocked;
        $this->isScheduleEnabled = $stateResponse->details->isScheduleEnabled;
        $this->hasSeenDock = $stateResponse->details->dockHasBeenSeen;
        $this->batteryCharge = $stateResponse->details->charge;

        $this->state = $stateResponse->state;
        $this->action = $stateResponse->action;

    }

    private function startCleaning($category, $mode = null, $passes = null)
    {
        // If the house cleaning service is not available, then throw an
        // exception
        if ($category == Service\RobotApi::CLEAN_HOUSE
            && !$this->isServiceAvailable(self::SERVICE_HOUSE_CLEANING)
        ) {
            throw new Exception('House cleaning is not available on this robot.');
        }

        // If the spot cleaning service is not available, then throw an
        // exception
        if ($category == Service\RobotApi::CLEAN_HOUSE
            && !$this->isServiceAvailable(self::SERVICE_SPOT_CLEANING)
        ) {
            throw new Exception('Spot cleaning is not available on this robot.');
        }

        // If the start cleaning command is not available, then throw an
        // exception
        if (!$this->isCommandAvailable(self::COMMAND_START)
        ) {
            throw new Exception('Robot is not able to start cleaning at this time.');
        }

        if ($this->apiVersion == 'basic-1') {
            $stateResponse = $this->api->startCleaning(
                $category,
                $mode,
                $passes
            );
        } else if ($this->apiVersion == 'micro-2') {
            $stateResponse = $this->api->startCleaning(
                $category,
                null,
                null,
                Service\RobotApi::NAVIGATION_NORMAL
            );
        } else if ($this->apiVersion == 'minimal-2') {
            $stateResponse = $this->api->startCleaning(
                $category,
                null,
                $passes,
                Service\RobotApi::NAVIGATION_NORMAL
            );
        } else if ($this->apiVersion == 'basic-2') {
            $stateResponse = $this->api->startCleaning(
                $category,
                $mode,
                $passes,
                Service\RobotApi::NAVIGATION_NORMAL
            );
        } else {
            throw new Exception('Unknown robot type');
        }

        $this->processStateResponse($stateResponse);
    }

    private function isCommandAvailable($command)
    {
        if (!in_array($command, array_keys($this->commandsAvailable))) {
            throw new Exception('Unable to determine if command ' . $command . ' is available, because it is unknown.');
        }

        return $this->commandsAvailable[$command];
    }
}

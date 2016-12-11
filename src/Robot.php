<?php

namespace AlexMace\NeatoBotvac;

class Robot
{

    private $api;

    public function __construct(Service\RobotApi $robotApi)
    {
        $this->api = $robotApi;
        $this->processStateResponse($this->api->getRobotState());
    }

    private function processStateResponse($stateResponse)
    {
        
    }
}

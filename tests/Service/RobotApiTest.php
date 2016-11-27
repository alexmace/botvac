<?php

namespace AlexMace\NeatoBotvac\Service;

use PHPUnit\Framework\TestCase;

class RobotApiTest extends TestCase
{

    public function testConstructor()
    {
        // Create a mock instance of the GuzzleHttp\Client that the class will
        // use to communicate with the API.

        $this->markTestIncomplete();
    }

    public function testGetRobotState()
    {
        $this->markTestIncomplete();
    }

    public function testDismissCurrentAlert()
    {
        $this->markTestIncomplete();
    }

    public function testGetRobotInfo()
    {
        $this->markTestIncomplete();
    }

    public function testFindMe()
    {
        // Only available on basic-1 models, check available services to know
        $this->markTestIncomplete();
    }

    public function testGetGeneralInfo()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // Responses are the same, aside from advanced-1 includes a language
        // value
        $this->markTestIncomplete();
    }

    public function testStartCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know

        // basic-1 wants these parameters:
        // category	integer	Required. Fixed to 2 for house cleaning. 3 for spot cleaning
        // mode	integer	Required. 1 eco 2 turbo.
        // modifier	integer	Required. The cleaning frequency. 1 normal 2 double.
        // spotWidth	integer	Required for spot cleaning. Width of the spot area to be cleaned in cm (100-400).
        // spotHeight	integer	Required for spot cleaning. Height of the spot area to be cleaned in cm (100-400).

        // micro-2 wants these parameters:
        // category	integer	Required. Fixed to 2 for house cleaning. 3 for spot cleaning
        // navigationMode	integer	The navigation mode. 1 normal 2 extra care.

        // minimal-2 wants these parameters:
        // category	integer	Required. Fixed to 2 for house cleaning. 3 for spot cleaning
        // modifier	integer	Required. The cleaning frequency. 1 normal 2 double.
        // navigationMode	integer	The navigation mode. 1 normal 2 extra care.

        // basic-2 wants these parameters:
        // category	integer	Required. Fixed to 2 for house cleaning. 3 for spot cleaning
        // mode	integer	Required. 1 eco 2 turbo.
        // modifier	integer	Required. The cleaning frequency. 1 normal 2 double.
        // navigationMode	integer	The navigation mode. 1 normal 2 extra care.
        // spotWidth	integer	Required for spot cleaning. Width of the spot area to be cleaned in cm (100-400).
        // spotHeight	integer	Required for spot cleaning. Height of the spot area to be cleaned in cm (100-400).
        $this->markTestIncomplete();

    }

    public function testStopCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();

    }

    public function testPauseCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();

    }

    public function testResumeCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();

    }

    public function testSendToBase()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();
    }

    public function testGetLocalStats()
    {
        // Only available on advanced-1
        // Check available services to know
        $this->markTestIncomplete();
    }

    public function testGetRobotManualCleaningInfo()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        $this->markTestIncomplete();
    }

    public function testGetPreferences()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // There are advanced-1 has a lot more preferences that can be got
        $this->markTestIncomplete();

    }

    public function testSetPreferences()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // There are advanced-1 has a lot more preferences that can be set
        $this->markTestIncomplete();

    }

    public function testSetSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

    public function testGetSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

    public function testEnableSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

    public function testDisableSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

}

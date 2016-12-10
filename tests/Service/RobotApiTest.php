<?php

namespace AlexMace\NeatoBotvac\Service;

use DateTime;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RobotApiTest extends TestCase
{

    private $container;
    private $mockHandler;
    private $robotApi;

    public function setUp()
    {
        // See http://docs.guzzlephp.org/en/latest/testing.html
        // Create a mock instance of the GuzzleHttp\Client that the class will
        // use to communicate with the API.
        $this->container = [];
        $history = Middleware::history($this->container);

        // Create a mock and queue two responses.
        $this->mockHandler = new MockHandler([]);

        $stack = HandlerStack::create($this->mockHandler);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $client = new Client(['handler' => $stack,]);

        $this->robotApi = new RobotApi($client, 'serial', 'secret');

    }

    private function setupResponse($statusCode, $body)
    {
        $responseHeaders = [
            'server' => "Cowboy",
            'date'   => "Fri, 02 Dec 2016 22:57:53 GMT",
            'content-length' => "585",
            'Access-Control-Allow-Origin' => "*",
            'Access-Control-Allow-Methods' => "GET,POST,PUT,DELETE,OPTIONS",
            'Access-Control-Allow-Headers' => "Accept,Date,X-Date,Authorization",
            'Content-Type' => "application/json",
        ];

        $this->mockHandler->append(
            new Response($statusCode, $responseHeaders, json_encode($body))
        );
    }

    private function getStateResponse(
        $reqId = 1,
        $cleaningCategory   = RobotApi::CLEAN_HOUSE,
        $cleaningMode       = RobotApi::MODE_ECO,
        $cleaningModifier   = RobotApi::SINGLE_PASS
    ) {
        // Create this as an array, then encode it as json an decode it again
        // so that we get an object back.
        return json_decode(json_encode([
            'version'   => 1,
            'reqId'     => $reqId,
            'result'    => "ok",
            'error'     => "ui_alert_invalid",
            'data'      => [],
            'state'     => 1,
            'action'    => 0,
            'cleaning'  => [
                'category'      => $cleaningCategory,
                'mode'          => $cleaningMode,
                'modifier'      => $cleaningModifier,
                'spotWidth'     => 0,
                'spotHeight'    => 0,
            ],
            'details'   => [
                'isCharging'        => false,
                'isDocked'          => true,
                'isScheduleEnabled' => true,
                'dockHasBeenSeen'   => false,
                'charge'            => 99
            ],
            'availableCommands' => [
                'start'     => true,
                'stop'      => false,
                'pause'     => false,
                'resume'    => false,
                'goToBase'  => false,
            ],
            'availableServices' => [
                'houseCleaning'     => "basic-1",
                'spotCleaning'      => "basic-1",
                'manualCleaning'    => "basic-1",
                'easyConnect'       => "basic-1",
                'schedule'          => "basic-1",
            ],
            'meta' => [
                'modelName' => "BotVacConnected",
                'firmware'  => "2.0.0",
            ]
        ]));

    }

    public function testConstructor()
    {
        $this->assertTrue($this->robotApi instanceof RobotApi);
    }

    /**
     * @depends testConstructor
     */
    public function testCalculateAuthorizationHeader()
    {
        // Create a datetime that we will use to calculate the authorization
        // header, because it forms part of the calculation, so we need to use
        // a known DT to test.
        $dateTime = new DateTime('2016-12-02 22:17:26', new DateTimeZone('GMT'));
        $parameters = [
            "reqId" => "1",
            "cmd"   => "getRobotState",
        ];

        $expected = 'NEATOAPP 6af05dab5444122f5ee587813782f3982dd2c19fd74e6c2fdb1aa372f1ee82cd';
        $this->assertEquals($expected, $this->robotApi->calculateAuthorizationHeader($dateTime, $parameters));
    }

    /**
     * @depends testConstructor
     */
    public function testGetRobotState()
    {

        // Create this as an array, then encode it as json an decode it again
        // so that we get an object back.
        $body = $this->getStateResponse();

        $this->setupResponse(200, $body);

        $this->assertEquals($body, $this->robotApi->getRobotState());
        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());

        // Verify that the request had the required headers
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('Date', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('X-Agent', $headers);
        $this->assertArraySubset(
            [
                'Host'      => ["nucleo.neatocloud.com"],
                'Accept'    => ['application/vnd.neato.nucleo.v1'],
            ],
            $headers
        );
    }

    /**
     * @depends testGetRobotState
     */
    public function testDismissCurrentAlert()
    {
        // Create this as an array, then encode it as json an decode it again
        // so that we get an object back.
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [],
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->dismissCurrentAlert());

    }

    /**
     * @depends testConstructor
     */
    public function testGetRobotInfo()
    {
        // Create this as an array, then encode it as json an decode it again
        // so that we get an object back.
        $body = json_decode(json_encode([
            'version'   => 1,
            'reqId'     => "1",
            'result'    => "ok",
            'error'     => "ui_alert_invalid",
            'data'      => [
                'modelName'                             => "BotVacConnected",
                'CPUMACID'                              => "a0f6fd28de6d",
                'MainBrdMfgDate'                        => "OPS11616",
                'RobotMfgDate'                          => "OPS12416",
                'BoardRev'                              => 1,
                'ChassisRev'                            => 1,
                'BatteryType'                           => 4,
                'WheelPodType'                          => 1,
                'DropSensorType'                        => 1,
                'MagSensorType'                         => 1,
                'WallSensorType'                        => 1,
                'LDSMotorType'                          => 2,
                'Locale'                                => 1,
                'USMode'                                => 3,
                'ModelName'                             => "905-0249",
                'NeatoServer'                           => "neato.cometa.io",
                'CartID'                                => 1,
                'brushSpeed'                            => 1400,
                'brushSpeedEco'                         => 800,
                'vacuumSpeed'                           => 64880,
                'vacuumPwrPercent'                      => 80,
                'vacuumPwrPercentEco'                   => 65,
                'runTime'                               => 113668640,
                'BrushPresent'                          => 1,
                'VacuumPresent'                         => 1,
                'PadPresent'                            => 0,
                'PlatenPresent'                         => 0,
                'BrushDirection'                        => 0,
                'VacuumDirection'                       => 0,
                'PadDirection'                          => 1,
                'CumulativeCartridgeTimeInSecs'         => 773476,
                'nCleaningsStartedWhereDustBinWasFull'  => 46,
                'BlowerType'                            => 1,
                'BrushMotorType'                        => 1,
                'SideBrushType'                         => 2,
                'SideBrushPower'                        => 1500,
                'nAutoCycleCleaningsStarted'            => 0,
                'hardware_version_major'                => 0,
                'hardware_version_minor'                => 0,
                'software_version_major'                => 0,
                'software_version_minor'                => 0,
                'max_voltage'                           => 0,
                'max_current'                           => 0,
                'voltage_multiplier'                    => 1,
                'current_multiplier'                    => 1,
                'capacity_mode'                         => 0,
                'design_capacity'                       => 4200,
                'design_voltage'                        => 14400,
                'mfg_day'                               => 8,
                'mfg_month'                             => 10,
                'mfg_year'                              => 2011,
                'serial_number'                         => 61966,
                'sw_ver'                                => 1280,
                'data_ver'                              => 2304,
                'mfg_access'                            => 57344,
                'mfg_name'                              => "Panasonic",
                'device_name'                           => "F164A1028",
                'chemistry_name'                        => "LION",
                'Major'                                 => 2,
                'Minor'                                 => 0,
                'Build'                                 => 0,
                'ldsVer'                                => "V2.6.15295",
                'ldsSerial'                             => "OPS12316AA-0142835",
                'ldsCPU'                                => "F2802x/c001",
                'ldsBuildNum'                           => "0000000000",
                'bootLoaderVersion'                     => 27828,
                'uiBoardSWVer'                          => 19,
                'uiBoardHWVer'                          => 0,
                'qaState'                               => 13398,
                'manufacturer'                          => 0,
                'driverVersion'                         => 0,
                'driverID'                              => 0,
                'ultrasonicSW'                          => 0,
                'ultrasonicHW'                          => 0,
                'blowerHW'                              => 0,
                'blowerSWMajor'                         => 0,
                'blowerSWMinor'                         => 0,
            ]
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->getRobotInfo());
    }

    /**
     * @depends testConstructor
     */
    public function testFindMe()
    {
        // Only available on basic-1 models, check available services to know
        // Create this as an array, then encode it as json an decode it again
        // so that we get an object back.
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [],
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->findMe());
    }

    /**
     * @depends testConstructor
     */
    public function testGetGeneralInfo()
    {
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [
                "productNumber" => "905-0321",
                "serial"        => "ZZZ99999-00000000000002",
                "model"         => "BotvacConnected",
                "firmware"      => "2.0.0-861",
                "battery"       => [
                    "level"                 => 3,
                    "timeToEmpty"           => 14320,
                    "timeToFullCharge"      => 1230,
                    "totalCharges"          => 143,
                    "manufacturingDate"     => "2015-02-20",
                    "authorizationStatus"   => 0,
                    "vendor"                => "Vendor Name"
                ]
            ]
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->getGeneralInfo());
    }

    /**
     * @depends testConstructor
     */
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

        $body = $body = $this->getStateResponse(
            "1",
            RobotApi::CLEAN_HOUSE,
            RobotApi::MODE_TURBO,
            RobotApi::SINGLE_PASS
        );

        $expectedRequestBody = [
            'reqId'     => 1,
            'cmd'       => 'startCleaning',
            'params'    => [
                "category"  => RobotApi::CLEAN_HOUSE,
                "mode"      => RobotApi::MODE_TURBO,
                "modifier"  => RobotApi::SINGLE_PASS,
            ]
        ];

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->startCleaning(RobotApi::CLEAN_HOUSE, RobotApi::MODE_TURBO, RobotApi::SINGLE_PASS));

        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expectedRequestBody, json_decode($request->getBody()->getContents(), true));
    }

    /**
     * @depends testConstructor
     */
    public function testStopCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $body = $this->getStateResponse();

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->stopCleaning());

    }

    /**
     * @depends testConstructor
     */
    public function testPauseCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $body = $this->getStateResponse();

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->pauseCleaning());

    }

    /**
     * @depends testConstructor
     */
    public function testResumeCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $body = $this->getStateResponse();

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->resumeCleaning());

    }

    /**
     * @depends testConstructor
     */
    public function testSendToBase()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $body = $this->getStateResponse();

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->sendToBase());
    }

    /**
     * @depends testConstructor
     */
    public function testGetLocalStats()
    {
        // Only available on advanced-1
        // Check available services to know
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [
                "houseCleaning" => [
                    "totalCleanedArea"      => 16.56,
                    "totalCleaningTime"     => 70,
                    "averageCleanedArea"    => 7.27,
                    "averageCleaningTime"   => 7,
                    "history"               => [
                        [
                            "start"                         => "2015-12-12T15:43:12Z",
                            "end"                           => "2015-12-12T16:43:12Z",
                            "suspendedCleaningChargingTime" => 0,
                            "errorTime"                     => 0,
                            "pauseTime"                     => 0,
                            "mode"                          => 1,
                            "area"                          => 6.40,
                            "launchedFrom"                  => "robot",
                            "completed"                     => true
                        ],
                        [
                            "start"                         => "2015-12-12T15:43:12Z",
                            "end"                           => "2015-12-12T16:43:12Z",
                            "suspendedCleaningChargingTime" => 10,
                            "errorTime"                     => 0,
                            "pauseTime"                     => 0,
                            "mode"                          => 1,
                            "area"                          => 10.16,
                            "launchedFrom"                  => "schedule",
                            "completed"                     => false
                        ]
                    ]
                ],
                "spotCleaning" => [
                    "totalCleanedArea"      => 20.54,
                    "totalCleaningTime"     => 30,
                    "averageCleanedArea"    => 10.27,
                    "averageCleaningTime"   => 7,
                    "history"               => [
                        [
                            "start"                         => "2015-12-12T15:43:12Z",
                            "end"                           => "2015-12-12T16:43:12Z",
                            "suspendedCleaningChargingTime" => 0,
                            "errorTime"                     => 0,
                            "pauseTime"                     => 0,
                            "mode"                          => 1,
                            "area"                          => 8.9,
                            "launchedFrom"                  => "app",
                            "completed"                     => true
                        ],
                        [
                            "start"                         => "2015-12-12T15:43:12Z",
                            "end"                           => "2015-12-12T16:43:12Z",
                            "suspendedCleaningChargingTime" => 10,
                            "errorTime"                     => 0,
                            "pauseTime"                     => 0,
                            "mode"                          => 1,
                            "area"                          => 12.45,
                            "launchedFrom"                  => "app",
                            "completed"                     => false
                        ]
                    ]
                ]
            ]
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->getLocalStats());
    }

    /**
     * @depends testConstructor
     */
    public function testGetRobotManualCleaningInfo()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [
                "ip_address"    => "192.168.1.15",
                "port"          => 8154,
                "ssid"          => "My Home Network"
            ]
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->getRobotManualCleaningInfo());

        // Documentation is wrong for this, it says it comes back with a
        // standard response, but it actually gives a state response.

        // Response also contains
        //      public $token =>
        // string(8) "12345678"
        // public $valid_for_seconds =>
        // int(300)
        // which are not documented.

    }

    /**
     * @depends testConstructor
     */
    public function testGetPreferences()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // There are advanced-1 has a lot more preferences that can be got
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [
                "dirtbinAlertReminderInterval"  => 150,
                "filterChangeReminderInterval"  => 43200,
                "brushChangeReminderInterval"   => 172800
                // advanced-1 has these additional fields
                // "robotSounds": true,
                // "dirtbinAlert": true,
                // "allAlerts": true,
                // "leds": true,
                // "buttonClicks": true,
                // "clock24h": true,
                // "locale": "en",
                // "availableLocales": ["en", "it", "de"]
            ]
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->getPreferences());

    }

    /**
     * @depends testConstructor
     */
    public function testSetPreferences()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // There are advanced-1 has a lot more preferences that can be set
        /*$body = $this->getStateResponse(
            "1",
            RobotApi::CLEAN_HOUSE,
            RobotApi::MODE_TURBO,
            RobotApi::SINGLE_PASS
        );*/
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => []
        ]));

        $expectedRequestBody = [
            'reqId'     => 1,
            'cmd'       => 'setPreferences',
            'params'    => [
                "dirtbinAlertReminderInterval"  => 150,
                "filterChangeReminderInterval"  => 43200,
                "brushChangeReminderInterval"   => 172800
            ]
        ];

        $this->setupResponse(200, $body);
        $this->assertEquals(
            $body,
            $this->robotApi->setPreferences(
                150,
                43200,
                172800
            )
        );

        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expectedRequestBody, json_decode($request->getBody()->getContents(), true));

    }

    /**
     * @depends testConstructor
     */
    public function testSetSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => []   // Documentation says this will be present and empty, but actually it is missing.
                                // Actual response also contains 'error'
        ]));

        $expectedRequestBody = [
            'reqId'     => 1,
            'cmd'       => 'setSchedule',
            'params'    => [
                "type"      => RobotApi::SCHEDULE_BASIC,
                "events"    => [
                    [
                        "mode"      => RobotApi::MODE_ECO,
                        "day"       => RobotApi::DAY_MONDAY,
                        "startTime" => "14:45"
                    ],
                    [
                        "mode"      => RobotApi::MODE_ECO,
                        "day"       => RobotApi::DAY_WEDNESDAY,
                        "startTime" => "09:05"
                    ]
                ]
            ]
        ];

        $this->setupResponse(200, $body);
        $this->assertEquals(
            $body,
            $this->robotApi->setSchedule(
                [
                    [
                        "mode"      => RobotApi::MODE_ECO,
                        "day"       => RobotApi::DAY_MONDAY,
                        "startTime" => "14:45"
                    ],
                    [
                        "mode"      => RobotApi::MODE_ECO,
                        "day"       => RobotApi::DAY_WEDNESDAY,
                        "startTime" => "09:05"
                    ]
                ]
            )
        );

        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals($expectedRequestBody, json_decode($request->getBody()->getContents(), true));
    }

    /**
     * @depends testConstructor
     */
    public function testGetSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [
                "type"      => RobotApi::SCHEDULE_BASIC, // This is set to 1 as per the docs, but my BotvacConnected returns 0, which is undocumented
                "enabled"   => true,
                "events"    => [
                    [
                        "mode"      => RobotApi::MODE_ECO, // Only on basic-1
                        "day"       => RobotApi::DAY_MONDAY,
                        "startTime" => "14:45"
                    ],
                    [
                        "mode"      => RobotApi::MODE_ECO,
                        "day"       => RobotApi::DAY_WEDNESDAY,
                        "startTime" => "09:05"
                    ]
                ]
            ]
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->getSchedule());

    }

    /**
     * @depends testConstructor
     */
    public function testEnableSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [],
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->enableSchedule());

    }

    /**
     * @depends testConstructor
     */
    public function testDisableSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $body = json_decode(json_encode([
            "version"   => 1,
            "reqId"     => "1",
            "result"    => "ok",
            "data"      => [],
        ]));

        $this->setupResponse(200, $body);
        $this->assertEquals($body, $this->robotApi->disableSchedule());

    }

}

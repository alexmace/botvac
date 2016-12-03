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
        $body = json_decode(json_encode([
            'version'   => 1,
            'reqId'     => "1",
            'result'    => "ok",
            'error'     => "ui_alert_invalid",
            'data'      => [],
            'state'     => 1,
            'action'    => 0,
            'cleaning'  => [
                'category'      => 2,
                'mode'          => 2,
                'modifier'      => 1,
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
            new Response(200, $responseHeaders, json_encode($body))
        );

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
            new Response(200, $responseHeaders, json_encode($body))
        );

        $this->assertEquals($body, $this->robotApi->dismissCurrentAlert());

    }

    /**
     * @depends testConstructor
     */
    public function testGetRobotInfo()
    {
        $this->markTestIncomplete();
    }

    /**
     * @depends testConstructor
     */
    public function testFindMe()
    {
        // Only available on basic-1 models, check available services to know
        $this->markTestIncomplete();
    }

    /**
     * @depends testConstructor
     */
    public function testGetGeneralInfo()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // Responses are the same, aside from advanced-1 includes a language
        // value
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testStopCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testPauseCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testResumeCleaning()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testSendToBase()
    {
        // Only available (according to the docs) on basic-1, minimal-2 and
        // basic-2 models (but it'd be surprising if it is not on all models)
        // check available services to know
        $this->markTestIncomplete();
    }

    /**
     * @depends testConstructor
     */
    public function testGetLocalStats()
    {
        // Only available on advanced-1
        // Check available services to know
        $this->markTestIncomplete();
    }

    /**
     * @depends testConstructor
     */
    public function testGetRobotManualCleaningInfo()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        $this->markTestIncomplete();
    }

    /**
     * @depends testConstructor
     */
    public function testGetPreferences()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // There are advanced-1 has a lot more preferences that can be got
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testSetPreferences()
    {
        // Only available on basic-1 & advanced 1 models,
        // check available services to know
        // There are advanced-1 has a lot more preferences that can be set
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testSetSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testGetSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testEnableSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

    /**
     * @depends testConstructor
     */
    public function testDisableSchedule()
    {
        // Only available on basic-1 & minimal-1 models,
        // check available services to know
        $this->markTestIncomplete();

    }

}

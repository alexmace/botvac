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
        $this->mockHandler = new MockHandler([
            //new Response(200, ['X-Foo' => 'Bar']),
            /*new Response(202, ['Content-Length' => 0]),
            new RequestException("Error Communicating with Server", new Request('GET', 'test'))*/
        ]);

        $stack = HandlerStack::create($this->mockHandler);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $client = new Client(['handler' => $stack,]);
/*
        // https://nucleo.neatocloud.com/vendors/neato/robots/OPS12416-A0F6FD28DE6D/messages
        //         Accept:application/vnd.neato.nucleo.v1
        // Date:Sun, 27 Nov 2016 14:30:27 GMT
        // Authorization:NEATOAPP 01c966b5f37af4c156da3522fba85c12026cf8fbb6031e56d386d1734f8bd510
        // X-Agent: ios-7|iPhone 4|0.11.3-142
        */
        $this->robotApi = new RobotApi($client, 'serial', 'secret');

    }

    public function testConstructor()
    {
        $this->assertTrue($this->robotApi instanceof RobotApi);
    }

    public function testCalculateAuthorizationHeader()
    {
        // Create a datetime that we will use to calculate the authorization
        // header, because it forms part of the calculation, so we need to use
        // a known DT to test.
        $dateTime = new DateTime('2016-12-02 22:17:26', new DateTimeZone('GMT'));
        $payload = '{"reqId":"1","cmd":"getRobotState"}';

        $expected = 'NEATOAPP 6af05dab5444122f5ee587813782f3982dd2c19fd74e6c2fdb1aa372f1ee82cd';
        $this->assertEquals($expected, $this->robotApi->calculateAuthorizationHeader($dateTime, $payload));
// /Users/amace/src/botvac-twitter/prototype.php:48:
// string(35) "{"reqId":"1","cmd":"getRobotState"}"
// /Users/amace/src/botvac-twitter/prototype.php:49:
// string(29) "Wed, 30 Nov 2016 21:48:08 GMT"
// /Users/amace/src/botvac-twitter/prototype.php:50:
// string(87) "ops12416-a0f6fd28de6d
// Wed, 30 Nov 2016 21:48:08 GMT
// {"reqId":"1","cmd":"getRobotState"}"
// /Users/amace/src/botvac-twitter/prototype.php:51:
// string(64) "242ee41137bf5ed3b47939214d5ab52654098ef6e79768b7d0e022f77d3ac82a"

    }

    /**
     * @depends testConstructor
     */
    public function testGetRobotState()
    {
        //

        $this->mockHandler->append(
            new Response(200, ['X-Foo' => 'Bar'])
        );

        $this->robotApi->getRobotState();
        $this->assertCount(1, $this->container);

        list($request, $response, $error, $options) = $this->container[0];

        // {"reqId": 1, "cmd": "getRobotState", "params": {}}

        // Headers
//         Nucleo uses the HTTP header Accept to version the API. The header has the format:
//
// application/vnd.neato.nucleo.v1
// X-Agent: ios-7|iPhone 4|0.11.3-142
// Authorization: NEATOAPP signature
// Following is pseudo-grammar that illustrates the construction of the Authorization request header. In the example, \n means the Unicode code point U+000A, commonly called newline.
//
// authorization = "NEATOAPP" + " " + signature
//
// signature = HMAC_SHA256(robot_secret_key, UTF8_encoded( string_to_sign))
//
// string_to_sign = lower(robot_serial) + "\n" +
//   date_header + "\n" +
//   body

        // Request URI
        // Nucleo endpoint is: https://nucleo.neatocloud.com:4443.
        // POST /vendors/neato/robots/:robot_serial/messages

        // Request Type
        // Always POST??

        // Request Body
        // {"reqId": 1, "cmd": "getRobotState", "params": {}}

        // Check response
        // State or Standard Response
    }

    /**
     * @depends testConstructor
     */
    public function testDismissCurrentAlert()
    {
        $this->markTestIncomplete();
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

<?php

namespace AlexMace\NeatoBotvac;

use AlexMace\NeatoBotvac\Service\RobotApi;
use PHPUnit\Framework\TestCase;

class RobotTest extends TestCase
{

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
        // Create a mock instance of the RobotApi that the class will
        // use to communicate with the API.
        $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        // A get robot state call should be made so that the Robot class can
        // know what the robot is currently doing, and what services are
        // available.
        $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
            $this->getStateResponse()
        );

        return new Robot($mockRobotApi);
    }

    /**
     * @depends testConstructor
     */
    public function testIsServiceAvailable(Robot $robot)
    {
        $this->assertFalse($robot->isServiceAvailable(Robot::SERVICE_FIND_ME));
        $this->assertFalse($robot->isServiceAvailable(Robot::SERVICE_GENERAL_INFO));
        $this->assertTrue($robot->isServiceAvailable(Robot::SERVICE_HOUSE_CLEANING));
        $this->assertFalse($robot->isServiceAvailable(Robot::SERVICE_LOCAL_STATS));
        $this->assertTrue($robot->isServiceAvailable(Robot::SERVICE_MANUAL_CLEANING));
        $this->assertFalse($robot->isServiceAvailable(Robot::SERVICE_PREFERENCES));
        $this->assertTrue($robot->isServiceAvailable(Robot::SERVICE_SCHEDULE));
        $this->assertTrue($robot->isServiceAvailable(Robot::SERVICE_SPOT_CLEANING));
    }

    /**
     * @depends testConstructor
     */
    public function testIsCharging(Robot $robot)
    {
        $this->assertFalse($robot->isCharging());
    }

    /**
     * @depends testConstructor
     */
    public function testIsDocked(Robot $robot)
    {
        $this->assertTrue($robot->isDocked());
    }

    /**
     * @depends testConstructor
     */
    public function testIsScheduleEnabled(Robot $robot)
    {
        $this->assertTrue($robot->isScheduleEnabled());
    }

    /**
     * @depends testConstructor
     */
    public function testHasSeenDock(Robot $robot)
    {
        $this->assertFalse($robot->isCharging());
    }

    /**
     * @depends testConstructor
     */
    public function testGetBatteryCharge(Robot $robot)
    {
        $this->assertEquals(99, $robot->getBatteryCharge());
    }

    /**
     * @depends testConstructor
     */
    public function testGetStatus(Robot $robot)
    {
        $this->assertEquals(Robot::STATE_IDLE, $robot->getState());
    }

    /**
     * @depends testConstructor
     */
    public function testGetAction(Robot $robot)
    {
        $this->assertEquals(Robot::ACTION_INVALID, $robot->getAction());
    }

    public function testEcoCleanHouse()
    {
        // Setup that startCleaning is called, with category set to 2 (for house
        // cleaning), mode set to 1 for Eco and no other arguments.
        // startCleaning should return a state object, and the state updated in
        // the robot.
        $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        // A get robot state call should be made so that the Robot class can
        // know what the robot is currently doing, and what services are
        // available.
        $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
            $this->getStateResponse()
        );
        $mockRobotApi->expects($this->once())
                     ->method('startCleaning')
                     ->with(
                        $this->equalTo(RobotApi::CLEAN_HOUSE),
                        $this->equalTo(RobotApi::MODE_ECO)
                     )
                     ->willReturn($this->getStateResponse());

        $robot = new Robot($mockRobotApi);
        $robot->ecoCleanHouse();
    }

    /**
     * Should rationalise this with the above test - perhaps via data provider?
     */
    public function testCleanHouse()
    {
        // Setup that startCleaning is called, with category set to 2 (for house
        // cleaning), mode set to 1 for Eco and no other arguments.
        // startCleaning should return a state object, and the state updated in
        // the robot.
        $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        // A get robot state call should be made so that the Robot class can
        // know what the robot is currently doing, and what services are
        // available.
        $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
            $this->getStateResponse()
        );
        $mockRobotApi->expects($this->once())
                     ->method('startCleaning')
                     ->with(
                        $this->equalTo(RobotApi::CLEAN_HOUSE),
                        $this->equalTo(RobotApi::MODE_TURBO)
                     )
                     ->willReturn($this->getStateResponse());

        $robot = new Robot($mockRobotApi);
        $robot->cleanHouse();
    }
    //
    // public function testStopCleaning()
    // {
    //     $this->markTestIncomplete();
    //     'availableCommands' => [
    //         'start'     => true,
    //         'stop'      => false,
    //         'pause'     => false,
    //         'resume'    => false,
    //         'goToBase'  => false,
    //     ],
    // }
    //
    // public function testPauseCleaning()
    // {
    //     $this->markTestIncomplete();
    //     'availableCommands' => [
    //         'start'     => true,
    //         'stop'      => false,
    //         'pause'     => false,
    //         'resume'    => false,
    //         'goToBase'  => false,
    //     ],
    // }
    //
    // public function testReturnToBase()
    // {
    //     $this->markTestIncomplete();
    //     'availableCommands' => [
    //         'start'     => true,
    //         'stop'      => false,
    //         'pause'     => false,
    //         'resume'    => false,
    //         'goToBase'  => false,
    //     ],
    // }


    /**
     * Should rationalise this with the above clean house tests - perhaps via data provider?
     */
    public function testCleanSpot()
    {
        // Setup that startCleaning is called, with category set to 2 (for house
        // cleaning), mode set to 1 for Eco and no other arguments.
        // startCleaning should return a state object, and the state updated in
        // the robot.
        $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        // A get robot state call should be made so that the Robot class can
        // know what the robot is currently doing, and what services are
        // available.
        $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
            $this->getStateResponse()
        );
        $mockRobotApi->expects($this->once())
                     ->method('startCleaning')
                     ->with(
                        $this->equalTo(RobotApi::CLEAN_SPOT),
                        $this->equalTo(RobotApi::MODE_TURBO)
                     )
                     ->willReturn($this->getStateResponse());

        $robot = new Robot($mockRobotApi);
        $robot->cleanSpot();
    }


    /**
     * Should rationalise this with the above clean house tests - perhaps via data provider?
     */
    public function testDeepCleanSpot()
    {
        // Setup that startCleaning is called, with category set to 2 (for house
        // cleaning), mode set to 1 for Eco and no other arguments.
        // startCleaning should return a state object, and the state updated in
        // the robot.
        $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        // A get robot state call should be made so that the Robot class can
        // know what the robot is currently doing, and what services are
        // available.
        $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
            $this->getStateResponse()
        );
        $mockRobotApi->expects($this->once())
                     ->method('startCleaning')
                     ->with(
                        $this->equalTo(RobotApi::CLEAN_SPOT),
                        $this->equalTo(RobotApi::MODE_TURBO),
                        $this->equalTo(RobotApi::DOUBLE_PASS)
                     )
                     ->willReturn($this->getStateResponse());

        $robot = new Robot($mockRobotApi);
        $robot->deepCleanSpot();
    }



    /**
     * Should rationalise this with the above clean house tests - perhaps via data provider?
     */
    public function testEcoCleanSpot()
    {

            // Setup that startCleaning is called, with category set to 2 (for house
            // cleaning), mode set to 1 for Eco and no other arguments.
            // startCleaning should return a state object, and the state updated in
            // the robot.
            $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

            // A get robot state call should be made so that the Robot class can
            // know what the robot is currently doing, and what services are
            // available.
            $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
                $this->getStateResponse()
            );
            $mockRobotApi->expects($this->once())
                         ->method('startCleaning')
                         ->with(
                            $this->equalTo(RobotApi::CLEAN_SPOT),
                            $this->equalTo(RobotApi::MODE_ECO)
                         )
                         ->willReturn($this->getStateResponse());

            $robot = new Robot($mockRobotApi);
            $robot->ecoCleanSpot();
    }



    /**
     * Should rationalise this with the above clean house tests - perhaps via data provider?
     */
    public function testEcoDeepCleanSpot()
    {

            // Setup that startCleaning is called, with category set to 2 (for house
            // cleaning), mode set to 1 for Eco and no other arguments.
            // startCleaning should return a state object, and the state updated in
            // the robot.
            $mockRobotApi = $this->getMockBuilder(RobotApi::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

            // A get robot state call should be made so that the Robot class can
            // know what the robot is currently doing, and what services are
            // available.
            $mockRobotApi->expects($this->once())->method('getRobotState')->willReturn(
                $this->getStateResponse()
            );
            $mockRobotApi->expects($this->once())
                         ->method('startCleaning')
                         ->with(
                            $this->equalTo(RobotApi::CLEAN_SPOT),
                            $this->equalTo(RobotApi::MODE_ECO),
                            $this->equalTo(RobotApi::DOUBLE_PASS)
                         )
                         ->willReturn($this->getStateResponse());

            $robot = new Robot($mockRobotApi);
            $robot->ecoDeepCleanSpot();
    }
    //
    // public function testEnableSchedule()
    // {
    //     $this->markTestIncomplete();
    // }
    //
    // public function testDisableSchedule()
    // {
    //     $this->markTestIncomplete();
    // }

}

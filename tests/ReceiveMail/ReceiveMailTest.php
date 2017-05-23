<?php
declare(strict_types=1);
namespace Pbc\ReceiveMail;

use PHPUnit\Framework\TestCase;
use Mockery as m;


/**
 * ReceiveMailTest
 *
 * Tests for ReceiveMail methods
 *
 * @author Nate Nolting <naten@paulbunyan.net>
 * @package Pbc\ReceiveMail
 */
class ReceiveMailTest extends TestCase
{

    /** @var m::mock $functions*/
    public static $functions;

    public function setUp()
    {
        parent::setUp();
        self::$functions = m::mock();
    }

    public function tearDown()
    {
        parent::tearDown();

    }

    /**
     * Test that the constructor will throw an exception if a field does not have a setter
     * @expectedException     \Exception
     * @expectedExceptionMessage Unknown field "foobar"
     */
    public function testThatTheConstructorWillThrowAnExceptionIfAFieldDoesNotHaveASetter()
    {
        $receiveMail = new ReceiveMail(['foobar' => 123]);
    }


    /**
     * Test that a field will return exception messages when the unknown setter is caught
     */
    public function testThatAFieldWillReturnExceptionMessagesWhenTheUnknownSetterIsCaught()
    {
        try {
            $receiveMail = new ReceiveMail(['foobar' => 123]);
        }
        catch(\Exception $ex) {
            $this->assertSame('Unknown field "foobar"', $ex->getMessage());
        }
    }



}

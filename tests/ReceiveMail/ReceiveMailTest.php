<?php
declare(strict_types=1);

namespace Pbc\ReceiveMail;

use Mockery as m;
use PHPUnit\Framework\TestCase;


/**
 * Mocked imap_open
 */
function imap_open($server, $username, $password)
{
    return ReceiveMailTest::$functions->imap_open($server, $username, $password);
}

/**
 * Mocked function_exists
 */
function function_exists($function_name)
{
    return ReceiveMailTest::$functions->function_exists($function_name);
}

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

    /** @var m::mock $functions */
    public static $functions;

    /** @var \Faker\Factory */
    public static $faker;

    /**
     * Setup the tests
     */
    public function setUp()
    {
        parent::setUp();
        self::$functions = m::mock();
        self::$faker = \Faker\Factory::create();
    }

    /**
     * Tear down the tests
     */
    public function tearDown()
    {
        parent::tearDown();
        m::close();

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
        } catch (\Exception $ex) {
            $this->assertSame('Unknown field "foobar"', $ex->getMessage());
        }
    }

    /**
     *  Test that the connection method will connect
     */
    public function testThatConnectWillConnect()
    {
        self::$functions->shouldReceive('function_exists')->once()->withArgs(['imap_open'])->andReturn(true);
        self::$functions->shouldReceive('imap_open')->once()->andReturn(true);
        $receiveMail = new ReceiveMail([]);
        $connect = $receiveMail->connect();
        $this->assertTrue($receiveMail->getConnection());
        $this->assertInstanceOf(ReceiveMail::class, $connect);

    }

    /**
     *  Test that the connection method will throw an exception
     * @expectedException     \Exception
     * @expectedExceptionMessage Error: Connecting to mail server
     */
    public function testThatConnectWillThrowAnException()
    {
        self::$functions->shouldReceive('function_exists')->once()->andReturn(true);
        self::$functions->shouldReceive('imap_open')->once()->andReturn(false);
        $receiveMail = new ReceiveMail([]);
        $receiveMail->connect();

    }

    /**
     *  Test that the connection method will throw an exception if imap_open does not exist
     * @expectedException     \Exception
     * @expectedExceptionMessage Error: "imap_open" function does not exist
     */
    public function testThatConnectWillThrowAnExceptionIfImapOpenDoeNotExist()
    {
        self::$functions->shouldReceive('function_exists')->once()->andReturn(false);
        $receiveMail = new ReceiveMail([]);
        $receiveMail->connect();
    }

    /**
     * Check that the constructor can set the username field
     */
    public function testConstructorCanSetUsername()
    {

        $value = self::$faker->word;
        $receiveMail = new ReceiveMail(['username' => $value]);
        $this->assertSame($value, $receiveMail->getUsername());
    }

    /**
     * Check that the constructor can set the email field
     */
    public function testConstructorCanSetEmail()
    {

        $email = self::$faker->email;
        $receiveMail = new ReceiveMail(['email' => $email]);
        $this->assertSame($email, $receiveMail->getEmail());
    }

    /**
     * Check that the constructor can set the password field
     */
    public function testConstructorCanSetPassword()
    {

        $password = self::$faker->password;
        $receiveMail = new ReceiveMail(['password' => $password]);
        $this->assertSame($password, $receiveMail->getPassword());

    }

    /**
     * Check that the constructor can set the ssl field
     */
    public function testConstructorCanSetSsl()
    {

        $value = true;
        $receiveMail = new ReceiveMail(['ssl' => $value]);
        $this->assertTrue($receiveMail->getSsl());
    }


    /**
     * Check that the constructor can set the port field
     */
    public function testConstructorCanSetPort()
    {

        $value = self::$faker->randomDigit;
        $receiveMail = new ReceiveMail(['port' => $value]);
        $this->assertSame($value, $receiveMail->getPort());
    }

    /**
     * Check that the constructor can set the mailServer field
     */
    public function testConstructorCanSetMailServer()
    {

        $value = 'pop.' . self::$faker->freeEmailDomain;
        $receiveMail = new ReceiveMail(['mailServer' => $value]);
        $this->assertSame($value, $receiveMail->getMailServer());
    }


    /**
     * Check that the constructor can set the serverType field
     */
    public function testConstructorCanSetServerType()
    {

        $value = self::$faker->word;
        $receiveMail = new ReceiveMail(['serverType' => $value]);
        $this->assertSame($value, $receiveMail->getserverType());
    }


    /**
     * Check that the constructor can set the imapPort field
     */
    public function testConstructorCanSetImapPort()
    {

        $value = 123;
        $receiveMail = new ReceiveMail(['imapPort' => $value]);
        $this->assertSame($value, $receiveMail->getImapPort());
    }


    /**
     * Check that the constructor can create a pop server stirng
     */
    public function testConstructorCanCreateAPopServerString()
    {

        $values = [
            'port' => self::$faker->randomDigit,
            'mailServer' => 'pop.' . self::$faker->freeEmailDomain,
        ];
        $receiveMail = new ReceiveMail($values);
        $this->assertSame('{' . $values['mailServer'] . ':' . $values['port'] . '/pop3}INBOX',
            $receiveMail->getServer());
    }


    /**
     * Check that the constructor can create a pop server stirng with Ssl
     */
    public function testConstructorCanCreateAPopServerStringWithSsl()
    {

        $values = [
            'port' => self::$faker->randomDigit,
            'mailServer' => 'pop.' . self::$faker->freeEmailDomain,
            'ssl' => true,
        ];
        $receiveMail = new ReceiveMail($values);
        $this->assertSame('{' . $values['mailServer'] . ':' . $values['port'] . '/pop3/ssl}INBOX',
            $receiveMail->getServer());
    }


    /**
     * Check that the constructor can create a imap server stirng
     */
    public function testConstructorCanCreateAnImapServerString()
    {

        $values = [
            'port' => self::$faker->randomDigit,
            'mailServer' => 'pop.' . self::$faker->freeEmailDomain,
            'serverType' => 'imap',

        ];
        $receiveMail = new ReceiveMail($values);
        $this->assertSame('{' . $values['mailServer'] . ':' . $values['port'] . '}INBOX', $receiveMail->getServer());
    }

    /**
     * Check that the constructor can create a imap server stirng with no port provided
     */
    public function testConstructorCanCreateAnImapServerStringWithNoPortProvided()
    {

        $values = [
            'port' => null,
            'mailServer' => 'imap.' . self::$faker->freeEmailDomain,
            'serverType' => 'imap'

        ];
        $receiveMail = new ReceiveMail($values);
        $this->assertSame('{' . $values['mailServer'] . ':' . $receiveMail->getImapPort() . '}INBOX',
            $receiveMail->getServer());
    }

}

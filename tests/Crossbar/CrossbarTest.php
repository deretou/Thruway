<?php

class CrossbarTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Thruway\Connection
     */
    protected $_conn;
    protected $_error;
    protected $_testArgs;
    protected $_testKWArgs;
    protected $_publicationId;
    protected $_details;
    protected $_testResult;
    protected $_echoResult;

    public function setUp()
    {
        $this->_testArgs   = null;
        $this->_testResult = null;
        $this->_error      = null;

        $this->_conn = new \Thruway\Connection(
            [
                "realm"       => 'realm1',
                "url"         => 'ws://127.0.0.1:8080/ws',
                "max_retries" => 0,
            ]
        );
    }


    public function testCall()
    {

        $this->_conn->on(
            'open',
            function (\Thruway\ClientSession $session) {

                for ($x = 0; $x <= 10; $x++) {
                    $session->call('com.example.add2', [$x, 18])->then(
                        function ($res) use ($x) {

                            $this->_testResult = $res;

                            if ($res[0] >= 28 || $x >= 10) {
                                $this->_conn->close();
                            }

                        },
                        function ($error) {
                            $this->_conn->close();
                            $this->_error = $error;
                        }
                    );

                }
            }
        );

        $this->_conn->open();

        $this->assertNull($this->_error, "Got this error when making an RPC call: {$this->_error}");
        $this->assertTrue(is_numeric($this->_testResult[0]));
        $this->assertEquals(28, $this->_testResult[0]);
    }


    public function testSubscribe()
    {
        $this->_conn->on('open', function (\Thruway\ClientSession $session) {

            $session->subscribe('com.example.oncounter', function ($args) {
                $this->_conn->close();
                $this->_testArgs = $args;
            });
        });

        $this->_conn->open();

        $this->assertGreaterThan(0, $this->_testArgs[0]);
    }

    public function testRegister()
    {
        $this->_conn->on('open', function (\Thruway\ClientSession $session) {

            $session->register('com.example.mul2', function ($args) {
                $this->_conn->close();
                $this->_testArgs = $args;

            });
        });

        $this->_conn->open();

        $this->assertGreaterThan(0, $this->_testArgs[0]);
    }

}

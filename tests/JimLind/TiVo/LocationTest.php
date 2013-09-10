<?php
namespace JimLind\TiVo;

function exec($command, &$return, &$status)
{
	$return = LocationTest::$execReturn;
	$status = LocationTest::$execStatus;
}

class LocationTest extends \PHPUnit_Framework_TestCase
{
	public static $execReturn;
	public static $execStatus;

	private $locator;
	private $logger;

	public function setUp() {
		$mockBuilder   = $this->getMockBuilder('\Symfony\Bridge\Monolog\Logger');
		$this->logger  = $mockBuilder->disableOriginalConstructor()->getMock();
		$this->locator = new \JimLind\TiVo\Location($this->logger);
	}

	/**
     * @dataProvider provider
     */
    public function testLocatorFind($return, $status, $output)
    {
		self::$execReturn = $return;
		self::$execStatus = $status;

		// Expect something to be logged if bad output.
		if ($output === false) {
			$this->logger->expects($this->once())->method('addWarning');
		} else {
			$this->logger->expects($this->exactly(0))->method('addWarning');
		}

		$found = $this->locator->find();
		$this->assertEquals($found, $output);
    }

	public static function getExecReturn()
	{
		return $this->execReturn;
	}

	public static function getExecStatus()
	{
		return $this->execStatus;
	}

	public function provider()
	{
		return array(
			array(
				'return' => null,
				'status' => 27,
				'output' => false,
			),
			array(
				'return' => null,
				'status' => 1,
				'output' => false,
			),
			array(
				'return' => null,
				'status' => 0,
				'output' => false,
			),
			array(
				'return' => array(),
				'status' => 0,
				'output' => false,
			),
			array(
				'return' => array(' address = [192.168.1.187]'),
				'status' => 0,
				'output' => '192.168.1.187',
			),
			array(
				'return' => array(' address = [192.168.1.X]'),
				'status' => 0,
				'output' => false,
			),
			array(
				'return' => array(
					'+ eth0 IPv4 Living Room _tivo-videos._tcp local',
					'= eth0 IPv4 Living Room _tivo-videos._tcp local',
					' hostname = [DVR-F449.local]',
					' address = [192.168.0.42]',
					' port = [443]',
					' txt = ["TSN=65200118047F449" "platform=tcd/Series3" "swversion=11.0m-01-2-652" "path=/TiVoConnect?Command=QueryContainer&Container=%2FNowPlaying" "protocol=https"]',
				),
				'status' => 0,
				'output' => '192.168.0.42',
			)
		);
	}
}

<?php

namespace JimLind\TiVo;

class LocationTest extends \PHPUnit_Framework_TestCase {

	private $logger;
	private $process;
	private $locator;

	public function setUp() {
		$mockLoggerBuilder = $this->getMockBuilder('\Symfony\Bridge\Monolog\Logger');
		$this->logger = $mockLoggerBuilder->disableOriginalConstructor()->getMock();

		$mockProcessBuilder = $this->getMockBuilder('\Symfony\Component\Process\Process');
		$this->process = $mockProcessBuilder->disableOriginalConstructor()->getMock();

		$this->locator = new \JimLind\TiVo\Location($this->logger, $this->process);
	}

	/**
	 * @dataProvider provider
	 */
	public function testLocatorFind($return, $output) {

		$this->process->expects($this->any())
				->method('getOutput')
				->will($this->returnValue($return));

		// Expect something to be logged if bad output.
		if ($output === false) {
			$this->logger->expects($this->once())
					->method('addWarning');
		} else {
			$this->logger->expects($this->exactly(0))
					->method('addWarning');
		}

		$found = $this->locator->find();
		$this->assertEquals($found, $output);
	}

	public function provider() {
		return array(
			array(
				'return' => null,
				'output' => false,
			),
			array(
				'return' => 0,
				'output' => false,
			),
			array(
				'return' => '',
				'output' => false,
			),
			array(
				'return' => ' address = [192.168.1.187]',
				'output' => '192.168.1.187',
			),
			array(
				'return' => ' address = [192.168.1.X]',
				'output' => false,
			),
			array(
				'return' => '+ eth0 IPv4 Living Room _tivo-videos._tcp' . PHP_EOL .
				'= eth0 IPv4 Living Room _tivo-videos._tcp' . PHP_EOL .
				' hostname = [DVR-F449.local]' . PHP_EOL .
				' address = [192.168.0.42]' . PHP_EOL .
				' port = [443]' . PHP_EOL .
				' txt = ["TSN=65200118047F449" "platform=tcd/Series3"]',
				'output' => '192.168.0.42',
			)
		);
	}

}

<?php

namespace JimLind\TiVo;

class NowPlayingTest extends \PHPUnit_Framework_TestCase {

	private $location;
	private $logger;
	private $process;

	public function setUp() {
		$mockLocationBuilder = $this->getMockBuilder('\JimLind\TiVo\Location');
		$this->location = $mockLocationBuilder->disableOriginalConstructor()->getMock();

		$mockLoggerBuilder = $this->getMockBuilder('\Symfony\Bridge\Monolog\Logger');
		$this->logger = $mockLoggerBuilder->disableOriginalConstructor()->getMock();

		$mockProcessBuilder = $this->getMockBuilder('\Symfony\Component\Process\Process');
		$this->process = $mockProcessBuilder->disableOriginalConstructor()->getMock();
	}

	/**
	 * @dataProvider nowPlayingDownloadProvider
	 */
	public function testNowPlayingDownload($ip, $return, $expected, $output) {
		$this->location->expects($this->once())
				->method('find')
				->will($this->returnValue($ip));

		// If the IP is legit.
		if ($ip) {
			// Basic setup for the process service
			$this->process->expects($this->atLeastOnce())
					->method('setCommandLine');
			$this->process->expects($this->atLeastOnce())
					->method('setTimeout');
			$this->process->expects($this->atLeastOnce())
					->method('run');

			// Recursive return values
			foreach($return as $index => $xmlValue) {
				$this->process->expects($this->at(($index * 4) + 3))
					->method('getOutput')
					->will($this->returnValue($xmlValue));
			}
		}

		// Expect something to be logged if bad output.
		if ($output === false) {
			$this->logger->expects($this->once())
					->method('addWarning');
		} else {
			$this->logger->expects($this->exactly(0))
					->method('addWarning');
		}

		// Constructor
		$nowPlaying = new \JimLind\TiVo\NowPlaying(
			$this->location,
			'MAK',
			$this->logger,
			$this->process
		);
		// Download
		$actual = $nowPlaying->download();
		$this->assertEquals($expected, $actual);
	}

	public function nowPlayingDownloadProvider() {
		return array(
			array(
				'ip' => false,
				'return' => false,
				'expected' => array(),
				'output' => false,
			),
			array(
				'ip' => '192.168.0.1',
				'return' => array(''),
				'expected' => array(),
				'output' => true,
			),
                        array(
                                'ip' => '192.168.0.1',
				'return' => array(
					'<xml><NorseWords>Ragnarok</NorseWords></xml>',
				),
				'expected' => array(),
				'output' => true,
			),
			array(
				'ip' => '192.168.0.1',
				'return' => array(
					'<xml><ItemCount>2</ItemCount><Item /><Item /></xml>',
					'<xml><ItemCount>1</ItemCount><Item /></xml>',
					'<xml><ItemCount>0</ItemCount></xml>',
				),
				'expected' => array(
					new \SimpleXMLElement('<Item />'),
					new \SimpleXMLElement('<Item />'),
					new \SimpleXMLElement('<Item />'),
				),
				'output' => true,
			),
		);
	}

}
<?php

namespace Image;

use Image\Google as Google;

class GoogleTest extends \PHPUnit_Framework_TestCase {

    private $key;
    private $process;
    private $fixture;

    public function setUp() {
        $this->key = (string) rand();
        
        $mockProcessBuilder = $this->getMockBuilder('\Symfony\Component\Process\Process');
	$this->process = $mockProcessBuilder->disableOriginalConstructor()->getMock();
        
        $this->fixture = new Google($this->key, $this->process);
    }

    /**
     * @dataProvider getOneURLProvider
     */
    public function testGetOneURL($keywords, $start, $expected) {
        $this->expected = $expected;
        
        $this->process->expects($this->once())
            ->method('setCommandLine')
            ->with($this->callback(function($input) {
                // If any of the data we are trying to pass in doesn't make it
                // fail the test.
                foreach($this->expected as $pair) {
                    if (strpos($input, $pair) === false) {
                        var_dump($input);
                        var_dump($pair);
                        return false;
                    }
                }
                // Otherwise it passes.
                return true;
            }));
        $this->process->expects($this->once())
            ->method('setTimeout');
        $this->process->expects($this->once())
            ->method('run');
        $this->process->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue('{"responseData":{"results":[{"unescapedUrl":"href"}]}}'));
        
        $this->fixture->getOneURL($keywords, $start);
    }

    public function getOneURLProvider() {
        return array(
            array(
                'keywords' => 'foo',
                'start' => '0',
                'expects' => array(
                    'q=foo',
                    'start=0', 
                    'key=' . $this->key,
                )
            ),
            array(
                'keywords' => 'bar',
                'start' => '1',
                'expects' => array(
                    'q=bar',
                    'start=1',
                    '&key=' . $this->key,
                )
            ),
            array(
                'keywords' => 'foo bar',
                'start' => '2',
                'expects' => array(
                    'q=foo+bar',
                    'start=2',
                    '&key=' . $this->key,
                )
            ),
        );
    }

}
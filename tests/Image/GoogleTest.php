<?php

namespace Image;

class GoogleTest extends \PHPUnit_Framework_TestCase {

    private $key;
    private $process;
    private $fixture;

    public function setUp() {
        $this->key = (string) rand();
        
        $mockProcessBuilder = $this->getMockBuilder('\Symfony\Component\Process\Process');
	$this->process = $mockProcessBuilder->disableOriginalConstructor()->getMock();
        
        $this->fixture = new \Image\Google($this->key, $this->process);
    }

    /**
     * @dataProvider getOneURLProvider
     */
    public function testGetOneURL($keywords, $expected) {
        $this->expected = $expected;
        
        $this->process->expects($this->once())
            ->method('setCommandLine')
            ->with($this->callback(function($input) {
                return (strpos($input, $this->expected) !== false);
            }));
        $this->process->expects($this->once())
            ->method('setTimeout');
        $this->process->expects($this->once())
            ->method('run');
        $this->process->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue('{"responseData":{"results":[{"unescapedUrl":"href"}]}}'));
        
        $this->fixture->getOneURL($keywords);
    }

    public function getOneURLProvider() {
        return array(
            array(
                'keywords' => 'foo',
                'expects' => 'q=foo&start=0&key=' . $this->key
            ),
            array(
                'keywords' => 'bar',
                'expects' => 'q=bar&start=0&key=' . $this->key
            ),
            array(
                'keywords' => 'foo bar',
                'expects' => 'q=foo+bar&start=0&key=' . $this->key
            ),
        );
    }

}
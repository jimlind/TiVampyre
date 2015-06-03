<?hh

namespace TiVampyre\Video\ChapterGenerator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EdlParser
{
	protected $logger = null;

	/**
     * Set the Logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

	public function parse($edlFile)
	{
		$commercialList = array();
		$edlContent     = file_get_contents($edlFile);
		$edlLineList    = preg_split ('/\r\n|\n|\r/', $edlContent);
		foreach ($edlLineList as $edlLine) {
			$this->parseLine($edlLine, $commercialList);
		}

		return $commercialList;
	}

	protected function parseLine($edlLine, &$outputList)
	{
		$data = preg_split ('/\s/', $edlLine);
		if (count($data) == 3 && $data[2] === '0') {
			$outputList[] = array(
				'start' => $data[0],
				'end'   => $data[1],
			);
		}
	}
}

<?hh

namespace TiVampyre\Video\ChapterGenerator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CommercialParser
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

	public function parse($commercialList)
	{
		$chapterList = array();

		foreach($commercialList as $index => $commercial) {
			if (!isset($commercialList[$index + 1])){
				continue;
			}
			$start = $commercial['end'];
			$end   = $commercialList[$index + 1]['start'];

			$chapterList[] = array(
				'start' => (float) $start,
				'end'   => (float) $end,
			);
		}

		return $this->bookendChapterList($commercialList, $chapterList);
	}

	protected function bookendChapterList($commercialList, $chapterList)
	{
		if (count($commercialList) > 0) {
			$firstChapter = array(
				'start' => (float) 0,
				'end'   => (float) $commercialList[0]['start'],
			);
			array_unshift($chapterList, $firstChapter);

			$maxChapter  = count($commercialList) - 1;
			$lastStart   = $commercialList[$maxChapter]['end'];
			$lastChapter =  array(
				'start' => (float) $lastStart,
				'end'   => (float) $lastStart + (24 * 60 * 60), // start + 24 hours
			);
			array_push($chapterList, $lastChapter);
		}

		return $this->cleanChapterList($chapterList);
	}

	protected function cleanChapterList($chapterList, $minimumChapter = 5)
	{
		foreach($chapterList as $index => $chapter) {
			if (($chapter['end'] - $chapter['start']) < $minimumChapter) {
				unset($chapterList[$index]);
			}
		}

		return $chapterList;
	}
}

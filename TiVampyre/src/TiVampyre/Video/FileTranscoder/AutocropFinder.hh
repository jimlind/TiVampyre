<?hh

namespace TiVampyre\Video\FileTranscoder;

use JimLind\TiVo\Utilities as TiVoUtilities;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Discern information about an MPEG file
 */
class AutocropFinder
{
	/**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        protected ProcessBuilder $processBuilder,
    ) {
        // Default to the NullLogger
        $this->setLogger(new NullLogger());
    }

	/**
     * Set the Logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

	public function findAutocrop(string $filePath): array
    {
        $process = $this->getProcess($filePath);
        $process->run();

        // HandBrake outputs lots of information on error
        $processOutput = $process->getErrorOutput();
        $aspectRatio   = $this->parseAutocrop($processOutput);

        return $aspectRatio;
    }

    protected function parseAutocrop(string $processOutput): array
    {
		$matches = array();
		$pattern = '|autocrop = (\d+)/(\d+)/(\d+)/(\d+)|';
		preg_match_all($pattern, $processOutput, $matches);

		if (count($matches) === 5) {
			// Data successfully found.
			return array(
				'top'    => intval($matches[1][0]),
				'bottom' => intval($matches[2][0]),
				'left'   => intval($matches[3][0]),
				'right'  => intval($matches[4][0]),
			);
		}
		// Nothing found.
		return [
			'top'    => 0,
			'bottom' => 0,
			'left'   => 0,
			'right'  => 0,
		];
    }
    /**
     * Build a Process with standard output at a 60 second timeout
     */
    protected function getProcess(string $filePath): Process
    {
        $arguments = [
            '--input=' . $filePath,
            '--out=/dev/null',
            '--start-at=duration:0',
            '--stop-at=duration:1',
        ];

        $this->processBuilder->setPrefix('HandBrakeCLI');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(60);
        return $this->processBuilder->getProcess();
    }
}

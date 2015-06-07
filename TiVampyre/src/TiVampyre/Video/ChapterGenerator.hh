<?hh

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Video\ChapterGenerator\CommercialParser;
use TiVampyre\Video\ChapterGenerator\EdlParser;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Interface with the Comskip Windows executable.
 */
class ChapterGenerator
{
    protected $logger = null;

    public function __construct(
        private ProcessBuilder $processBuilder,
        private EdlParser $edlParser,
        private CommercialParser $commercialParser,
        private string $comskipPath,
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
        $this->edlParser->setLogger($logger);
        $this->commercialParser->setLogger($logger);
    }

    public function generate(string $mpegFilename): array
    {
        $this->generateEdl($mpegFilename);

        $rawFilename    = substr($mpegFilename, 0, strrpos($mpegFilename, '.'));
        $edlFilename    = $rawFilename . '.edl';
        $commercialList = $this->edlParser->parse($edlFilename);
        $chapterList    = $this->commercialParser->parse($commercialList);

        $this->deleteEdlFiles($rawFilename . '.*');

        return $chapterList;
    }

    protected function generateEdl(string $mpegPath): void
    {
        $comskipExe = $this->comskipPath . 'comskip.exe';
        $comskipIni = $this->comskipPath . 'comskip.ini';

        $arguments = [
            $comskipExe,
            '--ini=' . $comskipIni,
            $mpegPath,
        ];

        $this->processBuilder->setPrefix('wine');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();
    }

    protected function deleteEdlFiles(string $searchPath): void
    {
        $rmList  = ['.edl', '.log', '.txt'];

        foreach(glob($searchPath) as $file) {
            $extension = substr($file, strrpos($file, '.'));
            if (in_array($extension, $rmList)) {
                unlink($file);
            }
        }
    }
}

<?hh

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\ProcessBuilder;
use TiVampyre\Entity\ShowEntity;

/**
 * Label Video Files
 */
class Labeler
{
    protected $logger = null;

    public function __construct(
        protected ProcessBuilder $processBuilder
    ): void {
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

    public function label(ShowEntity $show, $file): void
    {
        $this->addMetadata($show, $file);
        $this->renameFile($show, $file);
    }

    public function addMetadata(ShowEntity $show, $file)
    {
        $episodeTitle  = $show->getEpisodeTitle();
        $description   = $show->getDescription();
        $showTitle     = $show->getShowTitle();
        $episodeNumber = $show->getEpisodeNumber();
        $station       = $show->getStation();

        $arguments = [$file];
        if ($episodeTitle) $arguments[] = '--title=' . $episodeTitle;
        if ($description) $arguments[] = '--description=' . $description;
        if ($showTitle) $arguments[] = '--TVShowName=' . $showTitle;
        if ($episodeNumber) $arguments[] = '--TVEpisode=' . $episodeNumber;
        if ($episodeNumber) $arguments[] = '--TVEpisodeNum=' . $episodeNumber;
        if ($station) $arguments[] = '--TVNetwork=' . $station;

        if ($episodeTitle || $episodeNumber) {
            $arguments[] = '--stik=TV Show';
        } else {
            $arguments[] = '--stik=Movie';
        }

        $arguments[] = '--overWrite';

        $this->processBuilder->setPrefix('AtomicParsley');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();
    }

    public function renameFile(ShowEntity $show, string $filePath): string {
        $newFilename = $this->getNewFilename($show);
        $pathParts   = pathinfo($filePath);
        $newFilePath = $pathParts['dirname'] . '/' . $newFilename;

        $this->processBuilder->setPrefix('mv');
        $this->processBuilder->setArguments([$filePath, $newFilePath]);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();

        return $newFilePath;
    }

    protected function getNewFilename(ShowEntity $show): string
    {
        $showTitle     = $show->getShowTitle();
        $episodeTitle  = $show->getEpisodeTitle();
        $episodeNumber = $show->getEpisodeNumber();

        $rawFilename = $showTitle;
        if ($episodeNumber !== 0) {
            $rawFilename .= ' ' . $episodeNumber;
        }
        if ($episodeTitle !== '') {
            $rawFilename .= ' ' . $episodeTitle;
        }
        $rawFilename .= '.m4v';

        return preg_replace(['/[^A-Za-z0-9 \.]/', '/\s\s+/'], ' ', $rawFilename);
    }
}

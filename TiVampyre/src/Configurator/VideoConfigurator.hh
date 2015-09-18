<?hh

namespace Configurator;

use Silex\Application;
use TiVampyre\Video\ChapterGenerator;
use TiVampyre\Video\ChapterGenerator\CommercialParser;
use TiVampyre\Video\ChapterGenerator\EdlParser;
use TiVampyre\Video\Cleaner;
use TiVampyre\Video\Labeler;
use TiVampyre\Video\FileTranscoder;
use TiVampyre\Video\FileTranscoder\AspectRatioFinder;
use TiVampyre\Video\FileTranscoder\AutocropFinder;
use TiVampyre\Video\FileTranscoder\ResolutionCalculator;
use TiVampyre\Video\FileTranscoder\ResolutionFinder;


class VideoConfigurator
{
	static function setup(Application $application)
	{
		// Video Transcoder Resolution Finder
                $resolutionFinder = function ($app) {
			return new ResolutionFinder(
				$app['process_builder']
			);
		};
                $application->offsetSet('file_transcoder_resolution_finder', $resolutionFinder);

		// Video Transcoder Aspect Ratio Finder
                $aspectRatioFinder = function ($app) {
			return new AspectRatioFinder(
				$app['process_builder']
			);
		};
                $application->offsetSet('file_transcoder_aspect_ratio_finder', $aspectRatioFinder);

		// Video Transcoder Resolution Calculator
                $resolutionCalculator = function ($app) {
			return new ResolutionCalculator(
				$app['file_transcoder_resolution_finder'],
				$app['file_transcoder_aspect_ratio_finder']
			);
		};
                $application->offsetSet('file_transcoder_resolution_calculator', $resolutionCalculator);

		// Video Transcoder Autocrop Finder
                $autocropFinder = function ($app) {
			return new AutocropFinder(
				$app['process_builder']
			);
		};
                $application->offsetSet('file_transcoder_autocrop_finder', $autocropFinder);

		// Video Transcoder
                $transcoder = function ($app) {
		    return new FileTranscoder(
                        $app['process_builder'],
                        $app['file_transcoder_resolution_calculator'],
		        $app['file_transcoder_autocrop_finder']
		    );
		};
                $application->offsetSet('file_transcoder', $transcoder);

		// Video Chapter Generator
                $chapterGenerator = function ($app) {
		    return new ChapterGenerator(
		        $app['process_builder'],
				new EdlParser(),
				new CommercialParser(),
				$app['comskip_path']
		    );
		};
                $application->offsetSet('chapter_generator', $chapterGenerator);

		// Video Cleaner
                $cleaner = function ($app) {
		    return new Cleaner(
		        $app['process_builder'],
		        $app['monolog']
		    );
		};
                $application->offsetSet('video_cleaner', $cleaner);

		// Video Labeler
                $labeler = function ($app) {
		    return new Labeler(
		        $app['process_builder'],
		        $app['tivampyre_working_directory'],
		        $app['monolog']
		    );
		};
                $application->offsetSet('video_labeler', $labeler);
	}
}

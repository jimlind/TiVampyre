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
		$application['file_transcoder_resolution_finder'] = function ($app) {
			return new ResolutionFinder(
				$app['process_builder']
			);
		};

		// Video Transcoder Aspect Ratio Finder
		$application['file_transcoder_aspect_ratio_finder'] = function ($app) {
			return new AspectRatioFinder(
				$app['process_builder']
			);
		};

		// Video Transcoder Resolution Calculator
		$application['file_transcoder_resolution_calculator'] = function ($app) {
			return new ResolutionCalculator(
				$app['file_transcoder_resolution_finder'],
				$app['file_transcoder_aspect_ratio_finder']
			);
		};

		// Video Transcoder Autocrop Finder
		$application['file_transcoder_autocrop_finder'] = function ($app) {
			return new AutocropFinder(
				$app['process_builder']
			);
		};

		// Video Transcoder
		$application['file_transcoder'] = function ($app) {
		    return new FileTranscoder(
				$app['process_builder'],
				$app['file_transcoder_resolution_calculator'],
		        $app['file_transcoder_autocrop_finder'],
		        $app['monolog']
		    );
		};

		// Video Chapter Generator
		$application['chapter_generator'] = function ($app) {
		    return new ChapterGenerator(
		        $app['process_builder'],
				new EdlParser(),
				new CommercialParser(),
				$app['comskip_path']
		    );
		};

		// Video Cleaner
		$application['video_cleaner'] = function ($app) {
		    return new Cleaner(
		        $app['process_builder'],
		        $app['monolog']
		    );
		};

		// Video Labeler
		$application['video_labeler'] = function ($app) {
		    return new Labeler(
		        $app['process_builder'],
		        $app['tivampyre_working_directory'],
		        $app['monolog']
		    );
		};
	}
}
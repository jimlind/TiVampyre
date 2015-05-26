<?hh

namespace Application;

use Silex\Application;
use TiVampyre\Video\Cleaner;
use TiVampyre\Video\ComskipRunner;
use TiVampyre\Video\Labeler;
use TiVampyre\Video\FileTranscoder;

class VideoConfig
{
	static function setup(Application $application)
	{
		// Video Transcoder
		$application['file_transcoder'] = function ($app) {
		    return new FileTranscoder(
		        $app['process_builder'],
		        $app['monolog']
		    );
		};

		// Video ComSkip
		$application['comskip_runner'] = function ($app) {
		    return new ComskipRunner(
		        $app['comskip_path'],
		        $app['process_builder'],
		        $app['monolog']
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

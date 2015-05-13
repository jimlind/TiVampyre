<?hh

namespace Application;

use TiVampyre\Video\Transcode;
use Silex\Application;

class VideoConfig
{
	static function setup(Application $application)
	{
		// Video Transcoder
		$application['video_transcoder'] = function ($app) {
		    return new TiVampyre\Video\Transcode(
		        $app['process_builder'],
		        $app['monolog']
		    );
		};

		// Video ComSkip
		$application['comskip'] = function ($app) {
		    return new TiVampyre\Video\Comskip(
		        $app['comskip_path'],
		        $app['process_builder'],
		        $app['monolog']
		    );
		};

		// Video Cleaner
		$application['video_cleaner'] = function ($app) {
		    return new TiVampyre\Video\Clean(
		        $app['process_builder'],
		        $app['monolog']
		    );
		};

		// Video Labeler
		$application['video_labeler'] = function ($app) {
		    return new TiVampyre\Video\Label(
		        $app['process_builder'],
		        $app['tivampyre_working_directory'],
		        $app['monolog']
		    );
		};
	}
}

<?hh

namespace Application;

use JimLind\TiVo\Decode;
use JimLind\TiVo\Download;
use JimLind\TiVo\Location;
use JimLind\TiVo\NowPlaying;
use Silex\Application;

class TiVoConfig
{
	static function setup(Application $application)
	{
		// If IP isn't set, look it up.
		if (!isset($application['tivo_ip'])) {
		    $location = new Location(
				$application['process'],
				$application['monolog']
			);
		    $application['tivo_ip'] = $location->find();
		}

		// Manage the TiVo's connection to Now Playing.
		$application['tivo_now_playing'] = function ($app) {
		    return new NowPlaying(
		        $app['tivo_ip'],
		        $app['tivampyre_mak'],
		        $app['guzzle'],
		        $app['monolog']
		    );
		};

		// TiVo Downloader
		$application['tivo_downloader'] = function ($app) {
		    return new Download(
		        $app['tivampyre_mak'],
		        $app['guzzle'],
		        $app['monolog']
		    );
		};

		// TiVo Decoder
		$application['tivo_decoder'] = function ($app) {
		    return new Decode(
		        $app['tivampyre_mak'],
		        $app['process_builder'],
		        $app['monolog']
		    );
		};
	}
}

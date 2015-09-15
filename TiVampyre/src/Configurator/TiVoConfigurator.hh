<?hh

namespace Configurator;

use JimLind\TiVo\TiVoFinder;
use JimLind\TiVo\VideoDecoder;
use JimLind\TiVo\VideoDownloader;
use JimLind\TiVo\XmlDownloader;
use Silex\Application;

class TiVoConfigurator
{
	static function setup(Application $application)
	{
		// TiVo IP Finder
		if (!isset($application['tivo_ip'])) {
		    $finder = new TiVoFinder(
                        $application['process_builder']
                    );
		    $application['tivo_ip'] = $finder->find();
		}

		// TiVo XML Downloader
		$application['tivo_now_playing'] = function ($app) {
		    return new XmlDownloader(
		        $app['tivo_ip'],
		        $app['tivampyre_mak'],
		        $app['guzzle']
		    );
		};

		// TiVo Video Downloader
		$application['tivo_downloader'] = function ($app) {
		    return new VideoDownloader(
		        $app['tivampyre_mak'],
		        $app['guzzle']
		    );
		};

		// TiVo Video Decoder
		$application['tivo_decoder'] = function ($app) {
		    return new VideoDecoder(
		        $app['tivampyre_mak'],
		        $app['process_builder']
		    );
		};
	}
}
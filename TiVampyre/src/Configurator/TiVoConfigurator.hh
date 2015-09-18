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
		if (false === $application->offsetExists('tivo_ip')) {
                    $processBuilder = $application->offsetGet('process_builder');
		    $finder         = new TiVoFinder($processBuilder);

		    $application->offsetSet('tivo_ip', $finder->find());
		}

		// TiVo XML Downloader
		$tivoXmlDonloader = function ($app) {
		    return new XmlDownloader(
		        $app['tivo_ip'],
		        $app['tivampyre_mak'],
		        $app['guzzle']
		    );
		};
                $application->offsetSet('tivo_now_playing', $tivoXmlDonloader);

		// TiVo Video Downloader
		$tivoVideoDownloader = function ($app) {
		    return new VideoDownloader(
		        $app['tivampyre_mak'],
		        $app['guzzle']
		    );
		};
                $application->offsetSet('tivo_downloader', $tivoVideoDownloader);

		// TiVo Video Decoder
		$tivoVideoDecoder = function ($app) {
		    return new VideoDecoder(
		        $app['tivampyre_mak'],
		        $app['process_builder']
		    );
		};
                $application->offsetSet('tivo_decoder', $tivoVideoDecoder);
	}
}

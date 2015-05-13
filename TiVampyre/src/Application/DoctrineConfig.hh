<?hh

namespace Application;

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;

class DoctrineConfig
{
	static function setup(Application $application, string $directory)
	{
		// Register Doctrine Service
		$application->register(new DoctrineServiceProvider(), array(
		    'db.options' => array(
		        'driver' => 'pdo_sqlite',
		        'path'   => $directory . '/../db/tivampyre.db',
		    ),
		));

		// Register Doctrine ORM Service
		$application->register(new DoctrineOrmServiceProvider, array(
		    'orm.proxies_dir' => $directory . '/../cache/doctrine/proxies',
		    'orm.em.options'  => array(
		        'mappings' => array(
		            array(
		                'type'      => 'annotation',
		                'namespace' => 'TiVampyre\Entity',
		                'path'      => $directory . '/TiVampyre/Entity',
		            ),
		        ),
		    ),
		));
	}
}

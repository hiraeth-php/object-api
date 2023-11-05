<?php

namespace Hiraeth\Api\Object;

use Hiraeth\Api;
use Hiraeth\Doctrine\AbstractRepository;
use Json\Normalizer;
use Hiraeth\Doctrine\ManagerRegistry;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 *
 */
class GetCollections extends AbstractAction
{
	/**
	 * @return ResponseInterface|Normalizer
	 */
	public function __invoke(ManagerRegistry $managers, Api\Utility\Linker $linker): object
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get these items'
			]) ?: NULL);
		}

		$data       = array();
		$manager    = $managers->getManager();
		$meta_datas = $manager->getMetadataFactory()->getAllMetadata();

		foreach ($meta_datas as $meta_data) {
			$class        = $meta_data->getName();
			$repository   = $manager->getRepository($class);

			if (!$repository instanceof AbstractRepository) {
				continue;
			}

			if ($this->auth->can('manage', $repository)) {
				$data[]       = [
					'$pool' => $linker->link('/objects/{repository:r}/', [
							'repository' => $repository
						]
					),
					'meta' => [
						'total' => $repository->queryCount([]),
						'type'  => sprintf('%s[]', $class),
					]
				];
			}
		}

		return Normalizer::prepare([
			'data' => $data,
			'meta' => [
				'total' => count($data)
			]
		]);
	}
}

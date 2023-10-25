<?php

namespace API\Object;

use API;
use Json\Normalizer;
use Hiraeth\Doctrine\ManagerRegistry;

/**
 *
 */
class GetCollections extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(ManagerRegistry $managers, API\Utility\Linker $linker)
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get these items'
			]));
		}

		$data       = array();
		$manager    = $managers->getManager();
		$meta_datas = $manager->getMetadataFactory()->getAllMetadata();

		foreach ($meta_datas as $meta_data) {
			$class        = $meta_data->getName();
			$repository   = $manager->getRepository($class);

			if ($this->auth->can('get', $repository)) {
				$data[]       = [
					'$pool' => $linker->link('/objects/{repository:r}/', [
							'repository' => $repository
						]
					),
					'$meta' => [
						'total' => $repository->queryCount([]),
						'type'  => sprintf('%s[]', $class),
					]
				];
			}
		}

		return Normalizer::prepare([
			'$data' => $data,
			'$meta' => [
				'total' => count($data)
			]
		]);
	}
}

<?php

namespace Hiraeth\Api\Object;

use Hiraeth\Api;
use Json\Normalizer;
use Hiraeth\Doctrine\ManagerRegistry;
use Hiraeth\Doctrine\AbstractRepository;
use Doctrine\ORM\Query\QueryException;

/**
 *
 */
class GetEntities extends AbstractAction
{
	/**
	 * @var Hiraeth\Api\Utility\Linker
	 */
	protected $linker;

	/**
	 * @var ManagerRegistry
	 */
	protected $managers;

	/**
	 *
	 */
	public function __construct(ManagerRegistry $managers, Api\Utility\Linker $linker)
	{
		$this->managers = $managers;
		$this->linker   = $linker;
	}

	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository, $id = NULL)
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get these items'
			]));
		}

		if (empty($repository)) {
			return $this->response(404, json_encode([
				'error' => 'The requested pool does not exist'
			]));
		}

		if (!$this->auth->can('get', $repository)) {
			return $this->response(403, json_encode([
				'error' => 'You do not have the required authorization to get these items'
			]));
		}

		try {
			$total  = 0;
			$page   = $this->get('p', 1);
			$limit  = $this->get('l', 25);
			$order  = $this->get('o', []);
			$filter = $this->get('f', []);
			$result = $repository->findBy($filter, $order, $limit, ($page - 1) * $limit, $total);

		} catch (\Exception $e) {
			switch(get_class($e)) {
				case QueryException::class:
					$message = trim(array_slice(explode(':', $e->getMessage()), -1)[0]);
					break;
				default:
					$message = $e->getMessage();
					break;

			}

			return $this->response(400, new Api\Json\ResultError($this->get(), $message));
		}

		$class     = $repository->getClassName();
		$manager   = $this->managers->getManagerForClass($class);
		$meta_data = $manager->getClassMetaData($class);
		$fields    = array();

		foreach ($meta_data->getFieldNames() as $field) {
			$fields[] = [
				'name' => $field,
				'type' => $meta_data->getTypeOfField($field)
			];
		}

		foreach ($meta_data->getAssociationNames() as $field) {
			$mapping  = $meta_data->getAssociationMapping($field);
			$fields[] = [
				'name' => $field,
				'type' => $mapping['type'] & $meta_data::TO_MANY
					? sprintf('%s[]', $mapping['targetEntity'])
					: $mapping['targetEntity'],
			];
		}

		return Normalizer::prepare([
			'$pool' => $this->linker->link('/objects/'),
			'$data' => array_map(
				function($entity) {
					return Api\Json\Entity::prepare($entity, FALSE);
				},
				$result->getValues()
			),
			'$meta' => [
				'total'    => $total,
				'limit'    => $limit,
				'page'     => $page,
				'type'     => $class,
				'identity' => $meta_data->getIdentifierFieldNames(),
				'fields'   => $fields,
			]
		]);
	}
}

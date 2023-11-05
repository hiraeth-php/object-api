<?php

namespace Hiraeth\Api\Object;

use Hiraeth\Api;
use Json\Normalizer;
use Hiraeth\Doctrine\AbstractEntity;
use Hiraeth\Doctrine\AbstractRepository;
use Hiraeth\Doctrine\ManagerRegistry;
use Doctrine\ORM\Query\QueryException;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class GetEntities extends AbstractAction
{
	/**
	 * @var Api\Utility\Linker
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
	 * @param ?AbstractRepository<AbstractEntity> $repository
	 * @return ResponseInterface|Normalizer
	 */
	public function __invoke(?AbstractRepository $repository): object
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get these items'
			]) ?: NULL);
		}

		if (empty($repository)) {
			return $this->response(404, json_encode([
				'error' => 'The requested pool does not exist'
			]) ?: NULL);
		}

		if (!$this->auth->can('manage', $repository)) {
			return $this->response(403, json_encode([
				'error' => 'You do not have the required authorization to get these items'
			]) ?: NULL);
		}

		try {
			$total  = 0;
			$page   = $this->get('p', 1);
			$limit  = $this->get('l', 25);
			$order  = $this->get('o', []);
			$filter = $this->get('f', []);
			$result = $repository->findBy($filter, $order, $limit, ($page - 1) * $limit, $total);

		} catch (\Exception $e) {
			$message = $e->getMessage();

			switch(get_class($e)) {
				case QueryException::class:
					$message = trim(array_slice(explode(':', $message), -1)[0]);
					break;
			}

			return $this->response(400, new Api\Json\ResultError($this->get(), $message));
		}

		$class     = $repository->getClassName();
		$manager   = $this->managers->getManagerForClass($class ?: NULL);
		$meta_data = $manager->getClassMetaData($class ?: NULL);
		$fields    = array();

		foreach ($meta_data->getFieldNames() as $field) {
			$fields[] = [
				'name' => $field,
				'type' => $meta_data->getTypeOfField($field)
			];
		}

		foreach ($meta_data->getAssociationNames() as $field) {
			$fields[] = [
				'name' => $field,
				'type' => $meta_data->isCollectionValuedAssociation($field)
					? sprintf('%s[]', $meta_data->getAssociationTargetClass($field))
					: $meta_data->getAssociationTargetClass($field),
			];
		}

		return Normalizer::prepare([
			'$pool' => $this->linker->link('/objects/'),
			'data' => array_map(
				function($entity) {
					return Api\Json\Entity::prepare($entity, FALSE);
				},
				$result
			),
			'meta' => [
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

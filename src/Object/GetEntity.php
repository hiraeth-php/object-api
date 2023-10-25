<?php

namespace Hiraeth\Api\Object;

use Hireath\Api;
use Hiraeth\Doctrine\AbstractRepository;
use Doctrine\ORM\Query\QueryException;

/**
 *
 */
class GetEntity extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository, $id)
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get an item'
			]));
		}

		if (empty($repository)) {
			return $this->response(404, json_encode([
				'error' => 'The requested pool does not exist'
			]));
		}

		try {
			$record = $repository->find($this->getIdentity($id));

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

		if (!$record) {
			return $this->response(404, json_encode([
				'error' => 'The requested item does not exist'
			]));
		}

		if (!$this->auth->can('get', $record)) {
			return $this->response(403, json_encode([
				'error' => 'You do not have the required authorization to get this item'
			]));
		}

		return Api\Json\Entity::prepare($record, FALSE);
	}
}

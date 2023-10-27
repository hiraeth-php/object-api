<?php

namespace Hiraeth\Api\Object;

use Hiraeth\Api;
use Hiraeth\Api\Utility;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class DeleteEntity extends AbstractAction
{
	/**
	 * @var Utility\Identity
	 */
	protected $identity;


	/**
	 *
	 */
	public function __construct(Utility\Identity $identity)
	{
		$this->identity = $identity;
	}


	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository, $id)
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

		try {
			if (!$record = $repository->find($this->identity->parse($id))) {
				return $this->response(404, json_encode([
					'error' => 'The requested item does not exist'
				]));
			}

		} catch (\Exception $e) {
			$message = $e->getMessage();

			switch(get_class($e)) {
				case QueryException::class:
					$message = trim(array_slice(explode(':', $message), -1)[0]);
					break;
			}

			return $this->response(400, new Api\Json\ResultError($this->get(), $message));
		}

		if (!$this->auth->can('remove', $record)) {
			return $this->response(403, json_encode([
				'error' => 'You do not have the required authorization to remove this item'
			]));
		}

		$repository->remove($record, TRUE);

		return Api\Json\Entity::prepare($record, FALSE);
	}

}

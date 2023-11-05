<?php

namespace Hiraeth\Api\Object;

use Checkpoint;
use Hiraeth\Api;
use Hiraeth\Api\Utility;
use Hiraeth\Doctrine\AbstractRepository;
use Psr\Http\Message\ResponseInterface;
use Doctrine\ORM\Query\QueryException;
use Json\Normalizer;

/**
 *
 */
class PatchEntity extends AbstractAction
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
	 * @param ?AbstractRepository<object> $repository
	 * @return ResponseInterface|Normalizer
	 */
	public function __invoke(?AbstractRepository $repository, string $id): object
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get an item'
			]) ?: NULL);
		}

		if (empty($repository)) {
			return $this->response(404, json_encode([
				'error' => 'The requested pool does not exist'
			]) ?: NULL);
		}

		try {
			if (!$record = $repository->find($this->identity->parse($id))) {
				return $this->response(404, json_encode([
					'error' => 'The requested item does not exist'
				]) ?: NULL);
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

		if (!$this->auth->can('update', $record)) {
			return $this->response(403, json_encode([
				'error' => 'You do not have the required authorization to update this item'
			]) ?: NULL);
		}

		/**
		 * @var array<string, mixed>
		 */
		$data = $this->request->getParsedBody() ?: array();

		try {
			$repository->update($record, $data, FALSE);
			// TODO: Inspect
			$repository->store($record, TRUE);

		} catch (\Exception $e) {
			$messages = array();
			$message  = $e->getMessage();

			if ($e instanceof Checkpoint\ValidationException) {
				$messages = $e->getMessages();
			}

			return $this->response(409, new Api\Json\ResultError($data, $message, $messages));
		}

		return Api\Json\Entity::prepare($record, FALSE);
	}

}

<?php

namespace Hiraeth\Api\Object;

use Checkpoint;
use Hiraeth\Api;
use Hiraeth\Api\Utility;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class PostEntity extends AbstractAction
{
	/**
	 * @var Utility\Identity
	 */
	protected $identity;

	/**
	 * @var Utility\Linker
	 */
	protected $linker;

	/**
	 *
	 */
	public function __construct(Utility\Identity $identity, Utility\Linker $linker)
	{
		$this->identity = $identity;
		$this->linker   = $linker;
	}


	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository)
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

		if (!$this->auth->can('create', $repository)) {
			return $this->response(403, json_encode([
				'error' => 'You do not have the required authorization to create this item'
			]));
		}

		try {
			$data   = $this->request->getParsedBody();
			$record = $repository->create($data, FALSE);

			//TODO: Inspect
			$repository->store($record, TRUE);

		} catch (\Exception $e) {
			$messages = array();
			$message  = $e->getMessage();

			if ($e instanceof Checkpoint\ValidationException) {
				$messages = $e->getMessages();
			}

			return $this->response(409, new Api\Json\ResultError($data, $message, $messages));
		}

		return $this->response(201, NULL, [
			'Location' => $this->linker->link('/objects/{repository:r}/{id}', [
				'id'         => $this->identity->build($record),
				'repository' => $repository
			])
		]);
	}

}

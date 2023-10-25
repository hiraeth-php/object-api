<?php

namespace API\Object;

use Checkpoint;
use API\ErrorResult;
use API\AbstractAction;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class PostEntity extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository)
	{
		$data = $this->request->getParsedBody();

		if (empty($repository)) {
			return $this->response(404);
		}

		try {
			$record = $repository->getRepository()->create($data, FALSE);

			$repository->inspect($data, $this->request);
			$repository->getRepository()->store($record, TRUE);

		} catch (Checkpoint\ValidationException $error) {
			$error = new ErrorResult(
				$data, $error->getMessage(), $error->getMessages()
			);

			return $this->response(409, json_encode($error));
		}

		return $this->response(201, NULL, [
			'Location' => ($this->urlGenerator)(
				'/api/v1/{repository:r}/{id}',
				['repository' => $repository->getRepository()],
				$record
			)
		]);
	}

}

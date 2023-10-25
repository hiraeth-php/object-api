<?php

namespace API\Object;

use Checkpoint;
use API\ErrorResult;
use API\AbstractAction;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class PatchEntity extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository, $id)
	{
		$data = $this->request->getParsedBody();

		if (empty($repository)) {
			return $this->response(404);
		}

		if (!$record = $repository->getRepository()->findOneById($id)) {
			return $this->response(404);
		}

		try {
			$repository->getRepository()->update($record, $data, FALSE);
			$repository->inspect($data, $this->request);
			$repository->getRepository()->store($record, TRUE);

		} catch (Checkpoint\ValidationException $error) {
			$error = new ErrorResult(
				$data, $error->getMessage(), $error->getMessages()
			);

			return $this->response(409, json_encode($error));
		}

		return $record;
	}

}

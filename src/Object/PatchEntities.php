<?php

namespace Hiraeth\Api\Object;

use Checkpoint;
use Hiraeth\Api\Result;
use Hiraeth\Api\ErrorResult;
use Hiraeth\Api\AbstractAction;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class PatchEntities extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository)
	{
		$errors  = [];
		$records = [];
		$data    = $this->request->getParsedBody();

		if (empty($repository)) {
			return $this->response(404);
		}

		if (empty($data)) {
			return $this->response(400, json_encode([
				'error' => 'You must provide a data to update records'
			]));
		}

		foreach($data as $record_id => $record_data) {
			$record = $repository->getRepository()->findOneById($record_id);

			if (!$record) {
				continue;
			}

			try {
				$repository->getRepository()->update($record, $record_data, FALSE);
				$repository->inspect($record_data, $this->request);

				$records[] = $record;

			} catch (Checkpoint\ValidationException $error) {
				$errors[] = new ErrorResult(
					$record_data, $error->getMessage(), $error->getMessages()
				);

			}
		}

		if (!empty($errors)) {
			return $this->response(409, json_encode($errors));
		}

		foreach($records as $record) {
			$repository->getRepository()->store($record, TRUE);
		}

		return new Result($records, 1, count($records), count($records));
	}

}

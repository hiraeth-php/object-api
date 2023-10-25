<?php

namespace API\Object;

use API\AbstractAction;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class DeleteEntities extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository)
	{
		$ids = $this->get('id', []);

		if (empty($repository)) {
			return $this->response(404);
		}

		if (empty($ids)) {
			return $this->response(400, json_encode([
				'error' => 'You must provide a list of IDs to be removed'
			]));
		}

		$records = $repository->findByUrlIds($ids)->getValues();

		if (count($records) != count($ids)) {
			$record_ids = array_map(function($record) {
				return $record->getId();
			}, $records);

			return $this->response(404, json_encode(
				array_values(array_diff($ids, $record_ids))
			));
		}

		foreach($records as $record) {
			$repository->getRepository()->remove($record, TRUE);
		}

		return $this->response(204);
	}

}

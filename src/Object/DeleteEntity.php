<?php

namespace Hiraeth\Api\Object;

use Hiraeth\Api\AbstractAction;
use Hiraeth\Doctrine\AbstractRepository;

/**
 *
 */
class DeleteEntity extends AbstractAction
{
	/**
	 *
	 */
	public function __invoke(?AbstractRepository $repository, $id)
	{
		if (empty($repository)) {
			return $this->response(404);
		}

		if (!$record = $repository->findOneByApiId($id)) {
			return $this->response(404);
		}

		$repository->getRepository()->remove($record, TRUE);

		return $this->response(204);
	}

}

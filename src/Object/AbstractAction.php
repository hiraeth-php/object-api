<?php

namespace Hiraeth\Api\Object;

use Auth;
use Auth\Manager;
use Hiraeth\Actions;

/**
 *
 */
class AbstractAction extends Actions\AbstractAction implements Auth\ManagedInterface
{
	/**
	 * @var Auth\Manager
	 */
	protected $auth;


	/**
	 *
	 */
	public function getIdentity($id)
	{
		return (array) json_decode(base64_decode($id), TRUE);
	}


	/**
	 *
	 */
	public function setAuthManager(Manager $manager): object
	{
		$this->auth = $manager;

		return $this;
	}
}

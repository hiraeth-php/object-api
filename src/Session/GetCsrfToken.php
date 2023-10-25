<?php

namespace Hiraeth\Api\Session;

use Auth;
use Hiraeth\Session;
use Hiraeth\Actions;

/**
 *
 */
class GetCsrfToken extends Actions\AbstractAction
{
	/**
	 * @var Auth\Manager
	 */
	protected $auth;

	/**
	 * @var Session\Manager
	 */
	protected $session = NULL;


	/**
	 * Inject dependencies
	 */
	public function __construct(Auth\Manager $auth, Session\Manager $session)
	{
		$this->auth    = $auth;
		$this->session = $session;
	}


	/**
	 * If user is logged in, give them a CSRF token.
	 */
	public function __invoke()
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get a CSRF token'
			]));
		}

		$token = $this->session->getCsrfToken()->getValue();

		return $this->response(200, sprintf('"%s"', $token), ['Content-Type' => 'application/json']);
	}

}

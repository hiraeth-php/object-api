<?php

namespace Hiraeth\Api\Session;

use Auth;
use Hiraeth\Session;
use Hiraeth\Actions;
use Psr\Http\Message\ResponseInterface;

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
	protected $session;


	/**
	 * Inject dependencies
	 */
	public function __construct(Auth\Manager $auth, Session\Manager $session)
	{
		$this->auth    = $auth;
		$this->session = $session;
	}


	/**
	 *
	 */
	public function __invoke(): ResponseInterface
	{
		if (!$this->auth->is('user')) {
			return $this->response(401, json_encode([
				'error' => 'You must be authorized to get a CSRF token'
			]) ?: NULL);
		}

		$token = $this->session->getCsrfToken()->getValue();

		return $this->response(200, sprintf('"%s"', $token), ['Content-Type' => 'application/json']);
	}

}

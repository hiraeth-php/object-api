<?php

namespace Hiraeth\Api\Utility;

use Hiraeth\Application;
use Hiraeth\Routing\Resolver;
use Hiraeth\Routing\UrlGenerator;

/**
 *
 */
class Linker
{
	/**
	 * @var string|null
	 */
	static private $prefix = NULL;

	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @var Resolver
	 */
	protected $resolver;

	/**
	 * @var UrlGenerator
	 */
	protected $urlGenerator;

	/**
	 *
	 */
	public function __construct(Application $app, Resolver $resolver, UrlGenerator $url_generator)
	{
		$this->app          = $app;
		$this->resolver     = $resolver;
		$this->urlGenerator = $url_generator;

		if (!self::$prefix) {
			self::$prefix = rtrim($this->app->getEnvironment('API_PREFIX', '/api'), '/');
		}
	}


	/**
	 * @param array<string, mixed> $params
	 */
	public function link(string $path, array $params = array()): string
	{
		$uri  = $this->resolver->getRequest()->getUri();
		$path = call_user_func(
			$this->urlGenerator,
			sprintf('%s/%s', self::$prefix, ltrim($path, '/')),
			$params
		);

		return $uri->withPath($path)->withQuery('')
		;
	}
}

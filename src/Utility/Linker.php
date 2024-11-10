<?php

namespace Hiraeth\Api\Utility;

use Hiraeth\Application;
use Hiraeth\Routing\Resolver;
use Hiraeth\Http\UrlGenerator;

/**
 *
 */
class Linker
{
	/**
	 * @var string|null
	 */
	static private $host = NULL;

	/**
	 * @var array
	 */
	static private $paths = array();

	/**
	 * @var string|null
	 */
	static private $port = NULL;

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
		$request            = $this->resolver->getRequest();

		if (!self::$host) {
			$x_host = $request->getHeaderLine('API-Host') ?: $request->getHeaderLine('X-Forwarded-Host');

			if ($x_host) {
				self::$host = parse_url($x_host, PHP_URL_HOST) ?: $x_host;
				self::$port = parse_url($x_host, PHP_URL_PORT);
			} else {
				self::$host = $request->getUri()->getHost();
				self::$port = $request->getUri()->getPort();
			}
		}

		if (!self::$prefix) {
			self::$prefix = $this->app->getConfig('packages/object-api', 'prefix', '/api');
		}

		self::$paths = $this->app->getConfig('app', 'api.paths', [])[self::$host] ?? [];
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

		foreach (self::$paths as $from_path => $to_path) {
			if (strpos($path, $from_path) === 0) {
				$path = sprintf(
					'%s/%s',
					rtrim($to_path, '/'),
					ltrim(substr($path, strlen($from_path)), '/')
				);
				break;
			}
		}


		return $uri
			->withHost(self::$host)
			->withPort(self::$port)
			->withPath($path)
			->withQuery('')
		;
	}
}

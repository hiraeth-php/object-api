<?php

namespace Hiraeth\Api\Json;

use Json\Normalizer;;

/**
 *
 */
class ResultError extends Normalizer
{
	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @var mixed[]
	 */
	protected $messages = array();

	/**
	 * @var mixed[]
	 */
	protected $query = array();

	/**
	 * @param array<mixed> $query
	 * @param array<mixed> $messages
	 */
	public function __construct(array $query, string $error, array $messages = array())
	{
		$this->query     = $query;
		$this->error     = $error;
		$this->messages  = $messages;
	}


	/**
	 *
	 */
	public function __toString()
	{
		return json_encode($this) ?: 'Unknown Error';
	}


	/**
	 *
	 */
	public function jsonSerialize(): Normalizer
	{
		$result = [
			//
			// Prepare our internal array with our own nesting level (if we're root level, treat the
			// data as root level).
			//

			'query' => $this('query'),
			'error' => $this('error')
		];

		if ($this('messages')) {
			$result['messages'] = $this('messages');
		}

		return Normalizer::prepare($result);
	}
}

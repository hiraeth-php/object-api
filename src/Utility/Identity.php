<?php

namespace Hiraeth\Api\Utility;

use Doctrine\Common\Proxy\Proxy;
use Hiraeth\Doctrine\ManagerRegistry;

/**
 *
 */
class Identity
{
	/**
	 * @var ManagerRegistry
	 */
	protected $managers;


	/**
	 *
	 */
	public function __construct(ManagerRegistry $managers)
	{
		$this->managers = $managers;
	}


	/**
	 *
	 */
	public function build(object $entity)
	{
		$class     = get_class($entity);
		$manager   = $this->managers->getManagerForClass($class);
		$meta_data = $manager->getClassMetadata($class);
		$identity  = $meta_data->getIdentifierFieldNames();

		return base64_encode(json_encode(array_combine($identity, array_map(
			function ($field) use ($meta_data, $entity) {
				$value = $meta_data->getFieldValue($entity, $field);

				if ($value instanceof Proxy) {
					$value->__load();
				}

				if (is_object($value)) {
					return $this->build($value);
				}

				return $value;
			},
			$identity
		))));
	}


	/**
	 *
	 */
	public function parse(string $id)
	{
		return (array) json_decode(base64_decode($id), TRUE);
	}
}

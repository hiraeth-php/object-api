<?php

namespace Hiraeth\Api\Json;

use Hiraeth\Api;
use Json\Normalizer;
use Hiraeth\Doctrine\ManagerRegistry;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Proxy\Proxy;

/**
 *
 */
class Entity extends Normalizer
{
	/**
	 * @var Hiraeth\Api\Utility\Linker
	 */
	protected $linker;

	/**
	 * @var ManagerRegistry
	 */
	protected $managers;


	/**
	 *
	 */
	public function __construct(ManagerRegistry $managers, Api\Utility\Linker $linker)
	{
		$this->managers = $managers;
		$this->linker   = $linker;
	}


	/**
	 *
	 */
	public function jsonSerialize(): Normalizer
	{
		$data         = array();
		$class        = get_class($this('data'));
		$manager      = $this->managers->getManagerForClass($class);
		$meta_data    = $manager->getClassMetadata($class);
		$identity     = $meta_data->getIdentifierFieldNames();
		$repository   = $manager->getRepository($class);
		$url_identity = base64_encode(json_encode(array_combine($identity, array_map(
			function ($field) {
				return $this->$field;
			},
			$identity
		))));

		if ($this('data') instanceof Proxy) {
			$this('data')->__load();
		}

		$data['$pool'] = $this->linker->link('/objects/{repository:r}/', [
			'repository' => $repository
		]);

		$data['$item'] = $this->linker->link('/objects/{repository:r}/{id}', [
			'id'         => $url_identity,
			'repository' => $repository
		]);

		if ($this('nested')) {
			$fields = array_unique(array_merge($identity, $meta_data->getFieldNames()));
		} else {
			$fields = array_unique(array_merge($meta_data->getFieldNames(), $meta_data->getAssociationNames()));
		}

		foreach ($fields as $field) {
			$data[$field] = $meta_data->getFieldValue($this('data'), $field);

			if ($data[$field] && $meta_data->hasAssociation($field)) {
				if ($data[$field] instanceof Collection) {
					$data[$field] = $data[$field]->map(function($entity) {
						return Api\Json\Entity::prepare($entity);
					})->getValues();

				} else {
					$data[$field] = Api\Json\Entity::prepare($data[$field]);
				}
			}
		}

		return Normalizer::prepare($data, $this('nested'));
	}
}

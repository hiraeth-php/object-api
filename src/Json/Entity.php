<?php

namespace Hiraeth\Api\Json;

use Hiraeth\Api;
use Hiraeth\Api\Utility;
use Hiraeth\Doctrine\ManagerRegistry;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Proxy\Proxy;
use Json\Normalizer;

/**
 *
 */
class Entity extends Normalizer
{
	/**
	 * @var Utility\Identity
	 */
	protected $identity;

	/**
	 * @var Utility\Linker
	 */
	protected $linker;

	/**
	 * @var ManagerRegistry
	 */
	protected $managers;


	/**
	 *
	 */
	public function __construct(ManagerRegistry $managers, Utility\Identity $identity, Utility\Linker $linker)
	{
		$this->managers = $managers;
		$this->identity = $identity;
		$this->linker   = $linker;
	}


	/**
	 *
	 */
	public function jsonSerialize(): Normalizer
	{
		$data         = array();
		$class        = get_class($this('data'));
		$manager      = $this->managers->getManagerForClass($class ?: NULL);
		$meta_data    = $manager->getClassMetadata($class ?: NULL);
		$repository   = $manager->getRepository($class ?: NULL);
		$url_identity = $this->identity->build($this('data'));

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
			$fields = array_unique(array_merge(
				$meta_data->getIdentifierFieldNames(),
				$meta_data->getFieldNames()
			));

		} else {
			$fields = array_unique(array_merge(
				$meta_data->getFieldNames(),
				$meta_data->getAssociationNames()
			));
		}

		foreach ($fields as $field) {
			$data[$field] = $meta_data
				->getReflectionClass()
				->getProperty($field)
				->getValue($this('data'))
			;

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

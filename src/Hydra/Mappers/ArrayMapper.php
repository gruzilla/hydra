<?php

namespace Hydra\Mappers;

use Hydra\Interfaces\MapperInterface,
    Hydra\Interfaces\EntityRepositoryInterface,
    Hydra\Interfaces\MetadataFactoryInterface,
    Hydra\Annotation\Map,
    Hydra\Annotation\Decoder,
    Hydra\Decoder\JsonDecoder,
    Hydra\Metadata\DefaultMetadataFactory;

class ArrayMapper implements MapperInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory = null)
    {
        $this->metadataFactory = $metadataFactory ?: new DefaultMetadataFactory();
    }

    /**
     * @inheritdoc
     */
    public function map(EntityRepositoryInterface $entityRepository, $className, $data)
    {

        $decoder = $this->getDecoder($className);
        $data = $decoder->decode($data);

        if (is_array($data)) {
            $return = array();
            foreach ($data as $singleData) {
                $entity = $entityRepository->fetchOrCreateEntity($className, $singleData);
                $this->mapEntity($entity, $singleData);
                $return[] = $entity;
            }

            return $return;
        }

        $entity = $entityRepository->fetchOrCreateEntity($className, $data);
        $this->mapEntity($entity, $data);
        return $entity;
    }

    protected function getDecoder($className)
    {
        $decoderClassName = $this->metadataFactory->getDecoderClassName($className);

        return new $decoderClassName();
    }

    protected function mapEntity($entity, $data)
    {
        $className = get_class($entity);

        $properties = $this->metadataFactory->getProperties($className);

        foreach ($properties as $property) {
            $source = $this->metadataFactory->getMappingSource($className, $property->getName());
            $property->setAccessible(true);
            $property->setValue(
                $entity,
                $this->evalueateArrayQuery($data, $source)
            );
            $property->setAccessible(!$property->isPrivate() && !$property->isProtected());
        }

        foreach ($data as $key => $value) {
            if (isset($properties[$key])) {
                $property = $properties[$key];
                $property->setAccessible(true);
                $property->setValue(
                    $entity,
                    $value
                );
                $property->setAccessible(!$property->isPrivate() && !$property->isProtected());
            }
        }

        // allow chaining
        return $this;
    }

    /**
     * allows to query php-arrays
     * valid queries are:
     *
     * Example          |   Result
     * 4                |   returns the 4th index of the array
     * key              |   returns the index named 'key'
     * key.subkey       |   returns the index named 'subkey' on the index
     *                  |   named 'key'. this structure is allowed to go deep
     * key.0            |   returns the index 0 of the array on the index 'key'
     * key.count()      |   returns the amount of children the index 'key' has
     *
     * @param array  $data  the array that should be searched
     * @param string $query the query
     *
     * @return mixed
     */
    public static function evalueateArrayQuery($data, $query)
    {
        // return direct hits
        if (isset($data[$query])) {
            return $data[$query];
        }

        $path = explode('.', $query);

        $ref = $data;
        while (count($path) > 0) {

            $index = array_shift($path);

            if ($index === 'count()') {
                $ref = count($ref);
                break;
            }

            if (is_array($ref) && isset($ref[$index])) {
                $ref = $ref[$index];
            } else if (is_object($ref) && isset($ref->$index)) {
                $ref = $ref->$index;
            }
        }

        return $ref;
    }
}
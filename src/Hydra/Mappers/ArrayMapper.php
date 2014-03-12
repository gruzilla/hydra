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
    protected $typeMapperCache = array();

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
        $sources = $this->metadataFactory->getMappingSources($className);
        $typeMappers = $this->metadataFactory->getMappingTypes($className);

        foreach ($sources as $propertyName => $source) {
            if (!array_key_exists($propertyName, $properties)) {
                continue;
            }

            $property = $properties[$propertyName];
            $value = $this->evalueateArrayQuery($data, $source);

            if (array_key_exists($propertyName, $typeMappers)) {
                $value = $this
                    ->getTypeMapperInstance($typeMappers[$propertyName])
                    ->map($property, $value);
            }

            $property->setAccessible(true);
            $property->setValue(
                $entity,
                $value
            );
            $property->setAccessible(!$property->isPrivate() && !$property->isProtected());
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $properties)) {
                $property = $properties[$key];

                if (array_key_exists($key, $typeMappers)) {
                    $value = $this
                        ->getTypeMapperInstance($typeMappers[$key])
                        ->map($property, $value);
                }

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

    protected function getTypeMapperInstance($typeMapper)
    {
        if (array_key_exists($typeMapper, $this->typeMapperCache)) {
            return $this->typeMapperCache[$typeMapper];
        }

        return $this->typeMapperCache[$typeMapper] = new $typeMapper;
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
        $found = false;
        while (count($path) > 0) {

            $index = array_shift($path);

            $oldref = $ref;

            if ($index === 'count()') {
                $ref = count($ref);
                $found = true;
                break;
            }

            if (is_array($ref) && isset($ref[$index])) {
                $ref = $ref[$index];
            } else if (is_object($ref) && isset($ref->$index)) {
                $ref = $ref->$index;
            }

            if ($ref === $oldref) {
                break;
            }

            if (count($path) === 0) {
                $found = true;
                break;
            }
        }

        return $found ? $ref : null;
    }
}
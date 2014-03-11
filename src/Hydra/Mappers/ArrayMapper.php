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
                $this->evalueateQuery($data, $source)
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

    protected function evalueateQuery($data, $query)
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
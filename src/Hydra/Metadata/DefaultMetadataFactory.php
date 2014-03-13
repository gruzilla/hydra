<?php

namespace Hydra\Metadata;

use Hydra\Interfaces\MetadataFactoryInterface,
    Hydra\Annotation\Map,
    Hydra\Annotation\Repository,
    Hydra\Annotation\Decoder;

use Doctrine\Common\Annotations\Reader,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Annotations\AnnotationReader;

class DefaultMetadataFactory implements MetadataFactoryInterface
{
    /**
     * @var Reader
     */
    protected $reader;
    protected $metadata = array();

    public function __construct(Reader $reader = null)
    {
        $this->reader = $reader ?: new AnnotationReader();

        // register our own namespace in the doctrine annotation-class-registry
        // not quite sure, why this is necessary
        AnnotationRegistry::registerAutoloadNamespace(
            'Hydra\\',
            array(dirname(dirname(__DIR__)))
        );
    }

    public function getProperties($className)
    {
        $this->loadMetadata($className);
        return $this->metadata[$className]['properties'];
    }

    public function getDecoderClassName($className)
    {
        $this->loadMetadata($className);
        if (array_key_exists('decoder', $this->metadata[$className])) {
            return $this->metadata[$className]['decoder'];
        }

        throw new \RuntimeException('Could not find decoder for class ' . $className);
    }

    public function getMappingSources($className)
    {
        $this->loadMetadata($className);
        if (array_key_exists('sources', $this->metadata[$className])) {
            return $this->metadata[$className]['sources'];
        }
        return null;
    }

    public function getMappingSource($className, $propertyName)
    {
        $this->loadMetadata($className);
        if (array_key_exists($propertyName, $this->metadata[$className]['sources'])) {
            return $this->metadata[$className]['sources'][$propertyName];
        }
        return null;
    }

    public function getMappingTypes($className)
    {
        $this->loadMetadata($className);
        if (array_key_exists('types', $this->metadata[$className])) {
            return $this->metadata[$className]['types'];
        }
        return null;
    }

    public function getMappingType($className, $propertyName)
    {
        $this->loadMetadata($className);
        if (array_key_exists($propertyName, $this->metadata[$className]['types'])) {
            return $this->metadata[$className]['types'][$propertyName];
        }
        return null;
    }

    public function getRepositoryClassName($className)
    {
        $this->loadMetadata($className);
        if (array_key_exists('repository', $this->metadata[$className])) {
            return $this->metadata[$className]['repository'];
        }

        throw new \RuntimeException('Could not find repository for class ' . $className);
    }

    public function getMetadata($className)
    {
        $this->loadMetadata($className);
        return $this->metadata[$className];
    }

    protected function loadMetadata($className)
    {
        if (array_key_exists($className, $this->metadata)) {
            return $this;
        }

        $this->metadata[$className] = array(
            'decoder'       => 'Hydra\Decoder\JsonDecoder',
            'repository'    => null,
            'sources'       => array(),
            'properties'    => array()
        );

        $reflection = new \ReflectionClass($className);

        $sourceMap = array();
        $typeMap = array();

        $annotations = $this->reader->getClassAnnotations($reflection);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Decoder) {
                $this->metadata[$className]['decoder'] = $annotation->class;
            }
            if ($annotation instanceof Repository) {
                $this->metadata[$className]['repository'] = $annotation->class;
            }
        }

        foreach ($reflection->getProperties() as $property) {
            $annotations = $this->reader->getPropertyAnnotations($property);
            $propertyMap[$property->getName()] = $property;

            $typeMapper = null;
            $doctrineMapper = null;
            foreach ($annotations as $annotation) {

                if (get_class($annotation) === 'Doctrine\ORM\Mapping\Column') {
                    $doctrineMapper = 'Hydra\\Mappers\\Types\\' . ucfirst(strtolower($annotation->type));
                }

                if ($annotation instanceof Map) {
                    $sourceMap[$property->getName()] = $annotation->source;
                    $typeMapper = $annotation->typeMapper;
                }
            }

            if (null !== $typeMapper && class_exists($typeMapper)) {
                $typeMap[$property->getName()] = $typeMapper;
            } else if (null !== $doctrineMapper && class_exists($doctrineMapper)) {
                $typeMap[$property->getName()] = $doctrineMapper;
            }
        }

        $this->metadata[$className]['properties'] = $propertyMap;
        $this->metadata[$className]['sources'] = $sourceMap;
        $this->metadata[$className]['types'] = $typeMap;

        // allow chaining
        return $this;
    }

    protected function clearMetadata()
    {
        $this->metadata = array();
    }
}
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

    public function getMappingSource($className, $propertyName)
    {
        $this->loadMetadata($className);
        if (array_key_exists($propertyName, $this->metadata[$className]['sources'])) {
            return $this->metadata[$className]['sources'][$propertyName];
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
            'repository'    => 'Hydra\EntityRepository\DefaultEntityRepository',
            'sources'       => array(),
            'properties'    => array()
        );

        $reflection = new \ReflectionClass($className);

        $sourceMap = array();

        $annotations = $this->reader->getClassAnnotations($reflection);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Decoder) {
                $this->metadata[$className]['decoder'] = $annotation->getClass();
            }
            if ($annotation instanceof Repository) {
                $this->metadata[$className]['repository'] = $annotation->getClass();
            }
        }

        foreach ($reflection->getProperties() as $property) {
            $annotations = $this->reader->getPropertyAnnotations($property);
            $propertyMap[$property->getName()] = $property;

            foreach ($annotations as $annotation) {

                if (!($annotation instanceof Map)) {
                    continue;
                }

                $sourceMap[$property->getName()] = $annotation->getSource();
            }
        }

        $this->metadata[$className]['properties'] = $propertyMap;
        $this->metadata[$className]['sources'] = $sourceMap;

        // allow chaining
        return $this;
    }

    protected function clearMetadata()
    {
        $this->metadata = array();
    }
}
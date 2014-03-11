<?php

namespace Hydra\Mappers;

use Hydra\Interfaces\MapperInterface,
    Hydra\Annotation\Map,
    Hydra\Annotation\Decoder,
    Hydra\Decoder\JsonDecoder;

use Doctrine\Common\Annotations\Reader,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Annotations\AnnotationReader;

class ArrayMapper implements MapperInterface
{

    /**
     * @var Reader
     */
    protected $reader;
    protected $metadata;

    public function __construct(Reader $reader = null)
    {
        $this->reader = $reader ?: new AnnotationReader();
        $this->metadata = array();

        // register our own namespace in the doctrine annotation-class-registry
        // not quite sure, why this is necessary
        AnnotationRegistry::registerAutoloadNamespace(
            'Hydra\\',
            array(realpath(__DIR__.'/../../'))
        );
    }

    /**
     * @inheritdoc
     */
    public function map($className, $data)
    {

        $metadata = $this->getMetadata($className);

        $decoder = $this->getDecoder($metadata);

        $data = $decoder->decode($data);

        if (is_array($data)) {
            $return = array();
            foreach ($data as $entry) {
                $entity = new $className;
                $this->mapEntity($entity, $metadata, $entry);
                $return[] = $entity;
            }

            return $return;
        }

        $entity = new $className;
        $this->mapEntity($entity, $metadata, $entry);
        return $entity;
    }

    protected function getDecoder($metadata)
    {
        if (isset($metadata['decoder']) && class_exists($metadata['decoder'])) {
            return new $metadata['decoder'];
        }

        return new JsonDecoder();
    }

    protected function mapEntity($entity, $metadata, $data)
    {
        foreach ($metadata['properties'] as $property => $query) {
            $setter = 'set' . ucfirst($property);
            if (is_callable(array($entity, $setter))) {
                $value = $this->getValue($data, $query);
                call_user_func_array(array($entity, $setter), array($value));
            }
        }

        foreach ($data as $key => $value) {
            $key = join('', array_map('ucfirst', explode('_', $key)));
            $setter = 'set' . $key;
            if (is_callable(array($entity, $setter))) {
                call_user_func_array(array($entity, $setter), array($value));
            }
        }

        // allow chaining
        return $this;
    }

    protected function getMetadata($class)
    {
        if (array_key_exists($class, $this->metadata)) {
            return $this->metadata[$class];
        }

        $this->metadata[$class] = array();

        $reflection = new \ReflectionClass($class);

        $map = array();

        $annotations = $this->reader->getClassAnnotations($reflection);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Decoder) {
                $this->metadata[$class]['decoder'] = $annotation->getClass();
                break;
            }
        }

        foreach ($reflection->getProperties() as $property) {
            $annotations = $this->reader->getPropertyAnnotations($property);
            foreach ($annotations as $annotation) {

                if (!($annotation instanceof Map)) {
                    continue;
                }

                $map[$property->getName()] = $annotation->getSource();
            }
        }

        $this->metadata[$class]['properties'] = $map;

        return $this->metadata[$class];
    }

    protected function getValue($data, $query)
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
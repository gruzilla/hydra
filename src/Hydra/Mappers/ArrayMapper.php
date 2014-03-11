<?php

namespace Hydra\Mappers;

use Hydra\Interfaces\MapperInterface;

use JMS\Serializer\SerializerBuilder;

class ArrayMapper implements MapperInterface
{

    public function __construct(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer ?: SerializerBuilder::create()->build();
    }

    /**
     * @inheritdoc
     */
    public function map($className, $data)
    {
        $return = array();

        $data = json_decode($data, true);

        if (is_array($data)) {
            foreach ($data as $entry) {
                $entity = new $className;
                $this->mapEntity($entity, $entry);
                $return[] = $entity;
            }
        } else {
            $entity = new $className;
            $this->mapEntity($entity, $entry);
            $return = $entity;
        }

        return $return;
    }

    protected function mapEntity($entity, $data)
    {

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
}
<?php

namespace Hydra\Mappers\Types;

use Hydra\Interfaces\TypeMapperInterface;

class Datetime implements TypeMapperInterface
{
    public function map(\ReflectionProperty $property, $value)
    {
        try {
            return new \Datetime($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
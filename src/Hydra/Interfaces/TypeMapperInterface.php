<?php

namespace Hydra\Interfaces;

interface TypeMapperInterface
{
    public function map(\ReflectionProperty $property, $value);
}
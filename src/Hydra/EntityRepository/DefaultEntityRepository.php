<?php

namespace Hydra\EntityRepository;

use Hydra\Interfaces\EntityRepositoryInterface;

class DefaultEntityRepository implements EntityRepositoryInterface
{
    public function fetchOrCreateEntity($className, $data)
    {
        return new $className;
    }
}
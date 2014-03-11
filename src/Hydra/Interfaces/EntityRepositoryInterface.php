<?php

namespace Hydra\Interfaces;

interface EntityRepositoryInterface
{
    public function fetchOrCreateEntity($className, $data);
}
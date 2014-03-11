<?php

namespace Hydra\Interfaces;

interface MapperInterface
{
    public function map(EntityRepositoryInterface $entityRepository, $className, $data);
}
<?php

namespace Hydra\Interfaces;

interface RepositoryFactoryInterface
{
    public function getRepositoryForEntity($entityClassName);
    public function getRepository($repositoryClassName);
}
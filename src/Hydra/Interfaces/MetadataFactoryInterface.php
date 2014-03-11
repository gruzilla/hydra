<?php

namespace Hydra\Interfaces;

interface MetadataFactoryInterface
{
    public function getProperties($className);
    public function getDecoderClassName($className);
    public function getMappingSource($className, $propertyName);
    public function getRepositoryClassName($className);
    public function getMetadata($className);
}
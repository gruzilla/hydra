<?php

namespace Hydra\EntityRepository;

use Hydra\Interfaces\RepositoryFactoryInterface,
    Hydra\Interfaces\MetadataFactoryInterface,
    Hydra\Interfaces\EntityRepositoryInterface,
    Hydra\Metadata\DefaultMetadataFactory;

class DefaultRepositoryFactory implements RepositoryFactoryInterface
{

    protected $objectCache = array();
    protected $metadataFactory;
    protected $defaultRepository;

    public function __construct(MetadataFactoryInterface $metadataFactory = null, EntityRepositoryInterface $defaultRepository = null)
    {
        $this->metadataFactory = $metadataFactory ?: new DefaultMetadataFactory();
        $this->defaultRepository = $defaultRepository ?: new DefaultEntityRepository();
    }

    public function getRepositoryForEntity($entityClassName)
    {
        $repositoryClassName = $this->metadataFactory->getRepositoryClassName(
            $entityClassName
        );

        return $this->getRepository($repositoryClassName);
    }

    public function getRepository($repositoryClassName)
    {
        if (null === $repositoryClassName) {
            return $this->defaultRepository;
        }

        if (array_key_exists($repositoryClassName, $this->objectCache)) {
            return $this->objectCache[$repositoryClassName];
        }

        $this->objectCache[$repositoryClassName] = new $repositoryClassName;

        return $this->objectCache[$repositoryClassName];
    }

    public function clearObjectCache()
    {
        $this->objectCache = array();
    }
}
<?php

namespace Hydra\Common\Helper;

use Hydra\Hydra,
    Hydra\Jobs\GetJob,
    Hydra\Jobs\MappedJob,
    Hydra\Interfaces\MetadataFactoryInterface;

class RequestHelper
{
    public function __construct(Hydra $hydra, MetadataFactoryInterface $metadataFactory)
    {
        $this->hydra = $hydra;
        $this->metadataFactory = $metadataFactory;

        $this->hydra->load();
    }

    public function getLoadedServices()
    {
        return $this->hydra->getLoadedServices();
    }

    public function runSingleGetRequest($serviceName, $request, $mappedClass)
    {
        // run hydra
        $job = new GetJob($serviceName, $request);
        if (!empty($mappedClass)) {
            $job = MappedJob::createFrom($job, $mappedClass);

            $repositoryClass = $this->metadataFactory->getRepositoryClassName($mappedClass);
            $job->setEntityRepository(new $repositoryClass);
        }

        $this->hydra->add($job);
        $this->hydra->run();

        return $job->getResult();
    }
}
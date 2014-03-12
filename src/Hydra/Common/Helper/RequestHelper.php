<?php

namespace Hydra\Common\Helper;

use Hydra\Hydra,
    Hydra\Jobs\GetJob,
    Hydra\Jobs\MappedJob,
    Hydra\Interfaces\RepositoryFactoryInterface;

class RequestHelper
{
    public function __construct(Hydra $hydra, RepositoryFactoryInterface $repositoryFactory)
    {
        $this->hydra = $hydra;
        $this->repositoryFactory = $repositoryFactory;

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
            $job = MappedJob::createFrom($job, $mappedClass, $this->repositoryFactory);
        }

        $this->hydra->add($job);
        $this->hydra->run();

        return $job->getResult();
    }
}
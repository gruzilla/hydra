<?php

namespace Hydra\Workers;

use Hydra\Interfaces\WorkerInterface,
    Hydra\Interfaces\JobInterface,
    Hydra\Interfaces\ServiceProviderInterface,
    Hydra\ServiceProviders\DefaultServiceProvider;

class SerialWorker implements WorkerInterface
{
    protected $serviceProvider;

    public function __construct(ServiceProviderInterface $provider = null)
    {
        $this->serviceProvider = $provider ?: new DefaultServiceProvider();
    }

    public function run(JobInterface $job)
    {
        $service = $this->serviceProvider->createService(
            $job->getServiceName()
        );

        $result = $service->request(
            $job->getRequest(),
            $job->getMethod(),
            $job->getBody(),
            $job->getAdditionalHeaders()
        );

        $job->setResult(
            $result
        );
    }

    public function isRunning() {
        return false;
    }
}
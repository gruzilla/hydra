<?php

namespace Hydra;

use Hydra\Interfaces\WorkerInterface,
    Hydra\Interfaces\JobInterface,
    Hydra\Interfaces\ServiceProviderInterface,
    Hydra\Workers\SerialWorker,
    Hydra\ServiceProviders\DefaultServiceProvider,
    Hydra\Oauth\HydraTokenStorage;

class Hydra
{
    /**
     * @var WorkerInterface
     */
    protected $worker;

    /**
     * @var ServiceProviderInterface
     */
    protected $serviceProvider;

    protected $jobs = array();

    /**
     * creates a new hydra
     *
     * @param WorkerInterface          $worker          the worker to be used for the jobs
     * @param ServiceProviderInterface $serviceProvider the service provider to be used to load services
     */
    public function __construct(WorkerInterface $worker = null, ServiceProviderInterface $serviceProvider = null)
    {
        $this->serviceProvider = $serviceProvider ?: new DefaultServiceProvider();
        $this->worker = $worker;
        $this->worker = $worker ?: new SerialWorker($this->serviceProvider);
    }

    /**
     * load all (leave argument empty) services configured or load a specific
     * service
     *
     * @param string $serviceName which service to load
     *
     * @return self
     */
    public function load($serviceName = null)
    {
        if (null === $serviceName) {
            $this->serviceProvider->loadAllServices();
        } else {
            $this->serviceProvider->loadService($serviceName);
        }

        // allow chaining
        return $this;
    }

    /**
     * returns all services currently loaded in a hash, where every key is the
     * services name (ucfirst)
     *
     * @return array
     */
    public function getLoadedServices()
    {
        return $this->serviceProvider->getLoadedServices();
    }

    /**
     * add a job to the queue
     *
     * @param JobInterface $job [description]
     *
     * @return self
     */
    public function add(JobInterface $job)
    {
        $this->jobs[] = $job;

        // allow chaining
        return $this;
    }

    /**
     * run all jobs using the worker
     *
     * @return self
     */
    public function run()
    {
        foreach ($this->jobs as $job) {
            $this->worker->run($job);
        }

        while ($this->worker->isRunning()) {
            // waiting for worker to finish
        }

        // allow chaining
        return $this;
    }
}
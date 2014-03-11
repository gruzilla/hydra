<?php

namespace Hydra\Workers;

use Hydra\Interfaces\ServiceProviderInterface,
    Hydra\Interfaces\JobInterface,
    Hydra\Interfaces\MapperInterface,
    Hydra\Jobs\MappedJob,
    Hydra\Mappers\ArrayMapper;

class MappedSerialWorker extends SerialWorker
{
    protected $mapper;

    public function __construct(ServiceProviderInterface $provider = null, MapperInterface $mapper = null)
    {
        parent::__construct($provider);
        $this->mapper = $mapper ?: new ArrayMapper();
    }

    /**
     * runs the parent job and then maps the result
     *
     * @param JobInterface $job the job to execute
     *
     * @return self
     */
    public function run(JobInterface $job)
    {
        if (!($job instanceof MappedJob)) {
            throw new \InvalidArgumentException('MappedSerialWorker only accepts MappedJobs to run!');
        }

        parent::run($job);

        $job->setResult(
            $this->mapper->map(
                $job->getEntityRepository(),
                $job->getMappedClass(),
                $job->getResult()
            )
        );

        // allow chaining
        return $this;
    }
}
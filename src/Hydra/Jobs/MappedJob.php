<?php

namespace Hydra\Jobs;

use Hydra\Interfaces\JobInterface;

class MappedJob extends AbstractJob
{
    protected $mappedClass;

    /**
     * creates a basic job with service name and request
     *
     * @param string $serviceName ucfirst name of service to request from
     * @param string $request     the actual api-request
     * @param string $mappedClass FQCN of the entity to map the result to
     */
    public function __construct($serviceName, $request, $mappedClass)
    {
        parent::__construct($serviceName, $request);
        $this->setMappedClass($mappedClass);
    }

    /**
     * creates a mapped job from another (basically clones it)
     *
     * @param JobInterface $job         the job to clone
     * @param string       $mappedClass FQCN of the entity to map the result to
     *
     * @return self
     */
    public static function createFrom(JobInterface $job, $mappedClass)
    {
        $mappedJob = new MappedJob(
            $job->getServiceName(),
            $job->getRequest(),
            $mappedClass
        );

        $mappedJob->setMethod($job->getMethod());
        $mappedJob->setBody($job->getBody());

        return $mappedJob;
    }

    /**
     * Gets the value of mappedClass.
     *
     * @return mixed
     */
    public function getMappedClass()
    {
        return $this->mappedClass;
    }

    /**
     * Sets the value of mappedClass.
     *
     * @param mixed $mappedClass the mapped class
     *
     * @return self
     */
    public function setMappedClass($mappedClass)
    {
        $this->mappedClass = $mappedClass;

        return $this;
    }
}
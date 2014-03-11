<?php

namespace Hydra\Interfaces;

interface WorkerInterface
{

    /**
     * runs the given job
     *
     * @param JobInterface $job which job to add
     *
     * @return self
     */
    public function run(JobInterface $job);

    /**
     * returns if the current worker is still running
     *
     * @return boolean
     */
    public function isRunning();
}
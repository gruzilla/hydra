<?php

namespace Hydra\Interfaces;

interface ServiceProviderInterface
{
    public function createService($name);
}
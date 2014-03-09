<?php

namespace Hydra\Providers\Facebook;

use Hydra\Providers\Hydra\AbstractConfigCommand;

class ConfigCommand extends AbstractConfigCommand
{
    /**
     * returns the service name
     *
     * @return string
     */
    protected function getServiceName()
    {
        return 'facebook';
    }
}
<?php

namespace Hydra\Providers\Facebook;

use Hydra\Commands\AbstractConfigCommand;

class ConfigCommand extends AbstractConfigCommand
{
    /**
     * returns the service name
     *
     * @return string
     */
    protected function getServiceName()
    {
        return 'Facebook';
    }
}
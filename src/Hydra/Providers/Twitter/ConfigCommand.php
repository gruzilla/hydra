<?php

namespace Hydra\Providers\Twitter;

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
        return 'twitter';
    }

    /**
     * twitter requires an additional parameter for generating the
     * authorization uri
     *
     * @param Twitter $service twitter service
     *
     * @return array
     */
    protected function getAuthorizationParameters($service)
    {
        //return array();
        $token = $service->requestRequestToken();
        return array('oauth_token' => $token->getRequestToken());
    }
}
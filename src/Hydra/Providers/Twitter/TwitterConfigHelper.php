<?php

namespace Hydra\Providers\Twitter;

use Hydra\Common\Helper\ConfigHelper;

class TwitterConfigHelper extends ConfigHelper
{
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
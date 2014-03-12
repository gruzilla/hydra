<?php

namespace Hydra\Providers\Instagram;

use Hydra\Interfaces\OAuth2AccessTokenProviderInterface;

class AccessTokenProvider implements OAuth2AccessTokenProviderInterface
{
    public function retrieveAccessToken($service, $storage, $parameters)
    {
        if (!array_key_exists('code', $parameters)) {
            throw new \InvalidArgumentException('No code was found in the given parameters.');
        }

        $service->requestAccessToken($parameters['code']);
    }
}
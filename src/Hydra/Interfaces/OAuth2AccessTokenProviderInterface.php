<?php

namespace Hydra\Interfaces;

interface OAuth2AccessTokenProviderInterface
{
    public function retrieveAccessToken($service, $storage, $parameters);
}
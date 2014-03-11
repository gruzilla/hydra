<?php

namespace Hydra\Interfaces;

interface OAuth1AccessTokenProviderInterface
{
    public function retrieveAccessToken($service, $storage, $parameters);
}
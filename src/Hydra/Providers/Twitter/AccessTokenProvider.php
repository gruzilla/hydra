<?php

namespace Hydra\Providers\Twitter;

use Hydra\Interfaces\OAuth1AccessTokenProviderInterface;

class AccessTokenProvider implements OAuth1AccessTokenProviderInterface
{
    public function retrieveAccessToken($service, $storage, $parameters)
    {
        if (!array_key_exists('oauth_token', $parameters)) {
            throw new \InvalidArgumentException('No oauth_token was found in the given parameters.');
        }

        if (!array_key_exists('oauth_verifier', $parameters)) {
            throw new \InvalidArgumentException('No oauth_token was found in the given parameters.');
        }

        $token = $storage->retrieveAccessToken('Twitter');

        $service->requestAccessToken(
            $parameters['oauth_token'],
            $parameters['oauth_verifier'],
            $token->getRequestTokenSecret()
        );
    }
}
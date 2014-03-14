<?php

namespace Hydra\Mocks\OAuth;

use Hydra\OAuth\HydraTokenStorage;

class HydraTokenStorageStub extends HydraTokenStorage
{

    /**
     * @override
     */
    protected function loadConfigs()
    {

    }


    protected function save($serviceName)
    {

    }


    public function storeAccessToken($serviceName, \OAuth\Common\Token\TokenInterface $token)
    {

    }


}
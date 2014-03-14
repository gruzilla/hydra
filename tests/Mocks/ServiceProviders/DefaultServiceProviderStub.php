<?php

namespace Hydra\Mocks\ServiceProviders;

use Hydra\ServiceProviders\DefaultServiceProvider;

use Hydra\Mocks\OAuth\HydraTokenStorageStub;


class DefaultServiceProviderStub extends DefaultServiceProvider
{

    public function __construct(HydraTokenStorage $storage = null)
    {
        parent::__construct(new HydraTokenStorageStub());
    }

}
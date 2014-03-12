<?php

namespace Hydra\Common\Helper;

use Hydra\Hydra,
    Hydra\Jobs\Job,
    Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider;

use OAuth\Common\Token\TokenInterface;

class ExpirationHelper
{
    public function __construct(Hydra $hydra, HydraTokenStorage $storage)
    {
        $this->hydra = $hydra;
        $this->storage = $storage;

        $this->hydra->load();

    }

    public function getServiceExpirations()
    {
        // get token expiration for every service
        $services = $this->hydra->getLoadedServices();


        $expirations = array();
        foreach ($services as $serviceName => $service) {
            $token = $this->storage->retrieveAccessToken($serviceName);

            $eol = $token->getEndOfLife();

            $expiration
                = $eol === TokenInterface::EOL_UNKNOWN ?
                    'unknown' :
                    $eol === TokenInterface::EOL_NEVER_EXPIRES ?
                        'never' :
                        $eol;


            $expirations[] = array(
                $serviceName,
                $expiration
            );
        }

        return $expirations;
    }
}
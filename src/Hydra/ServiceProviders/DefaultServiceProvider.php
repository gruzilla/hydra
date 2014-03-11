<?php

namespace Hydra\ServiceProviders;

use Hydra\Interfaces\ServiceProviderInterface,
    Hydra\Interfaces\OAuth1AccessTokenProviderInterface,
    Hydra\OAuth\HydraTokenStorage;

use OAuth\Common\Consumer\Credentials;

class DefaultServiceProvider implements ServiceProviderInterface
{
    protected $objectCache;
    protected $storage;

    public function __construct(HydraTokenStorage $storage = null)
    {
        $this->storage = $storage ?: new HydraTokenStorage();
        $this->objectCache = array();
    }

    public function loadAllServices()
    {
        foreach ($this->storage->getLoadedServicesNames() as $serviceName) {
            $this->createService($serviceName);
        }

        // allow chaining
        return $this;
    }

    public function getLoadedServices()
    {
        return $this->objectCache;
    }

    public function loadService($serviceName)
    {
        $this->createService($serviceName);

        // allow chaining
        return $this;
    }

    public function createService($serviceName)
    {

        if (!$this->storage->hasConfig($serviceName)) {
            throw new \InvalidArgumentException('Service ' . $serviceName . ' unknown or not configured!');
        }

        if (array_key_exists($serviceName, $this->objectCache)) {
            return $this->objectCache[$serviceName];
        }

        if (empty($this->storage->getConsumerKey($serviceName))) {
            throw new \InvalidArgumentException('Service ' . $serviceName . ' has a configuration file, but the consumer key is not defined!');
        }

        if (empty($this->storage->getConsumerSecret($serviceName))) {
            throw new \InvalidArgumentException('Service ' . $serviceName . ' has a configuration file, but the consumer service is not defined!');
        }

        if (empty($this->storage->getCallbackUrl($serviceName))) {
            throw new \InvalidArgumentException('Service ' . $serviceName . ' has a configuration file, but the callback url is not defined!');
        }

        $callbackUrl = $this->storage->getCallbackUrl($serviceName) . $serviceName;

        /** @var $serviceFactory \OAuth\ServiceFactory An OAuth service factory. */
        $serviceFactory = new \OAuth\ServiceFactory();
        $uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
        $currentUri = $uriFactory->createFromAbsolute($callbackUrl);

        $credentials = new Credentials(
            $this->storage->getConsumerKey($serviceName),
            $this->storage->getConsumerSecret($serviceName),
            $currentUri->getAbsoluteUri()
        );

        $this->objectCache[$serviceName] = $serviceFactory->createService($serviceName, $credentials, $this->storage);

        return $this->objectCache[$serviceName];
    }

    public function createAccessTokenProvider($serviceName)
    {
        $class = 'Hydra\\Providers\\' . ucfirst($serviceName) . '\\AccessTokenProvider';
        if (!class_exists($class)) {
            throw new \InvalidArgumentException('There is no AccessTokenProvider defined for service '.$serviceName);
        }
        return new $class();
    }

    public function retrieveAccessToken($serviceName, $tokenParameters)
    {
        $provider = $this->createAccessTokenProvider($serviceName);
        $service = $this->createService($serviceName);

        if ($provider instanceof OAuth1AccessTokenProviderInterface) {
            return $provider->retrieveAccessToken($service, $this->storage, $tokenParameters);
        }

        return null;
    }
}
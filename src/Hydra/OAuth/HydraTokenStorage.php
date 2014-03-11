<?php

namespace Hydra\OAuth;

class HydraTokenStorage extends YamlTokenStorage
{

    public function getConsumerKey($service)
    {
        return $this->getParameter($service, 'key');
    }

    public function setConsumerKey($service, $value)
    {
        $this->setParameter($service, 'key', $value);

        // allow chaining
        return $this;
    }

    public function getConsumerSecret($service)
    {
        return $this->getParameter($service, 'secret');
    }

    public function setConsumerSecret($service, $value)
    {
        $this->setParameter($service, 'secret', $value);

        // allow chaining
        return $this;
    }

    public function getCallbackUrl($service)
    {
        return $this->getParameter($service, 'callback');
    }

    public function setCallbackUrl($service, $value)
    {
        $this->setParameter($service, 'callback', $value);

        // allow chaining
        return $this;
    }

    protected function getParameter($service, $parameter)
    {
        // normalize service name
        $service = ucfirst($service);

        if (!array_key_exists($service, $this->configs)) {
            return null;
        }
        if (!array_key_exists($parameter, $this->configs[$service])) {
            return null;
        }
        return $this->configs[$service][$parameter];
    }

    protected function setParameter($service, $parameter, $value)
    {
        // normalize service name
        $service = ucfirst($service);

        if (!array_key_exists($service, $this->configs)) {
            $this->configs[$service] = array();
        }

        $this->configs[$service][$parameter] = $value;
        $this->save($service);
    }
}
<?php

namespace Hydra\OAuth;

use OAuth\Common\Storage\Memory;
use OAuth\Common\Token\TokenInterface;
use Symfony\Component\Yaml\Yaml;

class YamlTokenStorage extends Memory
{
    const ACCES_TOKEN_KEY = 'accessToken';
    protected $configPath;
    protected $configs;

    public function __construct($configPath = 'config/hydra')
    {
        parent::__construct();
        $this->configPath = $configPath;
        $this->configs = array();
        $this->loadConfigs();
    }

    public function getLoadedServicesNames()
    {
        return array_keys($this->configs);
    }

    public function hasConfig($serviceName)
    {
        // normalize service name
        $serviceName = ucfirst($serviceName);

        return array_key_exists($serviceName, $this->configs);
    }

    protected function loadConfigs()
    {
        $search = $this->configPath . '/*';
        $basePath = dirname($search);
        if (!file_exists($basePath) || !is_writable($basePath)) {
            throw new \RuntimeException(
                'The configuration path ' . $basePath .' does not ' .
                'exist. Please create it and make sure it is writable.'
            );
        }
        
        // glob is not stream save, therefor we use php5 recursiveiterator
        $serviceConfigs = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($this->configPath), 
                        \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($serviceConfigs as $configFile) {
            $serviceName = ucfirst(explode('.', basename($configFile))[0]);

            $this->configs[$serviceName] = Yaml::parse($configFile);
            $this->configs[$serviceName] = $this->configs[$serviceName] ?: array();

            if (array_key_exists(self::ACCES_TOKEN_KEY, $this->configs[$serviceName])) {
                $this->tokens[$serviceName] = unserialize($this->configs[$serviceName][self::ACCES_TOKEN_KEY]);
            }
        }
    }

    protected function save($serviceName)
    {
        // normalize service name
        $serviceName = ucfirst($serviceName);

        $configFile = $this->configPath . '/' . $serviceName . '.yml';
        file_put_contents(
            $configFile,
            Yaml::dump($this->configs[$serviceName])
        );
    }

    /**
     * {@inheritDoc}
     */
    public function storeAccessToken($serviceName, TokenInterface $token)
    {
        // normalize service name
        $serviceName = ucfirst($serviceName);

        parent::storeAccessToken($serviceName, $token);

        $this->configs[$serviceName][self::ACCES_TOKEN_KEY] = serialize($token);
        $this->save($serviceName);

        // allow chaining
        return $this;
    }
}
<?php

namespace Hydra\Common\Helper;

use Hydra\Hydra,
    Hydra\OAuth\HydraTokenStorage,
    Hydra\Interfaces\ServiceProviderInterface;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\Process\Process;

class ConfigHelper
{
    // overwrite to true if your service needs scopes
    protected $needsScopes = false;

    protected $storage;
    protected $serviceProvider;
    protected $serviceName;
    protected $help;
    protected $providerFolder;
    protected $phpServerProcess;

    public function __construct(HydraTokenStorage $storage, ServiceProviderInterface $provider, $serviceName)
    {
        $this->storage = $storage;
        $this->serviceProvider = $provider;
        $this->serviceName = $serviceName;
        $this->providerFolder = dirname(dirname(__DIR__)).'/Providers/'.ucfirst($this->serviceName);
        $this->loadHelp();
    }

    public function configure(
        $consumerCredentialsCallback,       // array($key, $secret)
        $callbackUrlCallback,               // $callbackUrl
        $startPhpServerCallback,            // boolean
        $phpBinaryCallback,                 // $phpBinaryPath
        $scopeCallback,                     // array('scope1', 'scope2', ...)
        $useCasperCallback,                 // boolean
        $casperResult,                      // void
        $usernamePasswordCallback,          // array('username' => $username, 'password' => $password)
        $stopServerConfirmation             // void
    ) {

        //######################################################################
        // update consumer credentials
        //######################################################################

        $this->updateConsumerCredentials($consumerCredentialsCallback);

        //######################################################################
        // update callback url
        //######################################################################

        $this->updateCallbackUrlAndStartPHPServer(
            $callbackUrlCallback,
            $startPhpServerCallback,
            $phpBinaryCallback
        );

        //######################################################################
        // update scope if necessary
        //######################################################################

        $this->updateScope($scopeCallback);

        //######################################################################
        // generate authorization url
        //######################################################################

        $url = $this->getAuthorizationUri();

        //######################################################################
        // try to call authorizaion url automatically using casperjs
        //######################################################################

        $manuallyStopServerIfRunning = !$this->callAuthorizationUrlWithCasper(
            $url,
            $useCasperCallback,
            $casperResult,
            $usernamePasswordCallback
        );

        //######################################################################
        // clean up
        //######################################################################

        $this->stopPHPServer($manuallyStopServerIfRunning, $stopServerConfirmation);
    }

    public function isConfigEmpty()
    {
        $key = $this->storage->getConsumerKey($this->serviceName);
        $secret = $this->storage->getConsumerSecret($this->serviceName);

        return empty($key) && empty($secret);
    }

    public function getDeveloperLink()
    {
        if (null === $this->help) {
            return null;
        }
        return $this->help['link'];
    }

    public function getHelp()
    {
        if (null === $this->help) {
            return null;
        }
        return $this->help['setup'];
    }

    // overwrite if your service need special authorization parameters
    protected function getAuthorizationParameters($service)
    {
        return array();
    }

    protected function updateConsumerCredentials($consumerCredentialsCallback)
    {
        list($key, $secret) = $consumerCredentialsCallback(
            $this->storage->getConsumerKey($this->serviceName),
            $this->storage->getConsumerSecret($this->serviceName)
        );
        $this->storage->setConsumerKey($this->serviceName, $key);
        $this->storage->setConsumerSecret($this->serviceName, $secret);
    }

    protected function updateCallbackUrlAndStartPHPServer($callbackUrlCallback, $startPhpServerCallback, $phpBinaryCallback)
    {
        $callbackUrl = $callbackUrlCallback(
            $this->storage->getCallbackUrl($this->serviceName)
        );

        if (false !== strpos($this->serviceName, $callbackUrl)) {

            if ('/' !== $callbackUrl{strlen($callbackUrl)-1}) {
                $callbackUrl .= '/';
            }

            $callbackUrl .= $this->serviceName;
        }

        //######################################################################
        // validate callback url
        //######################################################################
        $headers = @get_headers($this->storage->getCallbackUrl($this->serviceName));

        // if validation fails, try to start our own php server to serve the
        // callback url
        if (false === $headers && $startPhpServerCallback()) {
            $this->tryPHPServer($phpBinaryCallback);
        } else {
            // we assume that when our server started correctly, all paths work
            // so we do no further checks.
            // however if we do not use our own server, check if everythings ok

            if (false === $headers
                || strtolower($headers[0]) == strtolower('HTTP/1.0 404 Not Found')
                || strtolower($headers[0]) == strtolower('HTTP/1.1 404 Not Found')
            ) {
                throw new \RuntimeException('The callback url is not reachable (404)');
            }
        }
        $this->storage->setCallbackUrl($this->serviceName, $callbackUrl);
    }

    protected function updateScope($scopeCallback)
    {
        if (!$this->needsScopes) {
            return;
        }

        $scope = $this->storage->getScope($this->serviceName);
        $scope = $scopeCallback($scope);
        $this->storage->setScope($this->serviceName, $scope);
    }

    protected function getAuthorizationUri()
    {
        // create service
        $service = $this->serviceProvider->createService($this->serviceName);

        // generate authorization url
        return $service->getAuthorizationUri(
            $this->getAuthorizationParameters($service)
        );
    }

    protected function callAuthorizationUrlWithCasper($url, $useCasperCallback, $casperResult, $usernamePasswordCallback)
    {
        if (!$useCasperCallback($this->detectCasperJs())) {
            return false;
        }

        // detect casper file
        $casperFile = $this->providerFolder.'/login.js';
        if (file_exists($casperFile)) {
            // if fails, show it and let the user do it manually
            $casperWorked = $this->tryCasper($url, $casperFile, $usernamePasswordCallback);
            if (false === $casperWorked) {
                $casperResult(false, true, null, $url);
                return false;
            } else {
                $casperResult(true, true, $casperWorked, $url);
            }
        } else {
            $casperResult(false, false, null, $url);
            return false;
        }

        return true;
    }

    protected function stopPHPServer($manuallyStopServerIfRunning, $stopServerConfirmation)
    {
        if (null !== $this->phpServerProcess) {
            if ($manuallyStopServerIfRunning) {
                $stopServerConfirmation();
            }
            $this->phpServerProcess->stop();
        }
    }

    protected function loadHelp()
    {
        $file = realpath($this->providerFolder.'/help.yml');

        if (file_exists($file)) {
            $this->help = Yaml::parse($file);
        }
    }

    protected function tryPHPServer($phpBinaryCallback)
    {

        $callback = parse_url($this->storage->getCallbackUrl($this->serviceName));

        $docRoot = realpath(dirname(dirname(__DIR__)) . '/Resources/DocRoot');

        if (empty($docRoot) || !file_exists($docRoot)) {
            throw new \RuntimeException('DocRoot not found. Given path: ' . $docRoot);
        }

        $phpBinary = $phpBinaryCallback('php');

        $phpServerCommand = 'exec ' . $phpBinary . ' -S ' . $callback['host'] . ':' .
                                $callback['port'] . ' -t ' . $docRoot;

        $this->phpServerProcess = new Process(
            $phpServerCommand
        );

        $this->phpServerProcess->start();
    }

    protected function detectCasperJs()
    {
        $casperVersionCommand = 'casperjs --version';
        $casperCheck = new Process($casperVersionCommand);

        $casperCheck->run();

        if (!$casperCheck->isSuccessful()) {
            return false;
        }

        $version = $casperCheck->getOutput();
        $version = explode('.', $version);

        return is_numeric($version[0]);
    }

    protected function tryCasper($url, $casperFile, $usernamePasswordCallback)
    {
        // check if casper file for login and app-authorization exists
        // also check if casper itself is available

        extract($usernamePasswordCallback());

        $command = "casperjs $casperFile $url $username $password";

        // just 4 fun
        unset($password);

        $casper = new Process($command);
        $casper->run();

        if (!$casper->isSuccessful()) {
            return false;
        }

        return $casper->getOutput();
    }

}
<?php

namespace Hydra\Commands\Base;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Helper\DialogHelper;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\Process\Process;

use Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider;

class ConfigExecuter
{
    protected $setupInfo = array();
    protected $phpServerProcess = null;
    protected $storage;
    protected $dialog;
    protected $serviceName;
    protected $providerFolder;

    public function __construct($serviceName, HydraTokenStorage $storage, DialogHelper $dialog)
    {
        $this->serviceName = $serviceName;
        $this->dialog = $dialog;
        $this->storage = $storage;
        $this->providerFolder = dirname(dirname(__DIR__)).'/Providers/'.ucfirst($this->serviceName);
    }

    protected function getAuthorizationParameters($service)
    {
        return array();
    }

    protected function loadSetup()
    {
        $file = realpath($this->providerFolder.'/setup.yml');

        if (file_exists($file)) {
            $this->setupInfo = Yaml::parse($file);
        }
    }

    protected function openLinkInDefaultBrowser($url)
    {
        if (false !== strpos(strtolower(PHP_OS), 'darwin')) {
            // mac os x
            `open $url`;
            return;
        }

        // only works in linux environments
        `xdg-open $url`;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Please follow the steps below to authorize '.$this->serviceName.':</info>');


        // load help
        $this->loadSetup($this->serviceName);


        // display help for service
        if (isset($this->setupInfo['setup'])) {
            $output->writeln($this->setupInfo['setup']);
        }


        // only ask user to open development site if key+secret are empty
        $key = $this->storage->getConsumerKey($this->serviceName);
        $secret = $this->storage->getConsumerSecret($this->serviceName);

        if (isset($this->setupInfo['link']) && empty($key) && empty($secret)) {
            $this->dialog->ask(
                $output,
                'Press [ENTER] to open the development site.'
            );
            $this->openLinkInDefaultBrowser($this->setupInfo['link']);
        }


        // ask consumer credentials and callback url
        // they get stored automatically on setting the values
        $this->askConsumerCredentials($input, $output);

        // ask scope for authentification
        $this->askScope($input, $output);

        // ask callback url
        $this->askCallbackUrl($input, $output);


        // create service
        $provider = new DefaultServiceProvider($this->storage);
        $service = $provider->createService($this->serviceName);


        // generate authorization url
        $url = $service->getAuthorizationUri(
            $this->getAuthorizationParameters($service)
        );


        // try to call auth url with casper, if fails, show it and let the user
        // do it manually
        $manuallyStopServerIfRunning = false;
        if (!$this->tryCasper($url, $input, $output, $this->serviceName)) {
            $manuallyStopServerIfRunning = true;
            $output->writeln('<info>Please open this link and authorize the app:</info>');
            $output->writeln('url: ' . $url);

            $open = $this->dialog->ask(
                $output,
                '<info>Press [ENTER] to open the link in your default browser ' .
                'or enter [N] to exit and follow the link manually.',
                false
            );
            if ($open !== 'N') {
                $this->openLinkInDefaultBrowser($url);
            }
        }


        // clean up
        if (null !== $this->phpServerProcess) {
            if ($manuallyStopServerIfRunning) {
                $this->dialog->ask(
                    $output,
                    '<info>Press [ENTER] to stop the local server.</info>',
                    false
                );
            }
            $this->phpServerProcess->stop();
        }
    }

    protected function askCallbackUrl(InputInterface $input, OutputInterface $output)
    {

        $callbackUrl = $this->storage->getCallbackUrl($this->serviceName);
        $callbackUrl = $this->dialog->ask(
            $output,
            'Callback url' . ( empty($callbackUrl) ? '' : ' [' . $callbackUrl . ']') . ': ',
            $callbackUrl
        );

        if ('/' !== $callbackUrl{strlen($callbackUrl)-1}) {
            $callbackUrl .= '/';
        }

        $this->storage->setCallbackUrl($this->serviceName, $callbackUrl);

        $headers = @get_headers($this->storage->getCallbackUrl($this->serviceName));
        $headersService = @get_headers($this->storage->getCallbackUrl($this->serviceName) . $this->serviceName);

        $callbackNotReachable = false === $headers
            || strtolower($headers[0]) == strtolower('HTTP/1.0 404 Not Found')
            || strtolower($headers[0]) == strtolower('HTTP/1.1 404 Not Found');
        $serviceNotReachable = false === $headersService
            || strtolower($headersService[0]) == strtolower('HTTP/1.0 404 Not Found')
            || strtolower($headersService[0]) == strtolower('HTTP/1.1 404 Not Found');

        if ($callbackNotReachable || $serviceNotReachable) {
            if (false === $headers) {
                $this->tryPHPServer($input, $output, $this->serviceName);
            } else {
                if ($callbackNotReachable) {
                    $output->writeln('<error>The callback url is not reachable (404)</error>');
                }
                if ($serviceNotReachable) {
                    $output->writeln('<error>The subfolder callback/'.$this->serviceName.' is not reachable (404)</error>');
                }
                exit;
            }
        }
    }

    protected function askScope(InputInterface $input, OutputInterface $output)
    {

        $scope = $this->storage->getScope($this->serviceName);
        $scope = $this->dialog->ask(
            $output,
            'Scope (comma seperated)' . ( empty($scope) ? '' : ' [' . implode(',', $scope) . ']') . ': ',
            $scope
        );

        $scope = explode(',', $scope);

        $this->storage->setScope($this->serviceName, $scope);

        // chaining
        return $this;
    }

    protected function tryPHPServer(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>No server running under the given url</comment>');
        $output->writeln('Attempting to start native PHP server...');

        $callback = parse_url($this->storage->getCallbackUrl($this->serviceName));

        $docRoot = realpath(dirname(dirname(__DIR__)) . '/Resources/DocRoot');

        if (empty($docRoot) || !file_exists($docRoot)) {
            $output->writeln('<error>DocRoot not found.</error>');
            return false;
        }

        $phpBinary = $this->askForPhpBinary($input, $output, 'php');

        $phpServerCommand = 'exec ' . $phpBinary . ' -S ' . $callback['host'] . ':' .
                                $callback['port'] . ' -t ' . $docRoot;

        $this->phpServerProcess = new Process(
            $phpServerCommand
        );

        $this->phpServerProcess->start();

        return true;
    }

    protected function askForPhpBinary(InputInterface $input, OutputInterface $output, $default)
    {
        return $this->dialog->askAndValidate(
            $output,
            'Which PHP binary should be used? [' . $default . ']' . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The path to the php binary can not be empty');
                }
                return $value;
            },
            false,
            $default
        );
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

    protected function tryCasper($url, InputInterface $input, OutputInterface $output)
    {
        // check if casper file for login and app-authorization exists
        // also check if casper itself is available
        $casperFile = $this->providerFolder.'/login.js';
        if (!file_exists($casperFile) || !$this->detectCasperJs()) {
            $output->writeln('No CasperJs login file found. Continuning manually.');
            return false;
        }

        $output->writeln('<info>CasperJs detected.</info>');
        $output->writeln('Attempting to use it...');

        extract($this->askUsernamePassword($input, $output));

        $command = "casperjs $casperFile $url $username $password";

        // just 4 fun
        unset($password);

        $casper = new Process($command);
        $output->writeln('working...');
        $casper->run();

        if (!$casper->isSuccessful()) {
            return false;
        }

        $callbackResult = $casper->getOutput();

        $output->writeln('<info>received:</info>');
        $output->writeln($callbackResult);

        return true;
    }

    protected function askConsumerCredentials(InputInterface $input, OutputInterface $output)
    {
        $key = $this->storage->getConsumerKey($this->serviceName);
        $key = $this->dialog->askAndValidate(
            $output,
            'Consumer key' . ( empty($key) ? '' : ' [' . $key . ']') . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The consumer key can not be empty');
                }
                return $value;
            },
            false,
            $key
        );
        $this->storage->setConsumerKey($this->serviceName, $key);

        $secret = $this->storage->getConsumerSecret($this->serviceName);
        $secret = $this->dialog->askAndValidate(
            $output,
            'Consumer secret' . ( empty($secret) ? '' : ' [' . $secret . ']') . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The consumer secret can not be empty');
                }
                return $value;
            },
            false,
            $secret
        );
        $this->storage->setConsumerSecret($this->serviceName, $secret);

        // allow chaining
        return $this;
    }

    protected function askUsernamePassword(InputInterface $input, OutputInterface $output)
    {
        $username = null;
        $password = null;

        while (empty($username)) {
            $username = $this->dialog->ask(
                $output,
                'Username: '
            );
        }

        while (empty($password)) {
            $password = $this->dialog->askHiddenResponse(
                $output,
                'Password: '
            );
        }

        return array(
            'username' => $username,
            'password' => $password
        );
    }
}
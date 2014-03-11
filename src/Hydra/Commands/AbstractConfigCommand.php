<?php

namespace Hydra\Commands;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\Process\Process;

use Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider;

abstract class AbstractConfigCommand extends Command
{
    protected $setupInfo = array();
    protected $phpServerProcess = null;
    protected $storage;

    abstract protected function getServiceName();

    protected function configure()
    {
        $this
            ->setName('hydra:' . $this->getServiceName() . ':config')
            ->setDescription('Configure credentials for ' . $this->getServiceName());
        ;
        $this->storage = new HydraTokenStorage();
    }

    protected function loadSetup($serviceName)
    {
        $file = realpath(__DIR__.'/../Providers/'.ucfirst($serviceName).'/setup.yml');

        if (file_exists($file)) {
            $this->setupInfo = Yaml::parse($file);
        }
    }

    protected function openLinkInDefaultBrowser($url)
    {
        if (false !== strpos(strtolower(PHP_OS), 'darwin')) {
            // mac os x
            `open $url`;
        }

        // only works in linux environments
        `xdg-open $url`;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceName = $this->getServiceName();
        $dialog = $this->getHelperSet()->get('dialog');
        $output->writeln('<info>Please follow the steps below to authorize '.$serviceName.':</info>');


        // load help
        $this->loadSetup($serviceName);


        // display help for service
        if (isset($this->setupInfo['setup'])) {
            $output->writeln($this->setupInfo['setup']);
        }


        // only ask user to open development site if key+secret are empty
        $key = $this->storage->getConsumerKey($serviceName);
        $secret = $this->storage->getConsumerSecret($serviceName);

        if (isset($this->setupInfo['link']) && empty($key) && empty($secret)) {
            $dialog->ask(
                $output,
                'Press [ENTER] to open the development site.'
            );
            $this->openLinkInDefaultBrowser($this->setupInfo['link']);
        }


        // ask consumer credentials and callback url
        // they get stored automatically on setting the values
        $this->askConsumerCredentials($input, $output, $serviceName);
        $callbackUrl = $this->askCallbackUrl($input, $output, $serviceName);


        // create service
        $provider = new DefaultServiceProvider($this->storage);
        $service = $provider->createService($serviceName);


        // generate authorization url
        $url = $service->getAuthorizationUri(
            $this->getAuthorizationParameters($service)
        );


        // try to call auth url with casper, if fails, show it and let the user
        // do it manually
        if (!$this->tryCasper($url, $input, $output, $serviceName)) {
            $output->writeln('<info>Please open this link and authorize the app:</info>');
            $output->writeln('url: ' . $url);

            $open = $dialog->ask(
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
            $this->phpServerProcess->stop();
        }
    }

    protected function askCallbackUrl(InputInterface $input, OutputInterface $output, $serviceName)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $callbackUrl = $this->storage->getCallbackUrl($serviceName);
        $callbackUrl = $dialog->ask(
            $output,
            'Callback url' . ( empty($callbackUrl) ? '' : ' [' . $callbackUrl . ']') . ': ',
            $callbackUrl
        );

        if ('/' !== $callbackUrl{strlen($callbackUrl)-1}) {
            $callbackUrl .= '/';
        }

        $this->storage->setCallbackUrl($serviceName, $callbackUrl);

        $headers = @get_headers($this->storage->getCallbackUrl($serviceName));
        $headersService = @get_headers($this->storage->getCallbackUrl($serviceName) . $serviceName);

        $callbackNotReachable = false === $headers
            || strtolower($headers[0]) == strtolower('HTTP/1.0 404 Not Found')
            || strtolower($headers[0]) == strtolower('HTTP/1.1 404 Not Found');
        $serviceNotReachable = false === $headersService
            || strtolower($headersService[0]) == strtolower('HTTP/1.0 404 Not Found')
            || strtolower($headersService[0]) == strtolower('HTTP/1.1 404 Not Found');

        if ($callbackNotReachable || $serviceNotReachable) {
            if (false === $headers) {
                $this->tryPHPServer($input, $output, $serviceName);
            } else {
                if ($callbackNotReachable) {
                    $output->writeln('<error>The callback url is not reachable (404)</error>');
                }
                if ($serviceNotReachable) {
                    $output->writeln('<error>The subfolder callback/'.$serviceName.' is not reachable (404)</error>');
                }
                exit;
            }
        }
    }

    protected function tryPHPServer(InputInterface $input, OutputInterface $output, $serviceName)
    {
        $output->writeln('<comment>No server running under the given url</comment>');
        $output->writeln('Attempting to start native PHP server...');

        $callback = parse_url($this->storage->getCallbackUrl($serviceName));

        $docRoot = realpath(__DIR__ . '/../Resources/DocRoot');

        if (empty($docRoot) || !file_exists($docRoot)) {
            $output->writeln('<error>DocRoot not found.</error>');
            return false;
        }

        $phpServerCommand = 'exec php -S ' . $callback['host'] . ':' .
                                $callback['port'] . ' -t ' . $docRoot;

        $this->phpServerProcess = new Process(
            $phpServerCommand
        );

        $this->phpServerProcess->start();

        return true;
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

    protected function tryCasper($url, InputInterface $input, OutputInterface $output, $serviceName)
    {
        // check if casper file for login and app-authorization exists
        // also check if casper itself is available
        $casperFile = __DIR__.'/../Providers/'.ucfirst($serviceName).'/login.js';
        if (!file_exists($casperFile) || !$this->detectCasperJs()) {
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

    protected function getAuthorizationParameters($service)
    {
        return array();
    }

    protected function askConsumerCredentials(InputInterface $input, OutputInterface $output, $serviceName)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $key = $this->storage->getConsumerKey($serviceName);
        $key = $dialog->askAndValidate(
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
        $this->storage->setConsumerKey($serviceName, $key);

        $secret = $this->storage->getConsumerSecret($serviceName);
        $secret = $dialog->askAndValidate(
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
        $this->storage->setConsumerSecret($serviceName, $secret);

        // allow chaining
        return $this;
    }

    protected function askUsernamePassword(InputInterface $input, OutputInterface $output)
    {
        $username = null;
        $password = null;

        $dialog = $this->getHelperSet()->get('dialog');

        while (empty($username)) {
            $username = $dialog->ask(
                $output,
                'Username: '
            );
        }

        while (empty($password)) {
            $password = $dialog->askHiddenResponse(
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
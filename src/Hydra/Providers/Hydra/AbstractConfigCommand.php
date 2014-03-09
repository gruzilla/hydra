<?php

namespace Hydra\Providers\Hydra;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Yaml\Yaml;

use Symfony\Component\Process\Process;

use OAuth\Common\Storage\Memory,
    OAuth\Common\Consumer\Credentials;

abstract class AbstractConfigCommand extends Command
{
    protected $setupInfo = array();
    protected $credentials = array();
    protected $phpServerProcess = null;

    abstract protected function getServiceName();

    protected function configure()
    {
        $this
            ->setName('hydra:' . $this->getServiceName() . ':config')
            ->setDescription('Configure credentials for ' . $this->getServiceName());
        ;
    }

    protected function loadSetup()
    {
        $file = realpath(__DIR__.'/../'.ucfirst($this->getServiceName()).'/setup.yml');

        if (file_exists($file)) {
            $this->setupInfo = Yaml::parse($file);
        }

        try {
            $file = 'config/' . $this->getServiceName() . '.yml';
            $this->credentials = Yaml::parse($file);
        } catch (\Exception $e) {
            $this->credentials = array(
                'key' => '',
                'secret' => ''
            );
        }
    }

    protected function saveSetup()
    {
        $file = 'config/' . $this->getServiceName() . '.yml';
        file_put_contents(
            $file,
            Yaml::dump($this->credentials)
        );
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
        $this->loadSetup();
        $dialog = $this->getHelperSet()->get('dialog');

        $output->writeln('<info>Please follow the steps below to authorize '.$this->getServiceName().':</info>');

        if (isset($this->setupInfo['setup'])) {
            $output->writeln($this->setupInfo['setup']);
        }

        if (isset($this->setupInfo['link']) && empty($this->credentials['key']) && empty($this->credentials['secret'])) {
            $dialog->ask(
                $output,
                'Press [ENTER] to open the development site.'
            );
            $this->openLinkInDefaultBrowser($this->setupInfo['link']);
        }


        $this->askConsumerCredentials($input, $output);

        $callbackUrl = $this->getCallbackUrl($input, $output);

        $this->saveSetup();

        $service = $this->getService($callbackUrl);

        $url = $service->getAuthorizationUri(
            $this->getAuthorizationParameters($service)
        );

        $this->tryCasper($url, $input, $output);

        if (null !== $this->phpServerProcess) {
            $this->phpServerProcess->stop();
        }
    }

    protected function getCallbackUrl(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $this->credentials['callback'] = $dialog->ask(
            $output,
            'Callback url' . ( empty($this->credentials['callback']) ? '' : ' [' . $this->credentials['callback'] . ']') . ': ',
            $this->credentials['callback']
        );

        if ('/' !== $this->credentials['callback']{strlen($this->credentials['callback'])-1}) {
            $this->credentials['callback'] .= '/';
        }

        $headers = @get_headers($this->credentials['callback']);
        if (false === $headers
            || strtolower($headers[0]) == strtolower('HTTP/1.0 404 Not Found')
            || strtolower($headers[0]) == strtolower('HTTP/1.1 404 Not Found')
        ) {
            if (false === $headers) {
                $this->tryPHPServer($input, $output);
            } else {
                $output->writeln('<error>The callback url is not reachable (404)</error>');
                exit;
            }
        }

        $output->writeln('Constructed callback url: ' . $this->credentials['callback'] . $this->getServiceName());

        return $this->credentials['callback'] . $this->getServiceName();
    }

    protected function tryPHPServer(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>No server running under the given url</comment>');
        $output->writeln('Attempting to start native PHP server...');

        $callback = parse_url($this->credentials['callback']);

        $docRoot = realpath(__DIR__ . '/../../Resources/DocRoot');

        $this->phpServerProcess = new Process(
            'exec php -S ' . $callback['host'] . ':' . $callback['port'] . ' -t ' . $docRoot
        );

        $this->phpServerProcess->start();
    }

    protected function detectCasperJs()
    {
        $version = shell_exec('casperjs --version 2>&1');
        $version = explode('.', $version);
        return is_numeric($version[0]);
    }

    protected function tryCasper($url, InputInterface $input, OutputInterface $output)
    {
        // check if casper file for login and app-authorization exists
        // also check if casper itself is available
        $casperFile = __DIR__.'/../'.ucfirst($this->getServiceName()).'/login.js';
        if (file_exists($casperFile) && $this->detectCasperJs()) {
            $output->writeln('<info>CasperJs detected.</info>');
            $output->writeln('Attempting to use it...');

            extract($this->getUsernamePassword($input, $output));

            $command = "casperjs $casperFile $url $username $password";

            unset($password);

            $output->writeln('working...');
            passthru($command);

        } else {
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
    }

    protected function getAuthorizationParameters($service)
    {
        return array();
    }

    protected function getService($callbackUrl)
    {
        /** @var $serviceFactory \OAuth\ServiceFactory An OAuth service factory. */
        $serviceFactory = new \OAuth\ServiceFactory();
        $uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
        $currentUri = $uriFactory->createFromAbsolute($callbackUrl);
        $currentUri->setQuery('');
        $storage = new Memory();

        $credentials = new Credentials(
            $this->credentials['key'],
            $this->credentials['secret'],
            $currentUri->getAbsoluteUri()
        );


        return $serviceFactory->createService($this->getServiceName(), $credentials, $storage);
    }

    protected function askConsumerCredentials(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $dialog->askAndValidate(
            $output,
            'Consumer key' . ( empty($this->credentials['key']) ? '' : ' [' . $this->credentials['key'] . ']') . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The consumer key can not be empty');
                }
                $this->credentials['key'] = $value;
            },
            false,
            $this->credentials['key']
        );

        $dialog->askAndValidate(
            $output,
            'Consumer secret' . ( empty($this->credentials['secret']) ? '' : ' [' . $this->credentials['secret'] . ']') . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \Exception('The consumer secret can not be empty');
                }
                $this->credentials['secret'] = $value;
            },
            false,
            $this->credentials['secret']
        );
    }

    protected function getUsernamePassword(InputInterface $input, OutputInterface $output)
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
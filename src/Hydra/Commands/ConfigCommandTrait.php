<?php

namespace Hydra\Commands;

use Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider,
    Hydra\Common\Helper\ConfigHelper;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Output\OutputInterface;

trait ConfigCommandTrait
{
    protected $serviceName;
    protected $dialogHelper;

    use OpenDefaultBrowserTrait;

    protected function configure()
    {
        $this
            ->setName('hydra:config')
            ->setDescription('Configure hydra credentials for a given service')
            ->addArgument(
                'service',
                InputArgument::REQUIRED,
                'which service to configure (ucfirst)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->serviceName = $input->getArgument('service');
        $this->dialogHelper = $this->getHelperSet()->get('dialog');
        $configHelper = $this->getConfigHelper($this->serviceName);

        $output->writeln(
            '<info>Welcome</info>' . "\n" .
            "\n" .
            "<info>App-Auth-Help:</info>" ."\n" .
            'To start using hydra you have to create an application and authorize ' . "\n" .
            'yourself against it. Whilst creating your application you have to ' . "\n" .
            'provide a "REDIRECT URL" or "CALLBACK URL". If you have no local ' . "\n" .
            'webserver running which can handle the callback and storage of the ' ."\n" .
            'access token please let hydra start its own server and handle ' . "\n" .
            'everything. Make sure you use the correct callback url (you will ' . "\n" .
            'be asked for it later). Craft your callback the following way: ' ."\n" .
            "\n" .
            'http://[your-local-dns-with-tld]:[localport]/' . $this->serviceName . "\n" .
            "\n" .
            '<info>Please follow the steps below to authorize '.$this->serviceName.':</info>'
        );

        // display help if available
        $help = $configHelper->getHelp();
        if (!empty($help)) {
            $output->writeln($help);
        }


        // only ask user to open development site if key+secret are empty
        $developerLink = $configHelper->getDeveloperLink();
        if (!empty($developerLink) && $configHelper->isConfigEmpty()) {
            $this->dialogHelper->ask(
                $output,
                'Press [ENTER] to open the development site.'
            );
            $this->openLinkInDefaultBrowser($developerLink);
        }


        try {
            $configHelper->configure(

                // TODO: refactor - extract functions to separate class let configHelper depend on it

                function ($key, $secret) use ($input, $output) {          // ask for consumer credentials
                    return $this->askConsumerCredentials(
                        $input,
                        $output,
                        $key,
                        $secret
                    );
                },

                function ($callbackUrl) use ($input, $output) {           // ask for callback url
                    return $this->askCallbackUrl($input, $output, $callbackUrl);
                },

                function () use ($input, $output) {                       // ask if php-server should be used
                    $output->writeln('<comment>No server running under the given url</comment>');
                    $output->writeln('Attempting to start native PHP server...');

                    return true;
                },

                function ($phpBinary) use ($input, $output) {             // ask for php binary path
                    return $this->askForPhpBinary($input, $output, $phpBinary);
                },

                function ($scope) use ($input, $output) {           // ask for scope
                    return $this->askScope($input, $output, $scope ?: array());
                },

                function ($casperDetected) use ($input, $output) {        // ask if casper should be used if detected

                    $output->writeln('<info>CasperJs detected.</info>');
                    $output->writeln('Attempting to use it...');

                    return true;
                },

                function ($casperWorked, $casperFileAvailable, $casperOutput, $url) use ($input, $output) {    // called when casper fails
                    if ($casperWorked) {
                        $output->writeln('<info>CasperJs seemes to have worked</info>');
                        $output->writeln($casperOutput);
                    } else {
                        if (!$casperFileAvailable) {
                            $output->writeln('No CasperJs login file found. Continuning manually.');
                        }

                        $output->writeln('<info>Please open this link and authorize the app:</info>');
                        $output->writeln('url: ' . $url);

                        $open = $this->dialogHelper->ask(
                            $output,
                            '<info>Press [ENTER] to open the link in your default browser ' .
                            'or enter [N] to exit and follow the link manually.',
                            false
                        );
                        if ($open !== 'N') {
                            $this->openLinkInDefaultBrowser($url);
                        }
                    }
                },

                function () use ($input, $output) {                       // called when user should input service username/password
                    return $this->askUsernamePassword($input, $output);
                },

                function () use ($input, $output) {                       // called when configuration is finished but its unsure if casper worked and before the server process is stoped
                    $this->dialogHelper->ask(
                        $output,
                        '<info>Press [ENTER] to stop the local server.</info>',
                        false
                    );
                }
            );
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e . '</error>');
        }
    }

    // overwrite for dependency injection
    protected function getConfigHelper($serviceName)
    {
        $storage = new HydraTokenStorage();
        $provider = new DefaultServiceProvider($storage);

        $className = $this->getConfigHelperClassName($serviceName);

        return new $className(
            $storage,
            $provider,
            $serviceName
        );
    }

    protected function getConfigHelperClassName($serviceName)
    {
        $className = 'Hydra\\Common\\Helper\\ConfigHelper';

        $serviceClassName = 'Hydra\\Providers\\' . ucfirst($serviceName) . '\\' . ucfirst($serviceName) . 'ConfigHelper';

        if (class_exists($serviceClassName)) {
            $className = $serviceClassName;
        }

        return $className;
    }

    protected function askCallbackUrl(InputInterface $input, OutputInterface $output, $callbackUrl)
    {

        $callbackUrl = $this->dialogHelper->ask(
            $output,
            'Callback url' . ( empty($callbackUrl) ? '' : ' [' . $callbackUrl . ']') . ': ',
            $callbackUrl
        );

        return $callbackUrl;
    }

    protected function askScope(InputInterface $input, OutputInterface $output, array $scope)
    {
        $scope = $this->dialogHelper->ask(
            $output,
            'Scope (comma separated)' . ( empty($scope) ? '' : ' [' . join(', ', $scope) . ']') . ': ',
            join(', ', $scope)
        );

        return (empty($scope) ? array() : array_map('trim', explode(',', $scope)));
    }

    protected function askConsumerCredentials(InputInterface $input, OutputInterface $output, $key, $secret)
    {
        $key = $this->dialogHelper->askAndValidate(
            $output,
            'Consumer key' . ( empty($key) ? '' : ' [' . $key . ']') . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \RuntimeException('The consumer key can not be empty');
                }
                return $value;
            },
            false,
            $key
        );

        $secret = $this->dialogHelper->askAndValidate(
            $output,
            'Consumer secret' . ( empty($secret) ? '' : ' [' . $secret . ']') . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \RuntimeException('The consumer secret can not be empty');
                }
                return $value;
            },
            false,
            $secret
        );

        // allow chaining
        return array($key, $secret);
    }

    protected function askForPhpBinary(InputInterface $input, OutputInterface $output, $default)
    {
        return $this->dialogHelper->askAndValidate(
            $output,
            'Which PHP binary should be used? [' . $default . ']' . ': ',
            function ($value) {
                if (trim($value) === '') {
                    throw new \RuntimeException('The path to the php binary can not be empty');
                }
                return $value;
            },
            false,
            $default
        );
    }

    protected function askUsernamePassword(InputInterface $input, OutputInterface $output)
    {
        $username = null;
        $password = null;

        while (empty($username)) {
            $username = $this->dialogHelper->ask(
                $output,
                'Username: '
            );
        }

        while (empty($password)) {
            $password = $this->dialogHelper->askHiddenResponse(
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
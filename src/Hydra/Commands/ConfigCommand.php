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
    Hydra\ServiceProviders\DefaultServiceProvider,
    Hydra\Commands\Base\ConfigExecuter;

class ConfigCommand extends Command
{
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
        $serviceName = $input->getArgument('service');
        $storage = new HydraTokenStorage();
        $dialogHelper = $this->getHelperSet()->get('dialog');

        $className = 'Hydra\\Commands\\Base\\ConfigExecuter';

        $serviceClassName = 'Hydra\\Providers\\' . ucfirst($serviceName) . '\\' . ucfirst($serviceName) . 'ConfigExecuter';
        if (class_exists($serviceClassName)) {
            $className = $serviceClassName;
        }

        $config = new $className(
            $serviceName,
            $storage,
            $dialogHelper
        );

        $config->execute($input, $output);
    }
}
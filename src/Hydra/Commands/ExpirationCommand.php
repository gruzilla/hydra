<?php

namespace Hydra\Commands;

use Hydra\Hydra,
    Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider,
    Hydra\Commands\Base\ExpirationExecuter;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;


class ExpirationCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('hydra:expiration')
            ->setDescription('Lists the expiration status of access tokens');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // create and load hydra
        $storage = new HydraTokenStorage();
        $hydra = new Hydra(null, new DefaultServiceProvider($storage));

        $expiration = new ExpirationExecuter(
            $hydra,
            $storage,
            $this->getHelperSet()->get('table')
        );

        $expiration->execute($input, $output);
    }

}
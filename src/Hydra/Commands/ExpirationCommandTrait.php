<?php

namespace Hydra\Commands;

use Hydra\Hydra,
    Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider,
    Hydra\Common\Helper\ExpirationHelper;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

trait ExpirationCommandTrait
{
    protected function configure()
    {
        $this
            ->setName('hydra:expiration')
            ->setDescription('Lists the expiration status of access tokens');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $expirationHelper = $this->getExpirationHelper($input, $output);
        $tableHelper = $this->getHelperSet()->get('table');

        $expirations = $expirationHelper->getServiceExpirations();

        $tableHelper
            ->setHeaders(array('Service', 'Expiration Status'))
            ->setRows($expirations);

        $tableHelper->render($output);
    }

    protected function getExpirationHelper(InputInterface $input, OutputInterface $output)
    {
        // create and load hydra
        $storage = new HydraTokenStorage();
        $hydra = new Hydra(null, new DefaultServiceProvider($storage));

        $expirationHelper = new ExpirationHelper(
            $hydra,
            $storage
        );

        return $expirationHelper;
    }
}
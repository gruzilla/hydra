<?php

namespace Hydra\Commands\Base;

use Hydra\Hydra,
    Hydra\Jobs\Job,
    Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Helper\TableHelper;

use OAuth\Common\Token\TokenInterface;

class ExpirationExecuter
{
    public function __construct(Hydra $hydra, HydraTokenStorage $storage, TableHelper $tableHelper)
    {
        $this->hydra = $hydra;
        $this->storage = $storage;
        $this->tableHelper = $tableHelper;

        $this->hydra->load();

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // get token expiration for every service
        $services = $this->hydra->getLoadedServices();


        $rows = array();
        foreach ($services as $serviceName => $service) {
            $token = $this->storage->retrieveAccessToken($serviceName);

            $eol = $token->getEndOfLife();

            $expiration
                = $eol === TokenInterface::EOL_UNKNOWN ?
                    'unknown' :
                    $eol === TokenInterface::EOL_NEVER_EXPIRES ?
                        'never' :
                        $eol;


            $rows[] = array(
                $serviceName,
                $expiration
            );
        }


        $this->tableHelper
            ->setHeaders(array('Service', 'Expiration Status'))
            ->setRows($rows);

        $this->tableHelper->render($output);
    }
}
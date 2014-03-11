<?php

namespace Hydra\Commands;

use Hydra\Hydra,
    Hydra\Jobs\Job,
    Hydra\OAuth\HydraTokenStorage,
    Hydra\ServiceProviders\DefaultServiceProvider;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument;

use OAuth\Common\Token\TokenInterface;

class HydraExpirationCommand extends Command
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
        $hydra->load();

        // ask which service to use
        $services = $hydra->getLoadedServices();

        foreach ($services as $serviceName => $service) {
            $token = $storage->retrieveAccessToken($serviceName);

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


        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Service', 'Expiration Status'))
            ->setRows($rows);

        $table->render($output);
    }

}
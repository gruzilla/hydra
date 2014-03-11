<?php

namespace Hydra\Commands;

use Hydra\Hydra,
    Hydra\Jobs\GetJob,
    Hydra\Jobs\MappedJob,
    Hydra\Workers\SerialWorker,
    Hydra\Workers\MappedSerialWorker,
    Hydra\Metadata\DefaultMetadataFactory,
    Hydra\Mappers\ArrayMapper,
    Hydra\Commands\Base\RequestExecuter;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption;

class RequestCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('hydra:request')
            ->setDescription('Run a hydra request from the command line')
            ->addArgument(
                'service',
                InputArgument::OPTIONAL,
                'Which service you want to query (ucfirst!)'
            )
            ->addArgument(
                'request',
                InputArgument::OPTIONAL,
                'The api-request you want to send'
            )
            ->addArgument(
                'mappedClass',
                InputArgument::OPTIONAL,
                'The entity class you want to map the results to'
            )
            ->addOption(
                'raw',
                null,
                InputOption::VALUE_NONE,
                'If the raw output should be printed'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialogHelper = $this->getHelperSet()->get('dialog');

        // create and load hydra
        $metadataFactory = new DefaultMetadataFactory();
        $mapper = new ArrayMapper($metadataFactory);
        $worker = new MappedSerialWorker(null, $mapper);

        $hydra = new Hydra($worker);

        $requestExecuter = new RequestExecuter(
            $hydra,
            $metadataFactory,
            $dialogHelper
        );

        $result = $requestExecuter->execute($input, $output);

        $output->writeln(print_r($result, 1));
    }
}
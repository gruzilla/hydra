<?php

namespace Hydra\Commands;

use Hydra\Hydra,
    Hydra\Jobs\GetJob,
    Hydra\Jobs\MappedJob,
    Hydra\Workers\SerialWorker,
    Hydra\Workers\MappedSerialWorker,
    Hydra\Metadata\DefaultMetadataFactory,
    Hydra\Mappers\ArrayMapper;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption;

class HydraRequestCommand extends Command
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
        $dialog = $this->getHelperSet()->get('dialog');

        // create and load hydra
        $worker = new SerialWorker();
        $metadataFactory = null;
        if (null === $input->getOption('raw')) {
            $metadataFactory = new DefaultMetadataFactory();
            $mapper = new ArrayMapper($metadataFactory);
            $worker = new MappedSerialWorker(null, $mapper);
        }

        $hydra = new Hydra($worker);
        $hydra->load();

        // ask which service to use
        $services = $hydra->getLoadedServices();
        $serviceName = $input->getArgument('service');
        if (empty($serviceName)) {
            $serviceNames = array_keys($services);
            $serviceName = $dialog->select(
                $output,
                'Please select the service you want to send a request to:',
                $serviceNames,
                false
            );
            $serviceName = $serviceNames[$serviceName];
        }

        if (!array_key_exists($serviceName, $services)) {
            throw new \InvalidArgumentException('Service ' . $serviceName . ' not configured or not loaded!');
        }


        // Ask which request the user wants to run
        $request = $input->getArgument('request');
        if (empty($request)) {
            $request = $dialog->askAndValidate(
                $output,
                'Please specify the api-request you want to send: ',
                function ($value) {
                    if (trim($value) === '') {
                        throw new \Exception('The request can not be empty');
                    }
                    return $value;
                },
                false,
                ''
            );
        }

        // ask the user if he wants to map the results before outputting
        $mappedClass = null;
        if (null === $input->getOption('raw')) {
            $mappedClass = $input->getArgument('mappedClass');
            if (empty($mappedClass)) {
                $mappedClass = $dialog->askAndValidate(
                    $output,
                    'Please specify to which class (FQCN) you want to map the result to: ',
                    function ($value) {
                        if (trim($value) === '') {
                            throw new \Exception('The fully qualified mapped class name can not be empty');
                        }
                        if (!class_exists($value)) {
                            throw new \Exception('The class '.$value.' cannot be found by the autoloader');
                        }
                        return $value;
                    },
                    false,
                    ''
                );
            }
        }


        // run hydra
        $job = new GetJob($serviceName, $request);
        if (!empty($mappedClass)) {
            $job = MappedJob::createFrom($job, $mappedClass);

            $repositoryClass = $metadataFactory->getRepositoryClassName($mappedClass);
            $job->setEntityRepository(new $repositoryClass);
        }

        $hydra->add($job);
        $hydra->run();


        // display response
        if ($input->getOption('raw')) {
            $output->writeln(
                $job->getResult()
            );
            exit;
        }

        $output->writeln(print_r($job->getResult(), 1));
    }

}
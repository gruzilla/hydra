<?php

namespace Hydra\Commands;

use Hydra\Hydra,
    Hydra\Common\Helper\RequestHelper,
    Hydra\Metadata\DefaultMetadataFactory,
    Hydra\EntityRepository\DefaultRepositoryFactory,
    Hydra\Mappers\ArrayMapper,
    Hydra\Workers\MappedSerialWorker;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

trait RequestCommandTrait
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

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dialogHelper = $this->getHelperSet()->get('dialog');
        $requestHelper = $this->getRequestHelper();

        // ask which service to use
        $services = $requestHelper->getLoadedServices();
        $serviceName = $input->getArgument('service');
        if (empty($serviceName)) {
            $serviceNames = array_keys($services);
            $serviceName = $this->dialogHelper->select(
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
            $request = $this->dialogHelper->askAndValidate(
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
        if (false === $input->getOption('raw')) {
            $mappedClass = $input->getArgument('mappedClass');
            if (empty($mappedClass)) {
                $mappedClass = $this->dialogHelper->askAndValidate(
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


        $result = $requestHelper->runSingleGetRequest(
            $serviceName,
            $request,
            $mappedClass
        );

        $output->writeln(print_r($result, 1));
    }

    protected function getRequestHelper()
    {

        // create and load hydra
        $metadataFactory = new DefaultMetadataFactory();
        $mapper = new ArrayMapper($metadataFactory);
        $worker = new MappedSerialWorker(null, $mapper);
        $repositoryFactory = new DefaultRepositoryFactory($metadataFactory);
        $hydra = new Hydra($worker);


        return new RequestHelper(
            $hydra,
            $repositoryFactory
        );
    }
}
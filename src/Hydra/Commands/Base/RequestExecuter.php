<?php

namespace Hydra\Commands\Base;

use Hydra\Hydra,
    Hydra\Jobs\GetJob,
    Hydra\Jobs\MappedJob,
    Hydra\Interfaces\MetadataFactoryInterface;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Helper\DialogHelper;

class RequestExecuter
{
    public function __construct(Hydra $hydra, MetadataFactoryInterface $metadataFactory, DialogHelper $dialogHelper)
    {
        $this->dialogHelper = $dialogHelper;
        $this->hydra = $hydra;
        $this->metadataFactory = $metadataFactory;

        $this->hydra->load();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // ask which service to use
        $services = $this->hydra->getLoadedServices();
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


        // run hydra
        $job = new GetJob($serviceName, $request);
        if (!empty($mappedClass)) {
            $job = MappedJob::createFrom($job, $mappedClass);

            $repositoryClass = $this->metadataFactory->getRepositoryClassName($mappedClass);
            $job->setEntityRepository(new $repositoryClass);
        }

        $this->hydra->add($job);
        $this->hydra->run();

        return $job->getResult();
    }
}
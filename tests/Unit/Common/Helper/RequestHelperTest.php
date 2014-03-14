<?php

namespace Hydra\Tests\Common\Helper;

use Hydra\Hydra,
    Hydra\Common\Helper\RequestHelper;

use Hydra\Mocks\ServiceProviders\DefaultServiceProviderStub;
use Hydra\Mocks\OAuth\HydraTokenStorageStub;

class RequestHelperTest extends \PHPUnit_Framework_TestCase
{


    public function testConstructor()
    {

        $defaultServiceProviderStub = 
            $this->getMockBuilder('Hydra\ServiceProviders\DefaultServiceProvider')
                    ->setConstructorArgs(array(new HydraTokenStorageStub()))
                    ->getMock();


        $hydra = new \Hydra\Hydra(null, $defaultServiceProviderStub);

        $requestHelper = new RequestHelper(
            $hydra,
            $this->getMock('\\Hydra\\Interfaces\\RepositoryFactoryInterface')
        );

        $this->assertInstanceOf('Hydra\Common\Helper\RequestHelper', $requestHelper);
    }




}

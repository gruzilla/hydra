<?php

namespace Hydra\Tests\Common\Helper;

use Hydra\Common\Helper\RequestHelper;

use Hydra\Mocks\ServiceProviders\DefaultServiceProviderStub;

class RequestHelperTest extends \PHPUnit_Framework_TestCase
{


    public function testConstructor()
    {
        $hydra = $this->getMockBuilder('\\Hydra\\Hydra')
                        ->setConstructorArgs(array(null, new DefaultServiceProviderStub()))
                        ->getMock();

        $requestHelper = new RequestHelper(
            $hydra,
            $this->getMock('\\Hydra\\Interfaces\\RepositoryFactoryInterface')
        );

        $this->assertInstanceOf('Hydra\Common\Helper\RequestHelper', $requestHelper);
    }



}

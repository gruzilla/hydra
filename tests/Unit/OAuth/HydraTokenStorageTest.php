<?php

namespace Hydra\Tests\OAuth;

use Hydra\OAuth\HydraTokenStorage;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;

class HydraTokenStorageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var vfstreamDirectory
     */
    private $root;

    /**
     * @var YamlTokenStorage
     */
    private $storage;

    private $sampleServiceName = 'sampleService';

    /**
     * setup test environment
     */
    public function setUp()
    {
        $this->root = vfsStream::setup('root', null, array('config' => array('hydra' => array())));
        $this->storage = new HydraTokenStorage(vfsStream::url('root/config/hydra'));
    }

    /**
     * @expectedException   \RuntimeException
     */
    public function testConfigDirNotExists()
    {
        $this->root = vfsStream::setup('root');
        $this->storage = new HydraTokenStorage();
    }

    /**
     * @cover Hydra\OAuth\YamlTokenStorage::loadConfigs
     * @cover Hydra\OAuth\YamlTokenStorage::save
     */
    public function testCreateConfig()
    {
        $this->storage->setConsumerKey($this->sampleServiceName, 'sampleKey');
        $this->assertTrue($this->root->hasChild('config/hydra/' . ucfirst($this->sampleServiceName) . '.yml'));
    }

    /**
     * @cover Hydra\OAuth\YamlTokenStorage::hasConfig
     */
    public function testHasConfig()
    {
        $this->storage->setConsumerKey($this->sampleServiceName, 'sampleKey');
        $this->assertTrue($this->storage->hasConfig($this->sampleServiceName));
    }

    /**
     * @cover Hydra\OAuth\YamlTokenStorage::getLoadedServicesNames
     */
    public function testGetLoadedServicesNames()
    {
        $this->storage->setConsumerKey($this->sampleServiceName, 'sampleKey');
        $this->assertContains(ucfirst($this->sampleServiceName), $this->storage->getLoadedServicesNames());
    }

    /**
     * @cover Hydra\OAuth\HydraTokenStorage::setConsumerKey
     * @cover Hydra\OAuth\HydraTokenStorage::getConsumerKey
     */
    public function testSaveAndLoadConsumerKey()
    {
        $this->assertNull($this->storage->getConsumerKey($this->sampleServiceName));
        $this->storage->setConsumerKey($this->sampleServiceName, 'sampleKey');
        $this->assertEquals($this->storage->getConsumerKey($this->sampleServiceName), 'sampleKey');
    }
    
    /**
     * @cover Hydra\OAuth\HydraTokenStorage::setConsumerSecret
     * @cover Hydra\OAuth\HydraTokenStorage::getConsumerSecret
     */
    public function testSaveAndLoadConsumerSecret()
    {
        $this->assertNull($this->storage->getConsumerSecret($this->sampleServiceName));
        $this->storage->setConsumerSecret($this->sampleServiceName, 'sampleSecret');
        $this->assertEquals($this->storage->getConsumerSecret($this->sampleServiceName), 'sampleSecret');
    }

    /**
     * @cover Hydra\OAuth\HydraTokenStorage::getCallbackUrl
     * @cover Hydra\OAuth\HydraTokenStorage::setCallbackUrl
     */
    public function testSaveAndLoadCallbackUrl()
    {
        $this->assertNull($this->storage->getCallbackUrl($this->sampleServiceName));
        $this->storage->setCallbackUrl($this->sampleServiceName, 'http://example.com:8000');
        $this->assertEquals($this->storage->getCallbackUrl($this->sampleServiceName), 'http://example.com:8000');
    }

    /**
     * @cover Hydra\OAuth\HydraTokenStorage::getScope
     * @cover Hydra\OAuth\HydraTokenStorage::setScope
     */
    public function testSaveAndLoadScope()
    {
        $this->assertNull($this->storage->getScope($this->sampleServiceName));
        $this->storage->setScope($this->sampleServiceName, array('basic','comments'));
        $this->assertEquals($this->storage->getScope($this->sampleServiceName), array('basic','comments'));
    }

    /**
     * @expectedException   \RuntimeException
     * @cover Hydra\OAuth\HydraTokenStorage::setScope
     */
    public function testScopeMustBeArrayException()
    {
        $this->storage->setScope($this->sampleServiceName, 'no_array_given');
    }
}

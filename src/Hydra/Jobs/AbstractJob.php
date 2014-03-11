<?php

namespace Hydra\Jobs;

use Hydra\Interfaces\JobInterface;

abstract class AbstractJob implements JobInterface
{

    protected $serviceName;
    protected $request;
    protected $result;
    protected $body;
    protected $method = 'GET';
    protected $additionalHeaders = array();

    /**
     * creates a basic job with service name and request
     *
     * @param string $serviceName ucfirst name of service to request from
     * @param string $request     the actual api-request
     */
    public function __construct($serviceName, $request)
    {
        $this->setServiceName($serviceName)
            ->setRequest($request);
    }

    /**
     * Gets the value of body.
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sets the value of body.
     *
     * @param mixed $body the body
     *
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Gets the value of method.
     *
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Sets the value of method.
     *
     * @param mixed $method the method
     *
     * @return self
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Gets the value of additionalHeaders.
     *
     * @return array
     */
    public function getAdditionalHeaders()
    {
        return $this->additionalHeaders;
    }

    /**
     * Sets the value of additionalHeaders.
     *
     * @param array $additionalHeaders the additional headers
     *
     * @return self
     */
    public function setAdditionalHeaders(array $additionalHeaders)
    {
        $this->additionalHeaders = $additionalHeaders;

        return $this;
    }

    /**
     * Gets the value of serviceName.
     *
     * @return mixed
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Sets the value of serviceName.
     *
     * @param mixed $serviceName the service name
     *
     * @return self
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Gets the value of request.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the value of request.
     *
     * @param mixed $request the request
     *
     * @return self
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Gets the value of result.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Sets the value of result.
     *
     * @param mixed $result the result
     *
     * @return self
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
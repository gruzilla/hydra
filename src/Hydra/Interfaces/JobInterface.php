<?php

namespace Hydra\Interfaces;

interface JobInterface
{
    public function setServiceName($serviceName);
    public function getServiceName();
    public function setRequest($request);
    public function getRequest();
    public function setMethod($httpMethod);
    public function getMethod();
    public function setAdditionalHeaders(array $headers);
    public function getAdditionalHeaders();
    public function setBody($body);
    public function getBody();
    public function setResult($result);
    public function getResult();
}
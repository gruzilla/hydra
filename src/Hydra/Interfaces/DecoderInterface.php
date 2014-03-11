<?php

namespace Hydra\Interfaces;

interface DecoderInterface
{
    /**
     * decodes the raw return data
     *
     * @param string $data
     * @return mixed
     */
    public function decode($data);
}
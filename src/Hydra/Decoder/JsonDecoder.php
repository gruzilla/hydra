<?php

namespace Hydra\Decoder;

use Hydra\Interfaces\DecoderInterface;

class JsonDecoder implements DecoderInterface
{
    public function decode($data)
    {
        return json_decode($data, true);
    }
}
<?php

namespace Hydra\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class Decoder
{
    /**
     * @Required
     * @var string
     */
    public $class;
}

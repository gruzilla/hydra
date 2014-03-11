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

    /**
     * Gets the value of class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}

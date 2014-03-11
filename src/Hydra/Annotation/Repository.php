<?php

namespace Hydra\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class Repository
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

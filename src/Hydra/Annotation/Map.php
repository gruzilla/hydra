<?php

namespace Hydra\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class Map
{
    /**
     * @Required
     * @var string
     */
    public $source;

    /**
     * Gets the value of source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
}

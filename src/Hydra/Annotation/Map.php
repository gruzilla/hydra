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
     * @Optional
     * @var string
     */
    public $typeMapper;
}

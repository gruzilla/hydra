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
}

<?php

namespace Hydra\Commands;

trait OpenDefaultBrowserTrait
{
    protected function openLinkInDefaultBrowser($url)
    {
        if (false !== strpos(strtolower(PHP_OS), 'darwin')) {
            // mac os x
            `open "$url"`;
            return;
        }

        // only works in linux environments
        `xdg-open "$url"`;
    }
}
<?php

namespace DebugBar\DataFormatter\VarDumper;

use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * We have to extend the base HtmlDumper class in order to get access to the protected-only
 * getDumpHeader function.
 */
class DebugBarHtmlDumper extends HtmlDumper
{
    /**
     * Resets an HTML header.
     */
    #[\ReturnTypeWillChange] public function resetDumpHeader(): void
    {
        $this->dumpHeader = null;
    }

    #[\ReturnTypeWillChange] public function getDumpHeaderByDebugBar(): string {
        // getDumpHeader is protected:
        return str_replace('pre.sf-dump', '.phpdebugbar pre.sf-dump', $this->getDumpHeader());
    }
}

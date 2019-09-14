<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2019 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Debug {
}

namespace {

    use Satori\Debug\{WebVarDump, CliVarDump};

    if (!function_exists('xdump')) {
        /**
         * Dumps color information about contents of variables.
         * Similar xdebug var_dump.
         */
        function xdump(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $values);
            } else {
                new WebVarDump($call['file'], $call['line'], $values);
            }
        }
    }

    if (!function_exists('xdd')) {
        /**
         * Dumps color information about contents of variables and dies.
         * Similar xdebug var_dump.
         */
        function xdd(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $values);
            } else {
                new WebVarDump($call['file'], $call['line'], $values);
            }
            die();
        }
    }
}

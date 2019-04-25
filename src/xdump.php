<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2019 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Debug {

    /**
     * Dumps monochrome information about the contents of variables.
     * Similar xdebug var_dump.
     */
    class BaseVarDump
    {
        /**
         * @var string Indent.
         */
        protected const _INDENT = '    ';

        /**
         * @var string First line format.
         */
        protected const _FIRST_LINE = "%s:%s:\n";

        /**
         * @var string Last line format.
         */
        protected const _LAST_LINE = "\n";

        /**
         * @var string Scalar format.
         */
        protected const _SCALAR = "%s %s\n";

        /**
         * @var string String format.
         */
        protected const _STRING = "'%s' (length=%s)";

        /**
         * @var string Integer format.
         */
        protected const _INT = "%s";

        /**
         * @var string Float format.
         */
        protected const _FLOAT = "%s";

        /**
         * @var string Boolean format.
         */
        protected const _BOOL = "%s";

        /**
         * @var string NULL format.
         */
        protected const _NULL = "null";

        /**
         * @var string Resource format.
         */
        protected const _RESOURCE = "resource(%s, %s)";

        /**
         * @var string Array format.
         */
        protected const _ARRAY = "%sarray (size=%s)\n";

        /**
         * @var string Empty array format.
         */
        protected const _EMPTY_ARRAY = "%s  empty\n";

        /**
         * @var string Array item format.
         */
        protected const _ARRAY_ITEM = "%s  %s => ";

        /**
         * @var string Object format.
         */
        protected const _OBJECT = "%sobject(%s)[%s]\n";

        /**
         * @var string Object property format.
         */
        protected const _OBJECT_PROP = "%s  %s %s => ";

        /**
         * @var array<string, string> Type names to display.
         */
        protected const _TYPES = [
            'boolean' => 'boolean',
            'integer' => 'int',
            'double' => 'float',
            'string' => 'string',
        ];

        /**
         * Dumps information about the contents of variables.
         *
         * @param string $file The file path.
         * @param int    $line The line number.
         * @param array  $vars Variables.
         */
        public function __construct(string $file, int $line, array $vars)
        {
            $this->printFirstLine($file, $line);
            foreach ($vars as $var) {
                $this->printValue($var);
            }
            $this->printLastLine();
        }

        /**
         * Prints a first line.
         *
         * @param string $file The file path.
         * @param int    $line The line number.
         */
        protected function printFirstLine(string $file, int $line): void
        {
            echo sprintf(static::_FIRST_LINE, $file, $line);
        }

        /**
         * Prints a list line.
         */
        protected function printLastLine(): void
        {
            echo static::_LAST_LINE;
        }

        /**
         * Prints a value.
         *
         * @param mixed  $value  The value.
         * @param string $indent The indent.
         */
        protected function printValue($value, string $indent = ''): void
        {
            if (is_array($value)) {
                $this->printArray($value, $indent);
            } elseif (is_object($value)) {
                $this->printObject($value, $indent);
            } elseif (is_scalar($value)) {
                echo $this->formatScalar($value, $indent);
            } else {
                echo sprintf("%s\n", $this->formatValue($value));
            }
        }

        /**
         * Prints an array.
         *
         * @param array  $array  The array.
         * @param string $indent The indent.
         */
        protected function printArray(array $array, string $indent = ''): void
        {
            echo $this->formatArray($array, $indent);
            foreach ($array as $key => $value) {
                echo $this->formatArrayItem($key, $indent);
                $this->printValue($value, $indent . static::_INDENT);
            }
        }

        /**
         * Prints an object.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         */
        protected function printObject($object, string $indent = ''): void
        {
            echo $this->formatObject($object, $indent);
            $reflect = new \ReflectionClass($object);
            $properties = $reflect->getProperties(
                \ReflectionProperty::IS_PUBLIC |
                \ReflectionProperty::IS_PROTECTED |
                \ReflectionProperty::IS_PRIVATE |
                \ReflectionProperty::IS_STATIC
            );
            foreach ($properties as $property) {
                $property->setAccessible(true);
                if ($property->isPublic()) {
                    $visibility = 'public';
                } elseif ($property->isProtected()) {
                    $visibility = 'protected';
                } elseif ($property->isPrivate()) {
                    $visibility = 'private';
                }
                if ($property->isStatic()) {
                    $visibility = $visibility . ' static';
                    $value = $property->getValue();
                } else {
                    $value = $property->getValue($object);
                }
                echo $this->formatObjectProp($visibility, $property->getName(), $indent);
                $this->printValue($value, $indent . static::_INDENT);
            }
        }

        /**
         * Formats a key in an array or a name of object property.
         *
         * @param int|string $key The unique key.
         *
         * @return string
         */
        protected function formatKey($key): string
        {
            return sprintf(is_int($key) ? "%s" : "'%s'", $key);
        }

        /**
         * Formats value information.
         *
         * @param mixed $value The value.
         *
         * @return string
         */
        protected function formatValue($value): string
        {
            switch (gettype($value)) {
                case 'NULL':
                    return static::_NULL;
                case 'integer':
                    return sprintf(static::_INT, $value);
                case 'double':
                    return sprintf(static::_FLOAT, $value);
                case 'boolean':
                    return sprintf(static::_BOOL, $value ? 'true' : 'false');
                case 'string':
                    return sprintf(static::_STRING, rtrim($value, "\n"), mb_strlen($value));
                case 'resource':
                    return sprintf(static::_RESOURCE, intval($value), get_resource_type($value));
                default:
                    return sprintf("%s", $value);
            }
        }

        /**
         * Formats scalar value information.
         *
         * @param mixed  $value  The value.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatScalar($value, string $indent = ''): string
        {
            $type = static::_TYPES[gettype($value)];

            return sprintf(static::_SCALAR, $type, $this->formatValue($value));
        }

        /**
         * Formats array information.
         *
         * @param array  $array  The array.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatArray(array $array, string $indent = ''): string
        {
            $firstIndent = $indent ? "\n$indent" : $indent;
            $string = sprintf(static::_ARRAY, $firstIndent, count($array));
            if (empty($array)) {
                $string .= sprintf(static::_EMPTY_ARRAY, $indent);
            }

            return $string;
        }

        /**
         * Formats array item information.
         *
         * @param int|string  $key    The unique key.
         * @param string      $indent The indent.
         *
         * @return string
         */
        protected function formatArrayItem($key, string $indent = ''): string
        {
            return sprintf(static::_ARRAY_ITEM, $indent, $this->formatKey($key));
        }

        /**
         * Formats object information.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatObject($object, string $indent = ''): string
        {
            $indent = $indent ? "\n$indent" : $indent;

            return sprintf(static::_OBJECT, $indent, get_class($object), spl_object_id($object));
        }

        /**
         * Formats object property information.
         *
         * @param string $vsbl   The visibility.
         * @param string $name   The property name.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatObjectProp(string $vsbl, string $name, string $indent = ''): string
        {
            return sprintf(static::_OBJECT_PROP, $indent, $vsbl, $this->formatKey($name));
        }
    }

    /**
     * Dumps color information about the contents of variables for a browser.
     * Similar xdebug var_dump.
     */
    class WebVarDump extends BaseVarDump
    {
        /**
         * @see \Satori\Debug\BaseVarDump Constants for information format.
         */
        protected const _FIRST_LINE = "<pre>\n<small>%s:%s:</small>\n";
        protected const _LAST_LINE = "</pre>\n";
        protected const _SCALAR = "<small>%s</small> %s\n";
        protected const _STRING = "<font color='#cc0000'>'%s'</font> <i>(length=%s)</i>";
        protected const _INT = "<font color='#4e9a06'>%s</font>";
        protected const _FLOAT = "<font color='#f57900'>%s</font>";
        protected const _BOOL = "<font color='#75507b'>%s</font>";
        protected const _NULL = "<font color='#3465a4'>null</font>";
        protected const _RESOURCE = "<b>resource</b>(<i>%s</i>, <i>%s</i>)";
        protected const _ARRAY = "%s<b>array</b> <i>(size=%s)</i>\n";
        protected const _EMPTY_ARRAY = "%s  <i><font color='#888a85'>empty</font></i>\n";
        protected const _ARRAY_ITEM = "%s  %s <font color='#888a85'>=&gt;</font> ";
        protected const _OBJECT = "%s<b>object</b>(<i>%s</i>)[<i>%s</i>]\n";
        protected const _OBJECT_PROP = "%s  %s %s <font color='#888a85'>=&gt;</font> ";
    }

    /**
     * Dumps color information about the contents of variables for a CLI.
     * Similar xdebug var_dump.
     */
    class CliVarDump extends BaseVarDump
    {
        /**
         * @see \Satori\Debug\BaseVarDump Constants for information format.
         */
        protected const _STRING = "\x1b[0;91m'%s'\x1b[0m \x1b[3m(length=%s)\x1b[0m";
        protected const _INT = "\x1b[0;32m%s\x1b[0m";
        protected const _FLOAT = "\x1b[0;33m%s\x1b[0m";
        protected const _BOOL = "\x1b[0;95m%s\x1b[0m";
        protected const _NULL = "\x1b[0;94mnull\x1b[0m";
        protected const _RESOURCE = "resource(%s, %s)";
        protected const _ARRAY = "%s\x1b[1marray\x1b[0m \x1b[3m(size=%s)\x1b[0m\n";
        protected const _EMPTY_ARRAY = "%s  \x1b[3;2mempty\x1b[0m\n";
        protected const _ARRAY_ITEM = "%s  %s \x1b[2m=>\x1b[0m ";
        protected const _OBJECT = "%s\x1b[1mobject\x1b[0m(\x1b[3m%s\x1b[0m)[\x1b[3m%s\x1b[0m]\n";
        protected const _OBJECT_PROP = "%s  \x1b[2m%s\x1b[0m %s \x1b[2m=>\x1b[0m ";
    }
}

namespace {

    use Satori\Debug\{BaseVarDump, WebVarDump, CliVarDump};

    if (!function_exists('dump')) {
        /**
         * Dumps monochrome information about the contents of variables.
         * Similar xdebug var_dump.
         */
        function dump(...$vars): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            new BaseVarDump($call['file'], $call['line'], $vars);
        }
    }

    if (!function_exists('xdump')) {
        /**
         * Dumps color information about the contents of variables.
         * Similar xdebug var_dump.
         */
        function xdump(...$vars): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $vars);
            } else {
                new WebVarDump($call['file'], $call['line'], $vars);
            }
        }
    }

    if (!function_exists('xdd')) {
        /**
         * Dumps color information about the contents of variables and die.
         * Similar xdebug var_dump.
         */
        function xdd(...$vars): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $vars);
            } else {
                new WebVarDump($call['file'], $call['line'], $vars);
            }
            die();
        }
    }

    if (!function_exists('jsdump')) {
        /**
         * Dumps monochrome information about the contents of variables for a javascript console.
         * Similar xdebug var_dump.
         */
        function jsdump(...$vars): void
        {
            echo "<script>\n";
            ob_start();
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            new BaseVarDump($call['file'], $call['line'], $vars);
            echo "console.dir(" . json_encode(ob_get_clean()) . ")\n";
            echo "</script>\n";
        }
    }
}

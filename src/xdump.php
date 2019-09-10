<?php

/**
 * @author    Yuriy Davletshin <yuriy.davletshin@gmail.com>
 * @copyright 2019 Yuriy Davletshin
 * @license   MIT
 */

declare(strict_types=1);

namespace Satori\Debug {

    /**
     * Dumps monochrome information about contents of variables.
     * Similar xdebug var_dump.
     */
    class BaseVarDump
    {
        /**
         * @var string End of line.
         */
        public const EOL = "\n";

        /**
         * @var string CSS style sheets.
         */
        protected const _STYLE = "";

        /**
         * @var string Indent.
         */
        protected const _INDENT = "    ";

        /**
         * @var string Format of first line.
         */
        protected const _FIRST_LINE = "";

        /**
         * @var string Format of last line.
         */
        protected const _LAST_LINE = self::EOL;

        /**
         * @var string File path and line number.
         */
        protected const _FILE_PATH_AND_LINE = "%s:%s:" . self::EOL;

        /**
         * @var string Format of a scalar value.
         */
        protected const _SCALAR = "%s %s";

        /**
         * @var string Format of a string value.
         */
        protected const _STRING = "'%s' (length=%s)";

        /**
         * @var string Format of an integer value.
         */
        protected const _INT = "%s";

        /**
         * @var string Format of a float value.
         */
        protected const _FLOAT = "%s";

        /**
         * @var string Format of a boolean value.
         */
        protected const _BOOL = "%s";

        /**
         * @var string Format of NULL.
         */
        protected const _NULL = "null";

        /**
         * @var string Format of a resource.
         */
        protected const _RESOURCE = "resource(%s, %s)";

        /**
         * @var string Format of an array.
         */
        protected const _ARRAY = "%sarray (size=%s)" . self::EOL;

        /**
         * @var string Format of an empty array.
         */
        protected const _EMPTY_ARRAY = "%s  empty" . self::EOL;

        /**
         * @var string Format of array item.
         */
        protected const _ARRAY_ITEM = "%s  %s => ";

        /**
         * @var string Format of an object.
         */
        protected const _OBJECT = "%sobject(%s)[%s]" . self::EOL;

        /**
         * @var string Format of object property.
         */
        protected const _OBJECT_PROPERTY = "%s  %s %s => ";

        /**
         * @var string Visibility of object property.
         */
        protected const _VISIBILITY = "%s";

        /**
         * @var array<string, string> Names of types to display.
         */
        protected const _TYPE_NAMES = [
            'boolean' => 'boolean',
            'integer' => 'int',
            'double' => 'float',
            'string' => 'string',
        ];

        /**
         * Dumps information about the contents of variables.
         *
         * @param string $file   The file path.
         * @param int    $line   The line number.
         * @param array  $values Values.
         */
        public function __construct(string $file, int $line, array $values)
        {
            $this->printFirstLine($file, $line);
            foreach ($values as $value) {
                $this->printValue($value);
            }
            $this->printLastLine();
        }

        /**
         * Prints first line.
         *
         * @param string $file The file path.
         * @param int    $line The line number.
         */
        protected function printFirstLine(string $file, int $line): void
        {
            echo static::_STYLE;
            echo static::_FIRST_LINE;
            echo sprintf(static::_FILE_PATH_AND_LINE, $file, $line);
        }

        /**
         * Prints list line.
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
                echo sprintf("%s" . static::EOL, $this->formatValue($value));
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
        protected function printObject(object $object, string $indent = ''): void
        {
            echo $this->formatObject($object, $indent);
            $properties = (new \ReflectionClass($object))->getProperties(
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
                echo $this->formatObjectProperty($visibility, $property->getName(), $indent);
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
         * Formats information about a value.
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
                    return sprintf(static::_STRING, rtrim($value, static::EOL), mb_strlen($value));
                case 'resource':
                    return sprintf(static::_RESOURCE, intval($value), get_resource_type($value));
                default:
                    return sprintf("%s", $value);
            }
        }

        /**
         * Formats information about a scalar value.
         *
         * @param mixed  $value  The value.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatScalar($value, string $indent = ''): string
        {
            $originalTypeName = gettype($value);
            $typeName = static::_TYPE_NAMES[$originalTypeName] ?? $originalTypeName;

            return sprintf(static::_SCALAR . static::EOL, $typeName, $this->formatValue($value));
        }

        /**
         * Formats information about an array.
         *
         * @param array  $array  The array.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatArray(array $array, string $indent = ''): string
        {
            $firstIndent = $indent ? static::EOL . $indent : $indent;
            $string = sprintf(static::_ARRAY, $firstIndent, count($array));
            if (empty($array)) {
                $string .= sprintf(static::_EMPTY_ARRAY, $indent);
            }

            return $string;
        }

        /**
         * Formats information about array item.
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
         * Formats information about an object.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatObject($object, string $indent = ''): string
        {
            $indent = $indent ? static::EOL . $indent : $indent;

            return sprintf(static::_OBJECT, $indent, get_class($object), spl_object_id($object));
        }

        /**
         * Formats information about object property.
         *
         * @param string $visibility The visibility.
         * @param string $name       The property name.
         * @param string $indent     The indent.
         *
         * @return string
         */
        protected function formatObjectProperty(string $visibility, string $name, string $indent = ''): string
        {
            $visibility = sprintf(static::_VISIBILITY, $visibility);
            return sprintf(static::_OBJECT_PROPERTY, $indent, $visibility, $this->formatKey($name));
        }
    }

    /**
     * Dumps color information about contents of variables for a browser.
     * Similar xdebug var_dump.
     */
    class WebVarDump extends BaseVarDump
    {
        /**
         * @see \Satori\Debug\BaseVarDump Overridden constants.
         */
        protected const _STYLE = <<<'DAMPSTYLE'
<style>
    ._vardump ._string {
        color: #f00;
    }
    ._vardump ._int {
        color: #008000;
    }
    ._vardump ._float {
        color: #FC7F00; 
    }
    ._vardump ._bool {
        color: #f0f;
    }
    ._vardump ._null {
        color: #5c5cff; 
        3465a4
    }
    ._vardump ._arrow,
    ._vardump ._empty {
        color: #999;
    }
    ._vardump ._visibility {
        color: #888;
    }

</style>

DAMPSTYLE;

        protected const _FIRST_LINE = "<pre class='_vardump'>" . self::EOL;
        protected const _LAST_LINE = "</pre>" . self::EOL;
        protected const _FILE_PATH_AND_LINE = "<span class='_path'>%s:%s:</span>" . self::EOL;
        protected const _SCALAR = "<span class='_scalar'>%s</span> %s";
        protected const _STRING = "<span class='_string'>'%s'</span> <i>(length=%s)</i>";
        protected const _INT = "<span class='_int'>%s</span>";
        protected const _FLOAT = "<span class='_float'>%s</span>";
        protected const _BOOL = "<span class='_bool'>%s</span>";
        protected const _NULL = "<span class='_null'>null</span>";
        protected const _RESOURCE = "<b>resource</b>(<i>%s</i>, <i>%s</i>)";
        protected const _ARRAY = "%s<b>array</b> <i>(size=%s)</i>" . self::EOL;
        protected const _EMPTY_ARRAY = "%s  <i><span class='_empty'>empty</span></i>" . self::EOL;
        protected const _ARRAY_ITEM = "%s  %s <span class='_arrow'>=&gt;</span> ";
        protected const _OBJECT = "%s<b>object</b>(<i>%s</i>)[<i>%s</i>]" . self::EOL;
        protected const _OBJECT_PROPERTY = "%s  %s %s <span class='_arrow'>=&gt;</span> ";
        protected const _VISIBILITY = "<span class='_visibility'>%s</span>";
    }

    /**
     * Dumps color information about contents of variables for a CLI.
     * Similar xdebug var_dump.
     */
    class CliVarDump extends BaseVarDump
    {
        /**
         * @see \Satori\Debug\BaseVarDump Overridden constants.
         */
        protected const _STRING = "\x1b[0;91m'%s'\x1b[0m \x1b[3m(length=%s)\x1b[0m";
        protected const _INT = "\x1b[0;32m%s\x1b[0m";
        protected const _FLOAT = "\x1b[0;33m%s\x1b[0m";
        protected const _BOOL = "\x1b[0;95m%s\x1b[0m";
        protected const _NULL = "\x1b[0;94mnull\x1b[0m";
        protected const _RESOURCE = "resource(%s, %s)";
        protected const _ARRAY = "%s\x1b[1marray\x1b[0m \x1b[3m(size=%s)\x1b[0m" . self::EOL;
        protected const _EMPTY_ARRAY = "%s  \x1b[3;2mempty\x1b[0m" . self::EOL;
        protected const _ARRAY_ITEM = "%s  %s \x1b[2m=>\x1b[0m ";
        protected const _OBJECT = "%s\x1b[1mobject\x1b[0m(\x1b[3m%s\x1b[0m)[\x1b[3m%s\x1b[0m]" . self::EOL;
        protected const _OBJECT_PROPERTY = "%s  %s %s \x1b[2m=>\x1b[0m ";
        protected const _VISIBILITY = "\x1b[2m%s\x1b[0m";
    }
}

namespace {

    use Satori\Debug\{BaseVarDump, WebVarDump, CliVarDump};

    if (!function_exists('_dump')) {
        /**
         * Dumps monochrome information about contents of variables.
         * Similar xdebug var_dump.
         */
        function _dump(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            new BaseVarDump($call['file'], $call['line'], $values);
        }
    }

    if (!function_exists('dump')) {
        /**
         * Dumps color information about contents of variables.
         * Similar xdebug var_dump.
         */
        function dump(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $values);
            } else {
                new WebVarDump($call['file'], $call['line'], $values);
            }
        }
    }

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

    if (!function_exists('dd')) {
        /**
         * Dumps color information about contents of variables and dies.
         * Similar xdebug var_dump.
         */
        function dd(...$values): void
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

    if (!function_exists('jsdump')) {
        /**
         * Dumps monochrome information about contents of variables for a javascript console.
         * Similar xdebug var_dump.
         */
        function jsdump(...$values): void
        {
            echo "<script>" . WebVarDump::EOL;
            ob_start();
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            new BaseVarDump($call['file'], $call['line'], $values);
            echo "console.dir(" . json_encode(ob_get_clean()) . ")" . WebVarDump::EOL;
            echo "</script>" . WebVarDump::EOL;
        }
    }
}

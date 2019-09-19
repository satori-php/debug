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
        protected const _STYLE = '';

        /**
         * @var string Indent.
         */
        protected const _INDENT = '    ';

        /**
         * @var string Format of output beginning.
         */
        protected const _TOP = '';

        /**
         * @var string Format of output end.
         */
        protected const _BOTTOM = '';

        /**
         * @var string File path and line number.
         */
        protected const _FILE_PATH_AND_LINE = '%s:%s:';

        /**
         * @var string Format of dump beginning.
         */
        protected const _DUMP_TOP = '';

        /**
         * @var string Format of dump end.
         */
        protected const _DUMP_BOTTOM = '';

        /**
         * @var string Format of output for empty arguments.
         */
        protected const _EMPTY = 'empty';

        /**
         * @var string Format of a scalar value.
         */
        protected const _SCALAR = '%s %s';

        /**
         * @var string Format of a string value.
         */
        protected const _STRING = '\'%s\' (length=%s)';

        /**
         * @var string Format of an integer value.
         */
        protected const _INT = '%s';

        /**
         * @var string Format of a float value.
         */
        protected const _FLOAT = '%s';

        /**
         * @var string Format of a boolean value.
         */
        protected const _BOOL = '%s';

        /**
         * @var string Format of NULL.
         */
        protected const _NULL = 'null';

        /**
         * @var string Format of a resource.
         */
        protected const _RESOURCE = 'resource(%s, %s)';

        /**
         * @var string Format of an array.
         */
        protected const _ARRAY = '%sarray (size=%s)';

        /**
         * @var string Format of an empty array.
         */
        protected const _EMPTY_ARRAY = '%s  empty';

        /**
         * @var string Format of array key.
         */
        protected const _ARRAY_KEY = '%s  %s => ';

        /**
         * @var string Format of an object.
         */
        protected const _OBJECT = '%sobject(%s)[%s]';

        /**
         * @var string Format of output for recursion.
         */
        protected const _RECURSION = '%s*RECURSION* %s...';

        /**
         * @var string Format of object key.
         */
        protected const _OBJECT_KEY = '%s  %s => ';

        /**
         * @var string Format of object property.
         */
        protected const _PROPERTY = '%s \'%s\'';

        /**
         * @var string Visibility of object property.
         */
        protected const _VISIBILITY = '%s';

        /**
         * @var string Format of output for excess nesting.
         */
        protected const _MORE = '%s  ...';

        /**
         * @var array Names of types to display.
         */
        protected const _TYPE_NAMES = [
            'boolean' => 'boolean',
            'integer' => 'int',
            'double' => 'float',
            'string' => 'string',
        ];

        /**
         * @var int Max number of nested levels.
         */
        protected $maxNestedLevels = 10;

        /**
         * @var int Number of current level.
         */
        protected $currentLevel = 0;

        /**
         * @var object[] Processed objects.
         */
        protected $objects = [];

        /**
         * Dumps information about the contents of variables.
         *
         * @param string     $file   The file path.
         * @param int        $line   The line number.
         * @param mixed[]    $values Values.
         * @param array|null $config Optional configuration.
         */
        public function __construct(string $file, int $line, array $values, array $config = null)
        {
            $this->configure($config ?? []);
            $this->printHeader($file, $line);
            $this->printBody($values);
            $this->printFooter();
        }

        /**
         * Configures *VarDump.
         *
         * @param array $config The configuration.
         *
         * @return void
         */
        protected function configure(array $config): void
        {
            if (isset($config['levels'])) {
                $this->setMaxNestedLevels($config['levels']);
            }
        }

        /**
         * Sets max number of nested levels.
         *
         * @param int $number The number of nested levels.
         *
         * @throws \RangeException If the number is less than 1.
         *
         * @return void
         */
        protected function setMaxNestedLevels(int $number): void
        {
            if ($number > 0) {
                $this->maxNestedLevels = $number;
            } else {
                throw new \RangeException(sprintf("Wrong number of levels: %s", $number));
            }
        }
        /**
         * Prints header.
         *
         * @param string $file The file path.
         * @param int    $line The line number.
         *
         * @return void
         */
        protected function printHeader(string $file, int $line): void
        {
            echo static::_STYLE;
            echo static::_TOP;
            echo sprintf(static::_FILE_PATH_AND_LINE, $file, $line) . static::EOL;
            echo static::_DUMP_TOP;
        }

        /**
         * Prints body.
         *
         * @param mixed[] $values Values.
         *
         * @return void
         */
        public function printBody(array $values): void
        {
            if (empty($values)) {
                echo static::_EMPTY . static::EOL;
            }
            foreach ($values as $value) {
                $this->currentLevel = 0;
                $this->objects = [];
                $this->printValue($value);
            }
        }

        /**
         * Prints footer.
         *
         * @return void
         */
        protected function printFooter(): void
        {
            echo static::_DUMP_BOTTOM;
            echo static::_BOTTOM . static::EOL;
        }

        /**
         * Prints a value.
         *
         * @param mixed  $value  The value.
         * @param string $indent The indent.
         *
         * @return void
         */
        protected function printValue($value, string $indent = ''): void
        {
            if (is_array($value)) {
                $this->printArray($value, $indent);
            } elseif (is_object($value)) {
                $this->printObject($value, $indent);
            } elseif (is_scalar($value)) {
                echo $this->formatScalarValue($value, $indent) . static::EOL;
            } else {
                echo $this->formatValue($value) . static::EOL;
            }
        }

        /**
         * Prints an array.
         *
         * @param array  $array  The array.
         * @param string $indent The indent.
         *
         * @return void
         */
        protected function printArray(array $array, string $indent = ''): void
        {
            echo $this->formatArrayHeader($array, $indent) . static::EOL;
            if (empty($array)) {
                echo sprintf(static::_EMPTY_ARRAY, $indent) . static::EOL;
                return;
            } elseif ($this->currentLevel >= $this->maxNestedLevels) {
                echo sprintf(static::_MORE, $indent) . static::EOL;
                return;
            }
            $this->printArrayItems($array, $indent);
        }

        /**
         * Prints array items.
         *
         * @param array  $array  The array.
         * @param string $indent The indent.
         *
         * @return void
         */
        protected function printArrayItems(array $array, string $indent): void
        {
            $this->currentLevel++;
            foreach ($array as $key => $value) {
                echo $this->formatArrayKey($key, $indent);
                $this->printValue($value, $indent . static::_INDENT);
            }
            $this->currentLevel--;
        }

        /**
         * Prints an object.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         *
         * @return void
         */
        protected function printObject(object $object, string $indent = ''): void
        {
            if (in_array($object, $this->objects)) {
                echo $this->formatRecursionHeader($object, $indent) . static::EOL;
                return;
            }
            echo $this->formatObjectHeader($object, $indent) . static::EOL;
            if ($this->currentLevel >= $this->maxNestedLevels) {
                echo sprintf(static::_MORE, $indent) . static::EOL;
                return;
            }
            $this->objects[$this->currentLevel] = $object;
            $this->printObjectProperties($object, $indent);
            unset($this->objects[$this->currentLevel]);
        }

        /**
         * Prints object properties.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         *
         * @return void
         */
        protected function printObjectProperties(object $object, string $indent): void
        {
            $this->currentLevel++;
            $properties = $this->getReflectionProperties($object);
            foreach ($properties as $property) {
                $visibility = $this->getPropertyVisibility($property);
                if ($property->isStatic()) {
                    $visibility .= ' static';
                    $value = $property->getValue();
                } else {
                    $value = $property->getValue($object);
                }
                $visibility = sprintf(static::_VISIBILITY, $visibility);
                $key = sprintf(static::_PROPERTY, $visibility, $property->getName());
                echo $this->formatObjectKey($key, $indent);
                $this->printValue($value, $indent . static::_INDENT);
            }
            $this->currentLevel--;
        }

        /**
         * Returns an array of reflection properties.
         *
         * @param object $object The object.
         *
         * @return array
         */
        protected function getReflectionProperties(object $object): array
        {
            $reflection = new \ReflectionClass($object);
            return $reflection->getProperties(
                \ReflectionProperty::IS_PUBLIC |
                \ReflectionProperty::IS_PROTECTED |
                \ReflectionProperty::IS_PRIVATE |
                \ReflectionProperty::IS_STATIC
            );
        }

        /**
         * Returns property visibility.
         *
         * @param \ReflectionProperty $property Reflection of the property.
         *
         * @return string
         */
        protected function getPropertyVisibility(\ReflectionProperty $property): string
        {
            $property->setAccessible(true);
            if ($property->isPublic()) {
                $visibility = 'public';
            } elseif ($property->isProtected()) {
                $visibility = 'protected';
            } elseif ($property->isPrivate()) {
                $visibility = 'private';
            }

            return $visibility;
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
                    return sprintf('%s', $value);
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
        protected function formatScalarValue($value, string $indent = ''): string
        {
            $originalTypeName = gettype($value);
            $typeName = static::_TYPE_NAMES[$originalTypeName] ?? $originalTypeName;

            return sprintf(static::_SCALAR, $typeName, $this->formatValue($value));
        }

        /**
         * Formats information about an array.
         *
         * @param array  $array  The array.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatArrayHeader(array $array, string $indent = ''): string
        {
            $firstIndent = $indent ? static::EOL . $indent : $indent;

            return sprintf(static::_ARRAY, $firstIndent, count($array));
        }

        /**
         * Formats information about array item.
         *
         * @param int|string $key    The unique key.
         * @param string     $indent The indent.
         *
         * @return string
         */
        protected function formatArrayKey($key, string $indent = ''): string
        {
            $key = sprintf(is_int($key) ? '%s' : "'%s'", $key);

            return sprintf(static::_ARRAY_KEY, $indent, $key);
        }

        /**
         * Formats information about an object.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatObjectHeader(object $object, string $indent = ''): string
        {
            $indent = $indent ? static::EOL . $indent : $indent;

            return sprintf(static::_OBJECT, $indent, get_class($object), spl_object_id($object));
        }

        /**
         * Formats information about an object.
         *
         * @param object $object The object.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatRecursionHeader(object $object, string $indent = ''): string
        {
            $indent = $indent ? static::EOL . $indent : $indent;

            return sprintf(
                static::_RECURSION,
                $indent,
                sprintf(static::_OBJECT, '', get_class($object), spl_object_id($object))
            );
        }

        /**
         * Formats information about object property.
         *
         * @param string $key    The property key.
         * @param string $indent The indent.
         *
         * @return string
         */
        protected function formatObjectKey(string $key, string $indent = ''): string
        {
            return sprintf(static::_OBJECT_KEY, $indent, $key);
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
    ._vardump {
        margin-bottom: 1em;
        font-family: monospace;
    }
    ._vardump pre {
        margin: 0;
    }
    ._vardump ._string {
        color: #f00;
    }
    ._vardump ._int {
        color: #008000;
    }
    ._vardump ._float {
        color: #fc7f00; 
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
    ._vardump ._recursion {
        color: #008000;
    }

</style>

DAMPSTYLE;

        protected const _TOP = '<div class="_vardump">' . self::EOL;
        protected const _BOTTOM = '</div>';
        protected const _FILE_PATH_AND_LINE = '<div class="_path">%s:%s:</div>';
        protected const _DUMP_TOP = '<pre>' . self::EOL;
        protected const _DUMP_BOTTOM = '</pre>' . self::EOL;
        protected const _EMPTY = '<i class="_empty">empty</i>';
        protected const _SCALAR = '<span class="_scalar">%s</span> %s';
        protected const _STRING = '<span class="_string">\'%s\'</span> <i>(length=%s)</i>';
        protected const _INT = '<span class="_int">%s</span>';
        protected const _FLOAT = '<span class="_float">%s</span>';
        protected const _BOOL = '<span class="_bool">%s</span>';
        protected const _NULL = '<span class="_null">null</span>';
        protected const _RESOURCE = '<b>resource</b>(<i>%s</i>, <i>%s</i>)';
        protected const _ARRAY = '%s<b>array</b> <i>(size=%s)</i>';
        protected const _EMPTY_ARRAY = '%s  <i class="_empty">empty</i>';
        protected const _ARRAY_KEY = '%s  %s <span class="_arrow">=&gt;</span> ';
        protected const _OBJECT = '%s<b>object</b>(<i>%s</i>)[<i>%s</i>]';
        protected const _OBJECT_KEY = '%s  %s <span class="_arrow">=&gt;</span> ';
        protected const _VISIBILITY = '<span class="_visibility">%s</span>';
        protected const _RECURSION = '%s<i class="_recursion">*RECURSION*</i> %s...';
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
        protected const _EMPTY = "\x1b[3;2mempty\x1b[0m";
        protected const _STRING = "\x1b[0;91m'%s'\x1b[0m \x1b[3m(length=%s)\x1b[0m";
        protected const _INT = "\x1b[0;32m%s\x1b[0m";
        protected const _FLOAT = "\x1b[0;33m%s\x1b[0m";
        protected const _BOOL = "\x1b[0;95m%s\x1b[0m";
        protected const _NULL = "\x1b[0;94mnull\x1b[0m";
        protected const _RESOURCE = "resource(%s, %s)";
        protected const _ARRAY = "%s\x1b[1marray\x1b[0m \x1b[3m(size=%s)\x1b[0m";
        protected const _EMPTY_ARRAY = "%s  \x1b[3;2mempty\x1b[0m";
        protected const _ARRAY_KEY = "%s  %s \x1b[2m=>\x1b[0m ";
        protected const _OBJECT = "%s\x1b[1mobject\x1b[0m(\x1b[3m%s\x1b[0m)[\x1b[3m%s\x1b[0m]";
        protected const _OBJECT_KEY = "%s  %s \x1b[2m=>\x1b[0m ";
        protected const _VISIBILITY = "\x1b[2m%s\x1b[0m";
        protected const _RECURSION = "%s\x1b[3;32m*RECURSION*\x1b[0m %s...";
    }
}

namespace {

    use Satori\Debug\{BaseVarDump, WebVarDump, CliVarDump};

    if (!function_exists('_dump')) {
        /**
         * Dumps monochrome information about contents of variables.
         * Similar xdebug var_dump.
         *
         * @param mixed ...$values One or more values.
         *
         * @return void
         */
        function _dump(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            new BaseVarDump($call['file'], $call['line'], $values);
        }
    }

    if (!function_exists('dump')) {
        /**
         * Dumps color information about contents of variables.
         * Similar xdebug var_dump.
         *
         * @param mixed ...$values One or more values.
         *
         * @return void
         */
        function dump(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
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
         *
         * @param mixed ...$values One or more values.
         *
         * @return void
         */
        function dd(...$values): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $values);
            } else {
                new WebVarDump($call['file'], $call['line'], $values);
            }
            die();
        }
    }

    if (!function_exists('xdump')) {
        /**
         * Dumps color information about contents of variables.
         * Similar xdebug var_dump.
         *
         * @param mixed[] $values The array with values [$var1, $var2, $var3].
         * @param array   $config The configuration ['levels' => 5].
         *
         * @return void
         */
        function xdump(array $values, array $config = null): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $values, $config);
            } else {
                new WebVarDump($call['file'], $call['line'], $values, $config);
            }
        }
    }

    if (!function_exists('xdd')) {
        /**
         * Dumps color information about contents of variables and dies.
         * Similar xdebug var_dump.
         *
         * @param mixed[] $values The array with values [$var1, $var2, $var3].
         * @param array   $config The configuration ['levels' => 5].
         *
         * @return void
         */
        function xdd(array $values, array $config = null): void
        {
            $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
            if (php_sapi_name() === 'cli') {
                new CliVarDump($call['file'], $call['line'], $values, $config);
            } else {
                new WebVarDump($call['file'], $call['line'], $values, $config);
            }
            die();
        }
    }

    if (!function_exists('jsdump')) {
        /**
         * Dumps monochrome information about contents of variables for a javascript console.
         * Similar xdebug var_dump.
         *
         * @param mixed ...$values One or more values.
         *
         * @return void
         */
        function jsdump(...$values): void
        {
            echo '<script>' . WebVarDump::EOL;
            ob_start();
            $call = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            new BaseVarDump($call['file'], $call['line'], $values);
            echo 'console.dir(' . json_encode(ob_get_clean()) . ')' . WebVarDump::EOL;
            echo '</script>' . WebVarDump::EOL;
        }
    }
}

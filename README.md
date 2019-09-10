# Debug tools

Requires PHP 7.2

## Usage

### VarDump

```php
class Personage
{
    protected $name;
    private $place;
    public static $something = 'important';

    public function __construct(string $name, string $place)
    {
        $this->name = $name;
        $this->place = $place;
    }
}

$alice = new Personage('Alice', 'Wonderland');

$var = [1, 'r' => 2.0000001, '3', true, null, ['x' => 3, 'y' => 5], $alice];

$f = fopen('./books/AliceInWonderland.txt', 'r');

// var_dump
dump($var, [], '', null, $f);

fclose($f);
```

## License
MIT License
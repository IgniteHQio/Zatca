<?php

namespace Malik12tree\ZATCA\Utils;

/*
**
* Implements the abstract base for all enum types
* @see http://stackoverflow.com/a/2324746/1003020
* @see http://stackoverflow.com/a/254543/1003020
*
* Example of a typical enum:
*
* class DayOfWeek extends Enum
* {
* const Sunday = 0;
* const Monday = 1;
* const Tuesday = 2;
* const Wednesday = 3;
* const Thursday = 4;
* const Friday = 5;
* const Saturday = 6;
* }
*
* Usage examples:
*
* $monday = DayOfWeek::Monday // (int) 1
* DayOfWeek::isValidName('Monday') // (bool) true
* DayOfWeek::isValidName('monday', $strict = true) // (bool) false
* DayOfWeek::isValidValue(0) // (bool) true
* DayOfWeek::fromString('Monday') // (int) 1
* DayOfWeek::toString(DayOfWeek::Tuesday) // (string) "Tuesday"
* DayOfWeek::toString(5) // (string) "Friday"
*/

abstract class Enum7
{
    private static $constCacheArray;

    public static function cases()
    {
        if (null == self::$constCacheArray) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }

        return self::$constCacheArray[$calledClass];
    }

    public static function isValidName($name, $strict = false)
    {
        $constants = self::cases();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));

        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value, $strict = true)
    {
        $values = array_values(self::cases());

        return in_array($value, $values, $strict);
    }

    public static function fromString($name)
    {
        if (self::isValidName($name, true)) {
            $constants = self::cases();

            return $constants[$name];
        }

        return false;
    }

    public static function toString($value)
    {
        if (self::isValidValue($value, true)) {
            return array_search($value, self::cases());
        }

        return false;
    }
}

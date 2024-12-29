<?php

namespace Malik12tree\ZATCA\Utils\Encoding;

class TLV
{
    public static function encode($tag, $value)
    {
        $value = strval($value);

        $valueHex = $value;
        $length = strlen($valueHex);
        if ($length > 0xFF) {
            throw new \Exception("Data field '{$value}' is too long! (max: 255)");
        }

        $tagHex = chr($tag);
        $lengthHex = chr($length);

        return "{$tagHex}{$lengthHex}{$valueHex}";
    }

    public static function encodeAll($valueByTag)
    {
        $hex = '';
        foreach ($valueByTag as $tag => $value) {
            $hex .= self::encode($tag, $value);
        }

        return $hex;
    }

    public static function decode($hex)
    {
        $valueByTag = [];
        for ($i = 0; $i < strlen($hex); $i += $length + 2) {
            $tag = ord($hex[$i]);
            $length = ord($hex[$i + 1]);
            $value = substr($hex, $i + 2, $length);

            $valueByTag[$tag] = $value;
        }

        return $valueByTag;
    }
}

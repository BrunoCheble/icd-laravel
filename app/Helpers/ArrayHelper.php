<?php
namespace App\Helpers;

class ArrayHelper
{
    /**
     * Transforma um array de objetos em um array simples de pares index => value.
     *
     * @param array $objects
     * @param string $indexField
     * @param string $valueField
     * @return array
     */
    public static function toKeyValueArray($objects, $indexField, $valueField)
    {
        $array = [];
        foreach ($objects as $object) {
            $array[$object->{$indexField}] = $object->{$valueField};
        }
        return $array;
    }

    public static function groupBy($array, $key)
    {
        $grouped = [];
        foreach ($array as $item) {
            // create a hash for each group index
            $grouped[md5($item[$key])][] = $item;
        }
        return $grouped;
    }
}

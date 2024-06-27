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
}

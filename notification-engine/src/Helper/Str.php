<?php

namespace Demo\Project\Helper;

class Str
{
    /**
     * Convert a string to kebab case
     *
     * @param string $value
     * @return string
     */
    public static function kebab(string $value): string
    {
        $delimiter = '-';
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
    }
}

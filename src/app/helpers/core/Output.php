<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 22/07/15
 * Time: 13:28
 */

namespace app\helpers\core;


class Output
{
    public static function htmlspecialchars($string, $flags = null) {
        if ($flags === null) {
            $flags = ENT_COMPAT | ENT_HTML401;
        }
        return htmlspecialchars($string, $flags, \Base::instance()->get('ENCODING'));
    }

    public static function rgbToCss(array $color, $hex = false, $alpha = null) {
        if ($hex) {
            if ($alpha) {
                return 'rgba(' . implode(', ', $color) . ', ' . $alpha . ')';
            }
            return 'rgb(' . implode(', ', $color) . ')';
        }
        return '#' . dechex($color[0]) . dechex($color[1]) . dechex($color[2]);
    }

    public static function jsQuoteEscape($string, $quote="'") {
        return str_replace($quote, '\\'.$quote, $string);
    }
}
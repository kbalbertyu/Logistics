<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/19/2018
 * Time: 7:42 PM
 */

namespace Application\Model;


class Tools {

    public static function contains($str, array $args) {
        $str = strtoupper(trim($str));
        if (empty($str)) {
            return false;
        }
        foreach ($args as $arg) {
            $arg = strtoupper($arg);
            if (strpos($str, $arg) === false) {
                return false;
            }
        }
        return true;
    }

    public static function containsAny($str, array $args) {
        $str = strtoupper(trim($str));
        if (empty($str)) {
            return false;
        }
        foreach ($args as $arg) {
            $arg = strtoupper($arg);
            if (strpos($str, $arg) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function startWith($str, $keyword) {
        $str = strtolower(trim($str));
        if (empty($str)) {
            return false;
        }
        return substr($str, 0, strlen($keyword)) == strtolower($keyword);
    }

    public static function startWithAny($str, array $keywords) {
        $str = strtolower(trim($str));
        if (empty($str)) {
            return false;
        }
        foreach ($keywords as $keyword) {
            if (self::startWith($str, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
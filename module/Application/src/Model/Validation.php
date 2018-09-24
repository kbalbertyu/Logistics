<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/23/2018
 * Time: 7:42 AM
 */

namespace Application\Model;


class Validation {

    private static $errors = [];

    public function addError(string $message) {
        self::$errors[] = $message;
    }

    public function stringify() {
        return implode(PHP_EOL, self::$errors);
    }

    public function isValid() {
        return empty(self::$errors);
    }
}
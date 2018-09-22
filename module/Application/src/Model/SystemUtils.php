<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 8/6/2018
 * Time: 8:04 PM
 */

namespace Application\Model;



class SystemUtils {

    public static function isProcessRunning($pid) {
        return file_exists('/proc/' . $pid . '/status');
    }

    public static function stopProcess($pid) {
        $command = 'kill -9 ' . $pid;
        exec($command);
        return !self::isProcessRunning($pid);
    }

    public static function runConsoleCommand($cmd) {
        $cmd = '/opt/bitnami/php/bin/php ' . ZF_PATH . '/public/index.php ' . $cmd . ' &';
        exec($cmd);
        return true;
    }
}
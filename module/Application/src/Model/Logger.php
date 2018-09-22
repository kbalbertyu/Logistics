<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 8/2/2018
 * Time: 5:54 PM
 */

namespace Application\Model;


use Zend\Console\ColorInterface;
use Zend\Log\Formatter\Simple;
use Zend\Log\Writer\Stream;
use Zend\Log\Logger as ZendLogger;
use Zend\Console\Adapter\AdapterInterface as Console;

class Logger {

    /**
     * @var ZendLogger
     */
    private $logger;

    /**
     * @var Console
     */
    private $console;

    public function __construct() {
        $this->initLogger();
    }

    private function initLogger() {
        if (strcmp(php_sapi_name(), 'cli') == 0) {
            $file = ZF_PATH . '/data/logs-console/' . date('Y/m/d') . '.log';
        } else {
            $file = ZF_PATH . '/data/logs/' . date('Y/m/d') . '.log';
        }

        $dir = dirname($file);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $writer = new Stream($file);
        $formatter = new Simple('%timestamp% %priorityName%: %message%', 'Y-m-d H:i:s');
        $writer->setFormatter($formatter);
        $logger = new ZendLogger();
        $logger->addWriter($writer);
        $logger->addProcessor(new LoggerProcessor());
        $this->logger = $logger;
    }

    public function setConsole(Console $console) {
        $this->console = $console;
    }

    public function err($text) {
        $text = $this->appendTrace($text);
        $this->errConsole($text);
        if (defined('NO_LOG')) {
            return;
        }
        $this->logger->err($text);
    }

    public function info($text) {
        $text = $this->appendTrace($text, 2);
        $this->infoConsole($text);
        if (defined('NO_LOG')) {
            return;
        }
        $this->logger->info($text);
    }

    public function warn($text) {
        $text = $this->appendTrace($text, 3);
        $this->warnConsole($text);
        if (defined('NO_LOG')) {
            return;
        }
        $this->logger->warn($text);
    }

    public function debug($text) {
        $this->warnConsole($text);
        $text = $this->appendTrace($text);
        if (defined('NO_LOG')) {
            return;
        }
        $this->logger->debug($text);
    }

    public function emerg($text) {
        $text = $this->appendTrace($text);
        $this->errConsole($text);
        if (defined('NO_LOG')) {
            return;
        }
        $this->logger->emerg($text);
    }

    private function warnConsole($text) {
        if ($this->console == null) {
            return;
        }
        $this->console->writeLine($this->formatConsoleLogText($text, 'warn'), ColorInterface::YELLOW);
    }

    private function errConsole($text) {
        if ($this->console == null) {
            return;
        }
        $this->console->writeLine($this->formatConsoleLogText($text, 'err'), ColorInterface::RED, ColorInterface::LIGHT_YELLOW);
    }

    private function infoConsole($text) {
        if ($this->console == null) {
            return;
        }
        $this->console->writeLine($this->formatConsoleLogText($text, 'info'), ColorInterface::GREEN);
    }

    private function formatConsoleLogText($text, $level): string {
        $text = date('Y-m-d H:i:s') . ': [' . ucwords($level) . '] ' . $text;
        return $text;
    }

    private function appendTrace($text, $traceLimit = 4) {
        $traceList = debug_backtrace(0, $traceLimit);
        array_shift($traceList);
        $traces = [];
        foreach ($traceList as $trace) {
            $file = str_replace('\\', '/', $trace['file']);
            $file = str_replace(ZF_PATH, '', $file);
            if (substr($file, 0, 7) != '/module') {
                break;
            }
            $file = basename($file);
            $traces[] = sprintf('%s, line: %d', $file, $trace['line']);
        }
        $text .= ', File Trace:' . implode(', ', $traces);
        return $text;
    }
}
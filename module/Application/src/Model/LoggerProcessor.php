<?php
namespace Application\Model;

use Zend\Log\Processor\Backtrace;

class LoggerProcessor extends Backtrace {
    
    public function process(array $event) {
        $trace = $this->getBacktrace();
        
        array_shift($trace); // ignore $this->getBacktrace();
        array_shift($trace); // ignore $this->process()
        
        $i = 0;
        while (isset($trace[$i]['class'])
            && false !== strpos($trace[$i]['class'], $this->ignoredNamespace)
            ) {
                $i++;
            }
            
            $file = null;
            if (isset($trace[$i - 1]['file'])) {
                $file = str_replace('\\', '/', $trace[$i - 1]['file']);
                $file = str_replace(ZF_PATH . '/module/', '', $file);
            }
            $function = isset($trace[$i]['function']) ? $trace[$i]['function'] : null;
            $line = isset($trace[$i - 1]['line']) ? $trace[$i - 1]['line'] : null;
            
            $origin = [
                'file'     => $file . '::' . $function . ' Line: ' . $line
            ];
            
            $extra = $origin;
            if (isset($event['extra'])) {
                $extra = array_merge($origin, $event['extra']);
            }
            $event['extra'] = $extra;
            
            return $event;
    }
}
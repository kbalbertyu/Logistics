<?php
namespace Application\Model;

use Zend\Hydrator\ObjectProperty;
use Zend\Hydrator\Strategy\ExplodeStrategy;
use Zend\Json\Json;

/**
 *
 * @author ACC22-8
 *
 */
class BaseModel {

    public $count;

    /**
     * @param array $data
     * @return array
     */
    public function removeExtraColumns($data) {
        $properties = Tools::getProperties(get_class($this));
        foreach ($data as $key => $value) {
            if (!in_array($key, $properties)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    public function exchangeArray(array $data) {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function toArray() {
        $extractor =  new ObjectProperty();
        return $extractor->extract($this);
    }

    public function __(string $key, $parameters = []) {
        Tools::translate($key, $parameters);
    }

    public static function formatNumericColumns(&$set, $numericColumns) {
        foreach ($numericColumns as $column) {
            if (empty($set[$column])) {
                $set[$column] = 0;
            }
        }
    }

    protected static function formatFieldNameMappings($fields) {
        $data = array();
        foreach ($fields as $field) {
            $data[$field] = self::deliciousCamelcase($field);
        }

        return $data;
    }

    public static function deliciousCamelcase($str) {
        $regex = '/(?<=[a-z])(?=[A-Z]) | (?<=[A-Z])(?=[A-Z][a-z])/x';
        $data = preg_split($regex, ucfirst($str));
        $explode = new ExplodeStrategy(' ');
        $formattedStr = $explode->extract($data);
        return $formattedStr;
    }

    /**
     *
     * @return Logger
     */
    public static function getLogger() {
        if (self::$logger != null) {
            return self::$logger;
        }
        self::$logger = new Logger();
        return self::$logger;
    }

    /**
     * Dumping variable to file
     * @param $var
     * @param string $name
     * @param bool $exportJson
     * @param bool $allowConsole
     * @return string|void
     */
    public static function dumpVariable($var, $name = '', $exportJson = false, $allowConsole = false) {
        if (defined('NO_VARIABLE_DUMP')) {
            return;
        }
        ob_start();
        var_dump($var);
        $result = ob_get_clean();

        if (strcmp(php_sapi_name(), 'cli') == 0) {
            if (!$allowConsole) {
                return;
            }
            $basePath = ZF_PATH . '/data/dump-console/' . date('Y/m/d');
        } else {
            $basePath = ZF_PATH . '/data/dump/' . date('Y/m/d');
        }

        $fileName = $basePath . '/' . (empty($name) ? '' : $name) . '-' . str_replace(' ', '-', microtime()) . '.html';

        $path = dirname($fileName);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($fileName, $result);

        if ($exportJson) {
            $jsonFileName = str_replace('.html', '.json', $fileName);
            file_put_contents($jsonFileName, is_string($var) ? $var : Json::encode($var));
        }

        return str_replace(ZF_PATH, '', $fileName);
    }

    public static function checkFileLines($file) {
        $lines = 0;
        if ($fh = fopen($file, 'r')) {
            while (!feof($fh)) {
                if (fgets($fh)) {
                    $lines++;
                }
            }
            fclose($fh);
        }
        return $lines;
    }

    public static function readLastLines($file, $n = null) {
        if (!$fp = fopen($file, 'r')) {
            return "File cannot be open.";
        }
        $pos = -2;
        $eof = "";
        $str = "";
        while ($n > 0) {
            while ($eof != "\n") {
                if (!fseek($fp, $pos, SEEK_END)) {
                    $eof = fgetc($fp);
                    $pos--;
                } else {
                    break;
                }
            }
            $str = fgets($fp) . $str;
            $eof = "";
            $n--;
        }
        return $str;
    }

    /**
     * @var Logger
     */
    private static $logger;
}
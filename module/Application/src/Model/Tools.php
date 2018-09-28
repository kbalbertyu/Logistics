<?php
/**
 * Created by PhpStorm.
 * User: AlbertYu
 * Date: 9/19/2018
 * Time: 7:42 PM
 */

namespace Application\Model;


use ReflectionClass;
use Zend\I18n\Translator\Loader\PhpArray;
use Zend\I18n\Translator\Translator;

class Tools {

    /**
     * @var Translator
     */
    private static $translator;

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

    /**
     * @return Translator
     */
    public static function getTranslator($locale = 'zh_CN') {
        if (!empty(self::$translator)) {
            self::$translator->setLocale($locale);
            return self::$translator;
        }
        $translator = new Translator();
        $translator->addTranslationFilePattern(PhpArray::class, ZF_PATH . '/config/languages/', '%s.php');
        self::$translator = $translator;
        self::$translator->setLocale($locale);
        return self::$translator;
    }

    /**
     * @param $key
     * @param $parameters
     * @return mixed|string
     */
    public static function __($key, $parameters = []) {
        $message = self::getTranslator()->translate($key);
        if (!empty($parameters)) {
            $names = array_keys($parameters);
            array_walk($names, function (&$name) {
                $name = '{' . $name . '}';
            });
            $message = str_replace($names, array_values($parameters), $message);
        }
        return $message;
    }

    public static function getProperties(string $class) {
        $reflector = new ReflectionClass($class);
        preg_match_all('/\@property\s+\w+\s+(\w+)/', $reflector->getDocComment(), $matches);
        return $matches[1];
    }

    private const FILE_MAX_SIZE_IN_MB = 20;

    private const ATTACHMENT_PATH = 'public/data/';

    public const ATTACHMENT_URL_PATH = 'data/';

    /**
     * @param $fileData the $_FILES['FILE-NAME'] array
     * @return bool|null|string
     */
    public static function uploadAttachment($fileData){
        $file = null;
        if(!empty($fileData)){
            if ($fileData['error'] !== 0) {
                throw new \RuntimeException(self::__('file.upload.error.with.code', ['code' => $fileData['error']]));
            }
            if($fileData['size'] > 1024 * 1000 * self::FILE_MAX_SIZE_IN_MB){
                throw new \RuntimeException(self::__('file.size.limit.alert', ['limit' => self::FILE_MAX_SIZE_IN_MB]));
            }
            $ext = substr(strrchr($fileData['name'],'.'), 1);
            $path = date('Ym').'/'.date('d');
            $newFileName = self::generateFileName($ext, ZF_PATH . '/' . self::ATTACHMENT_PATH . $path);
            if(!is_dir(ZF_PATH . '/' . self::ATTACHMENT_PATH . $path)){
                mkdir(ZF_PATH . '/' . self::ATTACHMENT_PATH . $path, 0755, true);
            }

            $moved = @move_uploaded_file($fileData['tmp_name'], ZF_PATH . '/' . self::ATTACHMENT_PATH . $path . '/' . $newFileName);
            if(!$moved){
                throw new \RuntimeException(self::__('upload.file.fail', ['name' => $fileData['name']]));
            }
            $file = $path . '/' . $newFileName;
        }
        return $file;
    }

    function generateFileName($fileExt, $path){
        do {
            $newFileName = rand(1000000,9999999) . "." . $fileExt;
            if (!file_exists($path . "/" . $newFileName)) {
                return $newFileName;
            }
        } while(1);
    }
}
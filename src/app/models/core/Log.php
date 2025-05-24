<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 06/07/15
 * Time: 22:03
 */

namespace app\models\core;


class Log extends \Prefab
{
    public static $LEVEL_LABELS = [
        LOG_DEBUG    => 'DEBUG',
        LOG_INFO     => 'INFO',
        LOG_WARNING  => 'WARN',
        LOG_ERR      => 'ERROR',
        LOG_CRIT     => 'CRITICAL'
    ];

    /**
     * @var \Log[]
     */
    protected static $_loggers = [];

    /**
     * @param $file
     * @return \Log
     */
    public static function getLogger($file) {
        if (!$file) {
            $file = Main::app()->getConfig('APP_LOG');
        }

        if (!isset(self::$_loggers[$file])) {
            self::$_loggers[$file] = new \Log($file);
        }

        return self::$_loggers[$file];
    }

    public function log($message, $level = LOG_DEBUG, $file = null): void {
        self::_log($message, $level, $file, false);
    }

    public function logObject($message, $level = LOG_DEBUG, $file = null): void {
        self::_log($message, $level, $file);
    }

    protected static function _log($message, $level = LOG_DEBUG, $file = null, $varDump = true) {
        if (!Main::app()->getConfig('LOG_ENABLED')) {
            return;
        }

        $line = ' [' . self::$LEVEL_LABELS[$level] . '] ' .
            self::getContextAsString([self::class, __FUNCTION__], 2) .
            ' ';
        $htmlErrors = ini_get('html_errors');
        ini_set('html_errors', 0);
        ob_start();
        if ($varDump) {
            var_dump($message);
        }
        else {
            echo $message;
        }

        $line .= trim(ob_get_clean(), "\n") . "\n";     // Remove multiple NL
        ini_set('html_errors', $htmlErrors);
        self::getLogger($file)->write($line, 'c');
    }

    public function logException(\Exception $e, $file = null): void {
        if (!$file) {
            $file .= Main::app()->getConfig('EXCEPTION_LOG');
        }

        $message = $e->getCode() . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString();
        self::_log($message, LOG_ERR, $file, false);
    }

    /**
     *
     * @param mixed $to
     *          String for functions:   "myFunction"
     *          Arrays for methods:     array($object, "myMethod")
     *                                  array("MyClass", "myStaticMethod")
     * @return string
     */
    public static function getContextAsString($to, $level = 1) {
        $target = null;
        try {
            $backtrace = debug_backtrace();
            //Reach target
            $counter = count($backtrace);

            //Reach target
            for($i = 0; $i < $counter; $i++) {
                $b = $backtrace[$i];
                if (self::_isTargetContext($b, $to) && isset($backtrace[$i+$level])) {
                    $target = $backtrace[$i+$level];
                    $target['file'] = $backtrace[$i + $level - 1]['file'];
                    $target['line'] = $backtrace[$i + $level - 1]['line'];
                    break;
                }
            }
        }
        catch(Exception $exception) {}

        if (null !== $target) {
            return self::_formatContext($target);
        }
        else {
            return '{Unknown Context}';
        }
    }

    protected static function _isTargetContext($context, $target): bool {
        if (is_string($target)) {
            $target = ['function' => $target];
        } elseif (is_array($target)) {
            if (is_string($target[0]) && is_string($target[1])) {
                $target['class'] = $target[0];
                $target['type'] = '::';
                $target['function'] = $target[1];
            }
            if (is_object($target[0]) && is_string($target[1])) {
                $target['class'] = get_class($target[0]);
                $target['type'] = '->';
                $target['function'] = $target[1];
            }
        }

        $functionsMatch = false;
        $classesMatch = false;
        $typesMatch = false;

        if (is_array($context)) {
            if ($context['function'] == $target['function']) {
                $functionsMatch = true;
            }

            if (isset($target['class']) && isset($context['class'])) {
                if ($context['class'] == $target['class']) {
                    $classesMatch = true;
                }
            }
            else {
                $classesMatch = true;
            }

            if (isset($target['type']) && isset($context['type'])) {
                if ($context['type'] == $target['type']) {
                    $typesMatch = true;
                }
            }
            else {
                $typesMatch = true;
            }
        }

        return $functionsMatch && $classesMatch && $typesMatch;
    }

    protected static function _formatContext($context): string {
        $rootDir = dirname($_SERVER['SCRIPT_FILENAME']);
        $file = $context['file'];
        if (0 === strpos($file, $rootDir)) {
            $file = substr($file, strlen($rootDir));
        }

        $out = $file . ' [line ' . $context['line'] . '] ';
        if (isset($context['class'])) {
            $out .= $context['class'] . $context['type'];
        }

        $out .= $context['function'] . '(';

        //TODO args

        $out .= ')';

        return $out;
    }
}
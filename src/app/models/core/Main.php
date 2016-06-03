<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 01/07/15
 * Time: 21:17
 */

namespace app\models\core;

class Main extends \Prefab
{
    protected static $_app;

    public function setup() {
        $this->_checkRequirements();
        $this->_installErrorHandler();
        $this->_registerShutdownFunction();

        // more to come...

        self::app()->setup();
        return $this;
    }

    protected function _checkRequirements() {
        if (version_compare(phpversion(), '5.5.0', '<')) {
            throw new \Exception('You must run PHP 5.5.0 or above (current version: ' . phpversion() . ').');
        }
    }

    /**
     * @return App
     * @throws \Exception
     */
    public static function app() {
        if (!self::$_app) {
            $appClass = \Base::instance()->get('app.APP_CLASS');
            if (!$appClass) {
                throw new \Exception('Missing app.APP_CLASS in configuration');
            }
            self::$_app = new $appClass();
        }
        return self::$_app;
    }

    protected function _installErrorHandler() {
        set_error_handler(array(__CLASS__, '__exception_error_handler'));
    }

    protected function _registerShutdownFunction() {
        register_shutdown_function(array(__CLASS__, '__on_shutdown'));
    }

    public static function __exception_error_handler($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function __on_shutdown() {
        //nothing for now
    }
}
<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 03/07/15
 * Time: 22:47
 */

namespace app\models\core;

class Session extends \Magic
{
    const MESSAGE_INFO = 'info';
    const MESSAGE_WARN = 'warn';
    const MESSAGE_ERROR = 'error';

    const MESSAGES_KEY = '__messages';

    protected static $_session;

    protected $_name;
    protected $_data;

    public function __construct($name) {
        if (!self::$_session) {
            self::$_session = new \Session();
        }
        $this->_name = $name;
        $this->_data =& $_SESSION[$name];
        if (!is_array($this->_data)) {
            $this->_data = array();
        }
    }

    public function getId() {
        return session_id() . '' . $this->_name;
    }

    public function exists($key) {
        return isset($this->_data[$key]);
    }

    public function set($key, $val) {
        $this->_data[$key] = $val;
        return $this;
    }

    public function &get($key) {
        if (isset($this->_data[$key])) {
            $val = &$this->_data[$key];
        }
        else {
            $val = null;
        }
        return $val;
    }

    public function clear($key) {
        unset($this->_data[$key]);
        return $this;
    }

    public function addMessage($message, $type, $options = array()) {
        $messages = $this->getMessages();
        $key = md5($message);

        // Prevent adding the same message multiple times
        if (!isset($messages[$type][$key])) {
            $messages[$type][$key] = array(
                'message' => $message,
                'options' => $options
            );
            $this->set(self::MESSAGES_KEY, $messages);
        }
        return $this;
    }

    /**
     *
     * @return string[][]
     */
    public function getMessages() {
        $messages = $this->get(self::MESSAGES_KEY);
        if (!is_array($messages) || empty($messages)) {
            $messages = $this->clearMessages();
        }
        return $messages;
    }

    /**
     *
     * @return string[]
     */
    public function getMessagesByType($type) {
        $messages = $this->getMessages();
        return isset($messages[$type]) ? $messages[$type] : array();
    }

    public function clearMessages() {
        $messages = array(
            self::MESSAGE_INFO  => array(),
            self::MESSAGE_WARN  => array(),
            self::MESSAGE_ERROR => array(),
        );
        $this->set(self::MESSAGES_KEY, $messages);
        return $messages;
    }

    /**
     * For now, it just returns the global locale based on F3's LANGUAGE and ENCODING variables.
     * No validation is performed on the data. Later we can imagine a locale per session and a
     * more robust way to retrieve it.
     *
     * @return string
     */
    public function getLocale() {
        list($lang) = explode(',', \Base::instance()->get('LANGUAGE'));
        $encoding = \Base::instance()->get('ENCODING');
        return str_replace('-', '_', $lang) . ".$encoding";
    }

    public function getCurrencyCode() {
        return Main::app()->getConfig('CURRENCY');
    }
}
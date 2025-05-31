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

    protected static $_cacheInstance;

    protected static $_session;

    protected $_data;

    public function __construct(protected string $_name) {
        if (!self::$_session) {
            self::$_cacheInstance = new \Cache(Main::app()->getConfig('SESSIONS'));
            // Disable default "onsuspect" behavior (HTTP 403)
            self::$_session = new \Session(fn() => null, null, self::$_cacheInstance);
        }

        $this->_data =& \Base::instance()->ref('SESSION.' . $this->_name);
    }

    public function getId(): string {
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

    public function addMessage($message, $type, $options = []): self {
        $messages = $this->getMessages();
        $key = md5($message);

        // Prevent adding the same message multiple times
        if (!isset($messages[$type][$key])) {
            $messages[$type][$key] = [
                'message' => $message,
                'options' => $options
            ];
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
        if (!is_array($messages) || $messages === []) {
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
        return $messages[$type] ?? [];
    }

    public function clearMessages(): array {
        $messages = [
            self::MESSAGE_INFO  => [],
            self::MESSAGE_WARN  => [],
            self::MESSAGE_ERROR => [],
        ];
        $this->set(self::MESSAGES_KEY, $messages);
        return $messages;
    }

    public function getLocale() {
        if (!$locale = $this->get('locale')) {
            [$locale] = explode(',', \Base::instance()->get('LANGUAGE'));
            if (!str_contains($locale, '.')) {
                $locale .= '.' . \Base::instance()->get('ENCODING');
            }

            $this->setLocale($locale);
        }

        return $locale;
    }

    public function setLocale($locale) {
        return $this->set('locale', $locale);
    }

    public function getTheme() {
        if ($theme = $this->get('theme')) {
            return $theme;
        }

        return null;
    }

    public function setTheme($theme) {
        return $this->set('theme', $theme);
    }
}

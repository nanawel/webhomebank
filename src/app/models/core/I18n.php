<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 01/07/15
 * Time: 21:17
 */

namespace app\models\core;

/**
 * Class I18n
 * Wrapper around F3 i18n features
 *
 * @package app\models
 */
class I18n extends \Prefab
{
    protected static $_PREFIX;

    protected $_currencyCode;
    protected $_numberFormatter;
    protected $_currencyFormatter;
    protected $_dateFormatter;

    public function __construct() {
        $fw = \Base::instance();
        self::$_PREFIX = $fw->get('PREFIX');

        if (!extension_loaded('intl')) {
            throw new \Exception('Missing "intl" extension.');
        }
    }

    public function setLocale($locale) {
        \Base::instance()->set('LANGUAGE', $locale);
    }

    public function getLocale() {
        list($lang) = explode(',', \Base::instance()->get('LANGUAGE'));
        return $lang;
    }

    public function setCurrencyCode($currencyCode) {
        $this->_currencyCode = $currencyCode;
    }

    public function getCurrencyCode() {
        return $this->_currencyCode;
    }

    public function getAvailableLocales()
    {
        return Main::app()->getConfig('LANGUAGES');
    }

    public function getAvailableCurrencies()
    {
        return Main::app()->getConfig('CURRENCIES');
    }

    /**
     * @param $string
     * @param mixed $vars...
     * @return mixed
     */
    public function tr($string, $vars = null) {
        $fw = \Base::instance();
        if ($string === '' || $string === null) {
            return '';
        }
        if (!is_array($vars)) {
            $vars = array_slice(func_get_args(), 1);
        }
        $translation = $this->get($string, $vars);
        if (!$translation) {
            if ($fw->get('DEBUG') > 1) {
                Log::instance()->log('Missing translation for string: ' . $string, LOG_DEBUG, 'i18n.log');
            }
            // Fallback: add the given string as translation itself
            $this->set($string, $string);
            $translation = $this->get($string, $vars);
        }
        return $translation;
    }

    /**
     * Formats a currency value (price, etc.)
     *
     * @param $value
     * @return string
     */
    public function currency($value, $withContainer = false, $currencyCode = null) {
        if ($currencyCode === null) {
            $currencyCode = $this->_currencyCode;
        }
        if ($formatter = $this->getCurrencyFormatterInstance()) {
            $return = $formatter->formatCurrency($value, $currencyCode);
        }
        else {
            $return = \Base::instance()->format("{0,number,currency,$currencyCode}", $value);
        }

        if ($withContainer) {
            $return = $this->_wrapContainer($value, $return, 'currency');
        }
        return $return;
    }

    public function number($value, $withContainer = false) {
        if ($formatter = $this->getNumberFormatterInstance()) {
            $return = $formatter->format($value);
        }
        else {
            $return = \Base::instance()->format('{0,number}', $value);
        }
        if ($withContainer) {
            $return = $this->_wrapContainer($value, $return);
        }
        return $return;
    }

    protected function _wrapContainer($rawValue, $displayValue, $additionalClasses = '') {
        $class = explode(' ', $additionalClasses);
        if (is_numeric($rawValue)) {
            $class[] = 'number';
            $class[] = $rawValue < 0 ? 'number-neg' : 'number-pos';
        }
        $class = implode(' ', $class);
        $return = "<span class=\"$class\">$displayValue</span>";
        return $return;
    }

    public function date($value) {
        if ($formatter = $this->getDateFormatterInstance()) {
            return $formatter->format($value);
        }
        return \Base::instance()->format('{0,date}', $value);
    }

    public function getNumberFormatterInstance() {
        if (class_exists('NumberFormatter') && !$this->_numberFormatter) {
            $this->_numberFormatter = new \NumberFormatter(
                $this->getLocale(),
                \NumberFormatter::DECIMAL
            );
        }
        return $this->_numberFormatter;
    }

    public function getCurrencyFormatterInstance() {
        if (class_exists('NumberFormatter') && !$this->_currencyFormatter) {
            $this->_currencyFormatter = new \NumberFormatter(
                $this->getLocale(),
                \NumberFormatter::CURRENCY
            );
        }
        return $this->_currencyFormatter;
    }

    public function getDateFormatterInstance() {
        if (class_exists('IntlDateFormatter') && !$this->_dateFormatter) {
            $this->_dateFormatter = new \IntlDateFormatter(
                $this->getLocale(),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::NONE
            );
        }
        return $this->_dateFormatter;
    }

    public function getLocaleCountryCodeISO2() {
        return substr($this->getLocale(), 0, 2);
    }

    public function set($key, $value) {
        return \Base::instance()->set(self::$_PREFIX . $key, $value);
    }

    public function get($key, $vars = null) {
        return \Base::instance()->get(self::$_PREFIX . $key, $vars);
    }
}
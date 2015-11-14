<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:28
 */

namespace app\models\core\Form\Element;

use xhb\util\MagicObject;

class AbstractElement extends MagicObject
{
    protected static $_document = null;

    protected $_tagName = null;
    protected $_domElement = null;
    protected $_value = null;

    public function __construct($tagName, $data = array()) {
        $this->_tagName = $tagName;
        $this->addData($data);
    }

    public function addClass($class) {
        $classes = explode(' ', $this->getClass());
        $classes[] = $class;
        $this->setClass(implode(' ', $classes));
        return $this;
    }

    public function removeClass($class) {
        $classes = explode(' ', $this->getClass());
        if ($key = array_search($class, $classes) !== null) {
            unset($classes[$key]);
        }
        $this->setClass(implode(' ', $classes));
        return $this;
    }

    public function getDOMElement() {
        if (!$this->_domElement) {
            $this->_domElement = $this->getDocument()->createElement($this->_tagName);
        }
        return $this->_domElement;
    }

    protected function _setCommonAttributes() {
        $attributes = array('id', 'name', 'class');
        foreach($attributes as $a) {
            if ($v = $this->getDataUsingMethod($a)) {
                $this->getDOMElement()->setAttribute($a, $v);
            }
        }
        return $this;
    }

    protected function _addAttributesToDOMElement(\DOMElement $el, array $attributes) {
        foreach($attributes as $k => $v) {
            if ($k && strpos($k, '_') !== 0) {
                $el->setAttribute($k, $v);
            }
        }
        return $this;
    }

    public final function toHtml() {
        return $this->_toHtml();
    }

    protected function _toHtml() {
        return '';
    }

    protected static function getDocument() {
        if (!self::$_document) {
            self::$_document = new \DOMDocument();
        }
        return self::$_document;
    }

    public function getValue() {
        $useRequest = true; // TODO Use config
        $useDefault = true; // TODO Use config
        if (!$v = parent::getValue()) {
            if (!$useRequest || !$v = \Base::instance()->get('REQUEST.' . $this->getName())) {
                if (!$useDefault || !$v = parent::getDefaultValue()) {
                    $v = null;
                }
            }
        }
        return $v;
    }

    public function setValue($value) {
        $this->_value = $value;
    }
}
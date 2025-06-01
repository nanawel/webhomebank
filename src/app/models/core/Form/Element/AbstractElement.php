<?php

namespace app\models\core\Form\Element;

use Xhb\Util\MagicObject;

class AbstractElement extends MagicObject
{
    protected static $_document = null;

    protected $_domElement = null;

    protected $_value = null;

    public function __construct(
        protected $tagName,
        array $data = []
    ) {
        $this->addData($data);
    }

    public function addClass($class): self {
        $classes = explode(' ', (string) $this->getClass());
        $classes[] = $class;
        $this->setClass(implode(' ', $classes));
        return $this;
    }

    public function removeClass($class): self {
        $classes = explode(' ', (string) $this->getClass());
        if ($key = array_search($class, $classes, true) !== null) {
            unset($classes[$key]);
        }

        $this->setClass(implode(' ', $classes));
        return $this;
    }

    public function getDOMElement() {
        if (!$this->_domElement) {
            $this->_domElement = static::getDocument()->createElement($this->tagName);
        }

        return $this->_domElement;
    }

    protected function _setCommonAttributes(): self {
        $attributes = ['id', 'name', 'class'];
        foreach($attributes as $a) {
            if ($v = $this->getDataUsingMethod($a)) {
                $this->getDOMElement()->setAttribute($a, $v);
            }
        }

        return $this;
    }

    protected function _addAttributesToDOMElement(\DOMElement $el, array $attributes): self {
        foreach($attributes as $k => $v) {
            if ($k && !str_starts_with($k, '_')) {
                $el->setAttribute($k, $v);
            }
        }

        return $this;
    }

    public final function toHtml(): string {
        return $this->_toHtml();
    }

    protected function _toHtml(): string {
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

    public function setValue($value): void {
        $this->_value = $value;
    }
}

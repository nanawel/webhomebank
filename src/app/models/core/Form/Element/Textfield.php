<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:28
 */

namespace app\models\core\Form\Element;

class Textfield extends AbstractElement
{
    public function __construct($data = []) {
        parent::__construct('input', $data);
    }

    protected function _toHtml(): string {
        $el = $this->getDOMElement();
        $this->_setCommonAttributes();
        $el->setAttribute('type', 'text');

        $el->setAttribute('value', (string) $this->getValue());
        $el->setAttribute('placeholder', (string) $this->getPlaceholder());

        return self::getDocument()->saveXML($el);
    }
}

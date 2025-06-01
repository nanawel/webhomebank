<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:28
 */

namespace app\models\core\Form\Element;

class Select extends AbstractElement
{
    const SEPARATOR_LABEL = '-';

    const SEPARATOR_LABEL_RENDERER = '──────────';

    public function __construct($data = []) {
        parent::__construct('select', $data);
    }

    protected function _toHtml(): string {
        $el = $this->getDOMElement();
        $this->_setCommonAttributes();
        foreach($this->getOptions() as $value => $option) {
            $optEl = $this->getDocument()->createElement('option');
            foreach($option as $n => $a) {
                if ($n == 'label') {
                    if ($a == self::SEPARATOR_LABEL) {
                        $optEl->nodeValue = self::SEPARATOR_LABEL_RENDERER;
                        $optEl->setAttribute('disabled', 'disabled');
                    }
                    else {
                        $optEl->nodeValue = htmlspecialchars($a);
                    }
                }
                else {
                    $optEl->setAttribute(htmlspecialchars($n), htmlspecialchars($a));
                }
            }

            $optEl->setAttribute('value', htmlspecialchars((string) $value));
            if ($value == $this->getValue()) {
                $optEl->setAttribute('selected', 'selected');
            }

            $el->appendChild($optEl);
        }

        return self::getDocument()->saveXML($el);
    }

    public function setSelection(string $key): void {
        $this->setValue($key);
    }
}

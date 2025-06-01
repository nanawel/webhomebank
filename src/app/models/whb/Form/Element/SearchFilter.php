<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:29
 */

namespace app\models\whb\Form\Element;

use app\models\core\Form\Element\Textfield;
use app\models\core\I18n;
use Xhb\Model\Xhb;

class SearchFilter extends Textfield implements IWhbElement
{
    public function __construct(protected \Xhb\Model\Xhb $_xhb, $data = []) {
        parent::__construct($data);
        $i18n = I18n::instance();
        $this->setPlaceholder($i18n->tr('Search...'));
        $this->addClass('search');
    }

    public function getXhb(): \Xhb\Model\Xhb {
        return $this->_xhb;
    }
}
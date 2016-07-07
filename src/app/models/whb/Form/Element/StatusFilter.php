<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:29
 */

namespace app\models\whb\Form\Element;

use app\helpers\whb\AccountOperation;
use app\models\core\Form\Element\Select;
use app\models\core\I18n;
use Xhb\Model\Xhb;

class StatusFilter extends Select implements IWhbElement
{
    protected $_xhb;

    public function __construct(Xhb $xhb, $data = array()) {
        $this->setLabel('Status');
        parent::__construct($data);
        $this->_xhb = $xhb;

        $i18n = I18n::instance();
        $periods = AccountOperation::getStaticCollectionFilters()['status'];
        $options = array();
        foreach($periods['values'] as $k => $p) {
            $options[$k] = array(
                'label' => $i18n->tr($p)
            );
        }
        $this->setOptions($options);
    }

    public function getXhb() {
        return $this->_xhb;
    }
}
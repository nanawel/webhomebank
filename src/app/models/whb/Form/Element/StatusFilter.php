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
    public function __construct(protected \Xhb\Model\Xhb $_xhb, $data = []) {
        $this->setLabel('Status');
        parent::__construct($data);

        $i18n = I18n::instance();
        $periods = AccountOperation::getStaticCollectionFilters()['status'];
        $options = [];
        foreach($periods['values'] as $k => $p) {
            $options[$k] = [
                'label' => $i18n->tr($p)
            ];
        }

        $this->setOptions($options);
    }

    public function getXhb(): \Xhb\Model\Xhb {
        return $this->_xhb;
    }
}
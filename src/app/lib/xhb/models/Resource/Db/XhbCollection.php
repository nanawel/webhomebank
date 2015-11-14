<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace xhb\models\Resource\Db;

use DB\SQL;
use xhb\models\Resource\Closure;

/**
 * Class XhbCollection
 *
 * Common parent collection class for XHB entities.
 *
 * @package xhb\models\Resource
 */
abstract class XhbCollection extends AbstractCollection
{
    const XHB_ID_COL = 'xhb_id';

    protected function _beforeLoad() {
        parent::_beforeLoad();
        if (!$this->getFlag('skip_xhb_id_filter')) {
            $this->addFieldToFilter('main_table.' . self::XHB_ID_COL, $this->getXhb()->getXhbId());
        }
        return $this;
    }

    protected function _execLoadQuery() {
        $items = parent::_execLoadQuery();
        $xhb = $this->getXhb();
        foreach($items as &$it) {
            $it['xhb'] = $xhb;
        }
        return $items;
    }
}
<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:47
 */

namespace xhb\models\Resource\Db\Payee;

use xhb\models\Resource\Db\XhbCollection;

class Collection extends XhbCollection implements \xhb\models\Resource\Iface\Payee\Collection
{
    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_init('\\xhb\\models\\Payee', 'key', \xhb\models\Resource\Db\Payee::MAIN_TABLE);
    }
}
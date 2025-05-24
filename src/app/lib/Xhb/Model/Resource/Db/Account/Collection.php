<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:47
 */

namespace Xhb\Model\Resource\Db\Account;

use Xhb\Model\Resource\Db\XhbCollection;

class Collection extends XhbCollection implements \Xhb\Model\Resource\Iface\Account\Collection
{
    public function __construct(array $params = []) {
        parent::__construct($params);
        $this->_init(\Xhb\Model\Xhb::MODEL_CLASS_NAMESPACE . 'Account', 'key', \Xhb\Model\Resource\Db\Account::MAIN_TABLE);
    }
}
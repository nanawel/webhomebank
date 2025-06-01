<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:47
 */

namespace Xhb\Model\Resource\Db\Payee;

use Xhb\Model\Resource\Db\XhbCollection;

class Collection extends XhbCollection implements \Xhb\Model\Resource\Iface\Payee\Collection
{
    public function __construct(array $params = []) {
        parent::__construct($params);
        $this->_init(\Xhb\Model\Xhb::MODEL_CLASS_NAMESPACE . 'Payee', 'key', \Xhb\Model\Resource\Db\Payee::MAIN_TABLE);
    }
}
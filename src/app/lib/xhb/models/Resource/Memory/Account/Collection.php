<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:47
 */

namespace xhb\models\Resource\Memory\Account;

use xhb\models\Resource\Memory\AbstractCollection;

class Collection extends AbstractCollection
{
    public function __construct($params = array()) {
        parent::__construct($params);
        $this->_init('\\xhb\\models\\Account', 'key');
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 06/09/15
 * Time: 19:26
 */

namespace Xhb\Model\Resource\Db;


class Operation extends AbstractModel
{
    const MAIN_TABLE = 'operation';

    const SPLIT_AMOUNT_TABLE = 'operation_split_amount';

    public function __construct(array $params = []) {
        parent::__construct($params);
        $this->_init(['xhb_id', 'id'], self::MAIN_TABLE);
    }
}
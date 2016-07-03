<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 06/09/15
 * Time: 19:26
 */

namespace xhb\models\Resource\Db;


class Account extends AbstractModel
{
    const MAIN_TABLE = 'account';

    public function __construct(array $params = array()) {
        parent::__construct($params);
        $this->_init(array('xhb_id', 'key'), self::MAIN_TABLE);
    }
}
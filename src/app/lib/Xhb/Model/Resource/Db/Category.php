<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 06/09/15
 * Time: 19:26
 */

namespace Xhb\Model\Resource\Db;


class Category extends AbstractModel
{
    const MAIN_TABLE = 'category';

    public function __construct(array $params = array()) {
        parent::__construct($params);
        $this->_init(array('xhb_id', 'key'), self::MAIN_TABLE);
    }
}
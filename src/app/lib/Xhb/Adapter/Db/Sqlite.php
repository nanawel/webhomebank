<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 28/06/16
 * Time: 13:21
 */

namespace Xhb\Adapter\Db;


use Xhb\Adapter\Db;
use Xhb\Model\Resource\Db\Xhb;
use Zend\Db\Adapter\Adapter;

class Sqlite extends Db
{
    public function __construct(array $config) {
        if (!isset($config['db']['database'])) {
            throw new \Exception('Missing database path');
        }
        $parentDir = dirname($config['db']['database']);
        if (!is_writable($parentDir)) {
            throw new \Exception($parentDir . ' is not writable');
        }
        parent::__construct($config);
    }

    public function importXhbData($xhbData, $xhbId, $force = false) {
        try {
            if (parent::importXhbData($xhbData, $xhbId, $force)) {
                @chmod($this->_config['db']['database'], 0640);
            }
        }
        catch (\Exception $e) {
            // Destroy incomplete DB file
            @unlink($this->_config['db']['database']);
            throw $e;
        }
    }
}

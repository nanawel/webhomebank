<?php

namespace Xhb\Adapter\Db;


use Xhb\Adapter\Db;
use Xhb\Model\Resource\Db\Xhb;
use Laminas\Db\Adapter\Adapter;

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

    public function importXhbData($xhbData, $xhbId, $force = false): bool {
        try {
            if (parent::importXhbData($xhbData, $xhbId, $force)) {
                @chmod($this->_config['db']['database'], 0640);
                return true;
            }

            return false;
        }
        catch (\Exception $exception) {
            // Destroy incomplete DB file
            @unlink($this->_config['db']['database']);
            throw $exception;
        }
    }
}

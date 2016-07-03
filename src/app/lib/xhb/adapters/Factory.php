<?php

namespace xhb\adapters;

use xhb\adapters\Db\Sqlite;
use Zend\Db\Adapter\Adapter;

class Factory
{
    /**
     *
     * @param $xhbConfig
     * @return AdapterInterface
     */
    public static function create(array $xhbConfig) {
        if (!isset($xhbConfig['resource_config']['type'])) {
            throw new \Exception('Missing resource type in configuration');
        }
        $adapter = null;
        switch ($xhbConfig['resource_config']['type']) {
            case 'db':
                if (!isset($xhbConfig['resource_config']['db']['driver'])) {
                    throw new \Exception('Missing DB driver in configuration');
                }
                switch ($xhbConfig['resource_config']['db']['driver']) {
                    case 'Pdo_Sqlite':
                        $adapter = new Sqlite($xhbConfig['resource_config']);
                        break 2;

                    case 'Pdo_Mysql':
                        //TODO Pdo_Mysql / Pdo_Pgsql

                    default:
                        throw new \Exception('Unsupported driver "' . $resourceParams['resource_config']['db']['driver'] . '"');
                }
                break;

            case null:
            case 'memory':
                throw new \Exception('"memory" resource type is deprecated, please use "db" instead.');
                break;

            default:
                throw new \Exception('Unsupported resource type "' . $xhbConfig['resource_config']['type'] . '"');
        }
        return $adapter;
    }
}
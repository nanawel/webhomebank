<?php

namespace Xhb\Adapter;

use Xhb\Adapter\Db\Sqlite;
use Laminas\Db\Adapter\Adapter;

class Factory
{
    /**
     *
     * @param $xhbConfig
     * @return AdapterInterface
     */
    public static function create(array $xhbConfig): ?\Xhb\Adapter\Db\Sqlite {
        if (!isset($xhbConfig['resource_config']['type'])) {
            throw new \Exception('Missing resource type in configuration');
        }

        $adapter = null;
        switch ($xhbConfig['resource_config']['type']) {
            case 'db':
                if (!isset($xhbConfig['resource_config']['db']['driver'])) {
                    throw new \Exception('Missing DB driver in configuration');
                }

                $adapter = match ($xhbConfig['resource_config']['db']['driver']) {
                    'Pdo_Sqlite' => new Sqlite($xhbConfig['resource_config']),
                    default => throw new \Exception('Unsupported driver "' . $resourceParams['resource_config']['db']['driver'] . '"'),
                };

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

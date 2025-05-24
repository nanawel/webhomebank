<?php

namespace Xhb\Adapter;

interface AdapterInterface
{
    /**
     * @param array $config
     */
    public function __construct(array $config);

    /**
     * @param $xhbId string
     * @return boolean
     */
    public function xhbExists($xhbId): bool;

    /**
     * @param $xhbData array
     * @param $xhbId string
     * @param $force boolean
     * @return void
     */
    public function importXhbData($xhbData, $xhbId, $force = false): bool;
}

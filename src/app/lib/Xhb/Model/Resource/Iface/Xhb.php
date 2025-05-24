<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 14/11/15
 * Time: 12:14
 */
namespace Xhb\Model\Resource\Iface;

use Xhb\Model\Resource\Db\AbstractCollection;

interface Xhb
{
    /**
     *
     * @return AbstractCollection
     */
    public function getAccountCollection();

    /**
     *
     * @return AbstractCollection
     */
    public function getCategoryCollection();

    /**
     *
     * @return AbstractCollection
     */
    public function getOperationCollection();

    /**
     *
     * @return AbstractCollection
     */
    public function getPayeeCollection();

    /**
     * @return string
     */
    public function getXhbId();

    /**
     * @param $modelClass
     * @param array $params
     * @return mixed
     */
    public function getCollectionInstance($modelClass, $params = []);
}
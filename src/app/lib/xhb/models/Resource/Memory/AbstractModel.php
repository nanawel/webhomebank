<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace xhb\models\Resource\Memory;

use xhb\models\Resource\Model;
use xhb\util\MagicObject;

/**
 * Class AbstractModel
 *
 * @package xhb\models\Resource\Memory
 */
abstract class AbstractModel extends MagicObject implements Model
{
    public function load(MagicObject $object, $id)
    {
        throw new \Exception(__METHOD__ . ': Not supported');
    }

    public function save(MagicObject $object)
    {
        throw new \Exception(__METHOD__ . ': Not supported');
    }

    public function delete(MagicObject $object)
    {
        throw new \Exception(__METHOD__ . ': Not supported');
    }
}
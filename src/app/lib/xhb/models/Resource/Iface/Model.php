<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace xhb\models\Resource\Iface;

use xhb\util\MagicObject;

/**
 * Interface Model
 *
 * @package xhb\models\Resource\Iface
 */
interface Model
{
    public function __construct(array $params = array());

    public function load(MagicObject $object, $id);

    public function save(MagicObject $object);

    public function delete(MagicObject $object);
}
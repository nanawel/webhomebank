<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace Xhb\Model\Resource\Iface;

use Xhb\Util\MagicObject;

/**
 * Interface Model
 *
 * @package Xhb\Model\Resource\Iface
 */
interface Model
{
    public function __construct(array $params = []);

    public function load(MagicObject $object, $id);

    public function save(MagicObject $object);

    public function delete(MagicObject $object);
}
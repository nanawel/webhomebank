<?php
namespace Xhb\Model;

use Xhb\Util\MagicObject;

/**
 * Class XhbModel
 *
 * @method int getAutoWeekday()
 * @method Xhb getXhb()
 *
 * @package Xhb\Model
 */
abstract class XhbModel extends MagicObject
{
    const MODEL_CLASS_NAMESPACE = 'Xhb\\Model\\';

    protected static $_resource = [];

    public function __construct(array $data = []) {
        $this->_init($data);
    }

    protected function _init(array $data) {
        $this->setData($data);
    }

    public function __toString() {
        $data = [];
        foreach($this->getData() as $k => $v) {
            $data[] = $k . '=' . (string)$v;
        }

        return get_class($this) . ': ' . implode('|', $data);
    }

    public function getResource($singleton = true, array $params = []) {
        $params = array_merge(
            ['resource_config' => $this->getXhb()->getResourceConfig()],
            $params
        );
        $namespace = trim($params['_namespace'] ?? self::MODEL_CLASS_NAMESPACE, '\\');
        $class = substr(trim(get_class($this), '\\'), strlen($namespace) + 1);
        if (!isset($params['xhb'])) {
            $params['xhb'] = $this->getXhb();
        }

        if (!$singleton) {
            return $this->getXhb()->getResourceInstance($class, $params);
        }

        if (!isset(self::$_resource[$class])) {
            self::$_resource[$class] = $this->getXhb()->getResourceInstance($class, $params);
        }

        return self::$_resource[$class];
    }

    public function load($id = null) {
        $this->getResource()->load($this, $id);
        return $this;
    }

    public function save() {
        $this->getResource()->save($this);
        return $this;
    }

    public function delete() {
        $this->getResource()->delete($this);
        return $this;
    }
}
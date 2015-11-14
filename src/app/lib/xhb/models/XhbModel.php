<?php
namespace xhb\models;

use xhb\util\MagicObject as MagicObject;

/**
 * Class XhbModel
 *
 * @method int getAutoWeekday()
 * @method Xhb getXhb()
 *
 * @package xhb\models
 */
abstract class XhbModel extends MagicObject
{
    const MODEL_CLASS_NAMESPACE = 'xhb\\model\\';

    protected static $_resource = array();

    public function __construct(array $data = array()) {
        $this->_init($data);
    }

    protected function _init(array $data) {
        $this->setData($data);
    }

    public function __toString() {
        $data = array();
        foreach($this->getData() as $k => $v) {
            $data[] = $k . '=' . (string)$v;
        }
        return get_class($this) . ': ' . implode('|', $data);
    }

    public function getResource($singleton = true, $params = array()) {
        $namespace = isset($params['_namespace']) ? $params['_namespace'] : self::MODEL_CLASS_NAMESPACE;
        $class = substr(get_class($this), strlen($namespace) + 1);
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
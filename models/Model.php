<?php
namespace li3_doctrine2\models;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Tools\Pagination\Paginator;

abstract class Model extends BaseEntity {

    private static $_repository = array();
    private static $_entityManager = array();

    protected static $connectionName = 'doctrine';

    protected $_entCols = array();

    public function __construct() {
        parent::__construct();
        self::_load();
    }

    public function save() {
        if (func_num_args() >= 1) {
            $options = func_get_arg(0);
            if (array_key_exists('validate', $options)) {
                $this->validates = $options['validate'];
            }
        }

        self::$_entityManager[get_called_class()]->persist($this);
        self::$_entityManager[get_called_class()]->flush();
    }

    public function delete() {
        self::$_entityManager[get_called_class()]->remove($this);
        self::$_entityManager[get_called_class()]->flush();
    }

    private static function _load() {

        if (!array_key_exists(get_called_class(), self::$_repository)) {
            self::$_repository[get_called_class()] = self::getRepository();
        }

        if (!array_key_exists(get_called_class(), self::$_entityManager)) {
            self::$_entityManager[get_called_class()] = self::getEntityManager();
        }
    }

    public static function __callStatic($name, $args) {
        self::_load();

        if (is_callable(array(self::$_entityManager[get_called_class()], $name))) {
            return call_user_func_array(array(self::$_entityManager[get_called_class()], $name), $args);
        } elseif (is_callable(array(self::$_repository[get_called_class()], $name))) {
            return call_user_func_array(array(self::$_repository[get_called_class()], $name), $args);
        }
    }

    public function &__get($name) {
        if ($this->_property_isset($name)) {
            return $this->$name;
        }
        $null = null; // this must be assigned to a variable because it returns a reference.
        return $null;
    }

    public function __set($name, $value = null) {
        if ($this->_property_isset($name)) {
            $this->$name = $value;
        }
    }

    public function __isset($name) {
        return $this->_property_isset($name);
    }

    protected function _property_isset($name) {
        // MODE
        if (!$this->_entCols) {
            $this->_entCols = $this->_getEntityFields();
        }

        return property_exists($this, $name) && in_array($name, $this->_entCols);
    }

    /**
     * Get record data as an array
     *
     * @param bool $allProperties If true, get also properties without getter methods
     * @return array Data
     */
    protected function _getData($allProperties = false) {
        $data = array();
        foreach($this->_getEntityFields() as $field) {
            $method = 'get' . \lithium\util\Inflector::camelize($field);
            if (method_exists($this, $method) && is_callable(array($this, $method))) {
                $data[$field] = $this->{$method}();
            } elseif (($allProperties && property_exists($this, $field)) || in_array($field, $this->_entCols)) {
                $data[$field] = $this->{$field};
            }
        }
        if (isset($name)) {
            return array_key_exists($name, $data) ? $data[$name] : null;
        }
        return $data;
    }

}

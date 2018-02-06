<?php
namespace Zaly;

/**
 * 加载配置文件
 *
 * @author 尹少爷 2017.12.21
 *
 */
class Config implements \ArrayAccess
{
    protected $_path;
    protected $_configs = array();
    public static $instance = '';

    protected function __construct($path)
    {
        $this->_path = $path ? $path : __DIR__.'/Config/';
    }

    public static function  init($path = '')
    {
        if(!self::$instance) {
            self::$instance = new Config($path);
        }
        return self::$instance;
    }
    //获取配置值
    public function offsetGet($key)
    {
        if (empty($this->_configs[$key]))
        {
            $filePath = $this->_path.'/'.$key.'.php';
            $fileName = $key.'.php';
            if(file_exists(dirname(__DIR__).'/Config/'.$fileName)) {
                $filePath = dirname(__DIR__).'/Config/'.$fileName;
            }
            $config = require $filePath;
            $this->_configs[$key] = $config;
        }
        return $this->_configs[$key];
    }
    //设置配置值
    public function offsetSet($key, $value)
    {
        throw new \Exception("cannot write config file.");
    }
    //检查配置是否存在
    public function offsetExists($key)
    {
        return isset($this->_configs[$key]);
    }
    //删除配置
   public  function offsetUnset($key)
    {
        unset($this->_configs[$key]);
    }

}

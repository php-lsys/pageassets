<?php
namespace LSYS\PageAssets;
/**
 * @method \LSYS\PageAssets pageAssets()
 */
class DI extends \LSYS\DI{
    /**
     * @var string default config
     */
    public static $config="assets";
    /**
     * @return static
     */
    public static function get(){
        $di=parent::get();
        !isset($di->pageAssets)&&$di->pageAssets(new \LSYS\DI\SingletonCallback(function (){
            $config=\LSYS\Config\DI::get()->config(self::$config);
            return new \LSYS\PageAssets($config);
        }));
        return $di;
    }
}
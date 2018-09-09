<?php
namespace LSYS\PageAssets;
/**
 * @method \LSYS\PageAssets page_assets()
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
        !isset($di->page_assets)&&$di->page_assets(new \LSYS\DI\SingletonCallback(function (){
            $config=\LSYS\Config\DI::get()->config(self::$config);
            return new \LSYS\PageAssets($config);
        }));
        return $di;
    }
}
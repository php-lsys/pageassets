<?php
use LSYS\PageAssets;
use LSYS\PageAssets\Merge;
include_once __DIR__."/Bootstarp.php";
//$assets=\LSYS\PageAssets\DI::get();//获取默认PageAssets,或自行NEW
$merge=new Merge(true);
// $merge=NULL;//如果你不需要对文件进行合并操作，可不传此负责合并资源的对象
$assets=new PageAssets(\LSYS\Config\DI::get()->config("assets"),$merge);//建议将此对象绑定到你的view对象中，实现全局共享

//$assets 对象的方法说明参见方法注释说明
$merge&&ob_start();
?>
<?php
include "tpl.php";
?>
<?php
if ($assets->getMerge()) echo $assets->getMerge()->render(ob_get_clean());//进行引入资源压缩渲染，配置参考:config/assets.php
?>

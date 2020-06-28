<?php
namespace LSYS;
use LSYS\PageAssets\CSSFile;
use LSYS\PageAssets\AssetsFile;
use LSYS\PageAssets\JSFile;
use LSYS\PageAssets\Merge;
class PageAssets {
	/**
	 * 默认配置
	 * @var string
	 */
	protected $_version;
	protected $_file_url;
	/**
	 * @var Merge
	 */
	protected $_merge;
	/**
	 * 页面资源管理
	 * 当需要对页面资源进行合并优化时,设置　$merge
	 * @param Config $config　
	 * @param Merge $merge
	 */
	public function __construct(Config $config,Merge $merge=null){
		if ($merge)$this->_merge=$merge->setPageAssets($this,$config);
		$this->_version=$config->get("version","1.0");
		$_defurl="assets/";
		$this->_file_url=(array)$config->get("file_url",$_defurl);
	}
	/**
	 * 得到完整资源链接
	 * @param AssetsFile $file
	 * @return string
	 */
	public function url(AssetsFile $file):string{
		if (!$file->isLocal())return $file->path();
		$url=$this->_file_url;
		$path=$file->path();
		return $this->_findUrl($url).$path;
	}
	/**
	 * 得到完整css链接
	 * @param string $css_url
	 * @return string
	 */
	public function cssUrl($css_url):string{
		$css_url=$this->_cssobj($css_url);
		return $this->url($css_url);
	}
	/**
	 * @param string|array $css_url
	 * @return \LSYS\PageAssets\CSSFile
	 */
	protected function _cssobj($css_url){
		if (!$css_url instanceof CSSFile){
			if (is_array($css_url)){
				if (count($css_url)<2){
					list($css_url)=$css_url;
					$version=null;
				}else{
					list($css_url,$version)=$css_url;
				}
			}else $version=$this->_version;
			$css_url=new CSSFile(strval($css_url),strval($version));
		}
		return $css_url;
	}
	/**
	 * 输出css加载标签
	 * $is_merge 为null[默认]时　直接输出js标签，不进行任何合并操作
	 * $is_merge 为true 时　此js合并到页面head部分进行加载，会在任何style标签之前加载
	 * @param string $css_url
	 * @param bool|null $is_merge
	 * @param array $attr
	 * @return void|string
	 */
	public function css($css_url,$is_merge=null,$attr=[]):string {
		if (!$css_url instanceof CSSFile){
			$css_url=new CSSFile(strval($css_url),$this->_version);
		}
		if ($this->_isLoad($css_url))return'';
		$attr=$this->_attributes($attr);
		if ($is_merge&&$this->_merge){
			$this->_merge->addHeaderCss($css_url,$attr);
			return '';
		}else{
			if ($this->_merge){
				$link=$this->_merge->bulidCss($css_url);
			}else{
				$link=$this->url($css_url);
			}
			return $this->cssTag($link, $attr);
		}
	}
	/**
	 * 得到完整JS链接
	 * @param string $js_url
	 * @return string
	 */
	public function jsUrl($js_url):string {
		$js_url=$this->_jsobj($js_url);
		return $this->url($js_url);
	}
	/**
	 * @param string|array $js_url
	 * @return \LSYS\PageAssets\JSFile
	 */
	protected function _jsobj($js_url){
		if (!$js_url instanceof JSFile){
			if (is_array($js_url)){
				if (count($js_url)<2){
					list($js_url)=$js_url;
					$version=null;
				}else{
					list($js_url,$version)=$js_url;
				}
			}else $version=$this->_version;
			$js_url=new JSFile(strval($js_url),strval($version));
		}
		return $js_url;
	}
	/**
	 * 输出js加载标签
	 * $is_merge 为null[默认] 时　直接输出js标签，不进行任何合并操作
	 * $is_merge 为true 时　此js合并到页面head部分进行加载，注意:会在任何script前
	 * $is_merge 为false 时　此js合并到body标签结束时进行加载
	 * @param string $js_url
	 * @param bool|null $is_merge
	 * @param array $attr
	 * @return void|string
	 */
	public function js($js_url,$is_merge=null,$attr=[]):string{
		$js_url=$this->_jsobj($js_url);
		if ($this->_isLoad($js_url))return '';
		$attr=$this->_attributes($attr);
		if ($is_merge===true&&$this->_merge){
			$this->_merge->addHeaderJs($js_url,$attr);
			return '';
		}
		if ($is_merge===false&&$this->_merge){
			$this->_merge->addFooterJs($js_url,$attr);
			return '';
		}
		if ($this->_merge){
			$link=$this->_merge->bulidJs($js_url);
		}else{
			$link=$this->url($js_url);
		}
		return $this->jsTag($link, $attr);
	}
	/**
	 * 得到完整的文件链接
	 * @param string $file_url
	 * @return string
	 */
	public function fileUrl($file_url):string{
		if (!$file_url instanceof AssetsFile){
			$file_url=new AssetsFile($file_url,$this->_version);
		}
		return $this->url($file_url);
	}
	/**
	 * 创建css加载html
	 * @param string $link
	 * @param string $attr
	 * @return string
	 */
	public function cssTag(string $link,string $attr):string{
		return "<link type='text/css' rel='stylesheet' href='{$link}' {$attr}/>\n";
	}
	/**
	 * 创建js加载html
	 * @param string $link
	 * @param string $attr
	 * @return string
	 */
	public function jsTag(string $link,string $attr):string{
		return "<script type='text/javascript'  src='{$link}' {$attr}></script>\n";
	}
	/**
	 * 得到当期资源合并对象
	 * @return \LSYS\PageAssets\Merge|null
	 */
	public function getMerge(){
		return $this->_merge;
	}
	/**
	 * 得到当前版本号
	 * @return string
	 */
	public function getVersion():string{
	    return strval($this->_version);
	}
	/**
	 * 得到资源目录
	 * @return string
	 */
	public function getUrl():string{
		return $this->_findUrl($this->_file_url);
	}
	protected function _findUrl($urls){
		$max=count($urls)-1;
		if ($max<0) return '/';
		return $urls[rand(0,$max)];
	}
	protected $_is_load=[];
	protected function _isLoad(AssetsFile $file){
		$hash=md5($file->path());
		if (in_array($hash,$this->_is_load))return true;
		$this->_is_load[]=$hash;
	}
	protected function _attributes(array $attributes = NULL)
	{
		if (empty($attributes)) return '';
		$compiled = '';
		foreach ($attributes as $key => $value)
		{
			if ($value === NULL)
			{
				// Skip attributes that have NULL values
				continue;
			}
			if (is_int($key))
			{
				// Assume non-associative keys are mirrored attributes
				$key = $value;
				$value=null;
			}
			
			// Add the attribute key
			$compiled .= ' '.$key;
			$value=htmlspecialchars( (string) $value, ENT_QUOTES, \LSYS\Core::charset(), true);
			// Add the attribute value
			if($value) $compiled .= '="'.$value.'"';
		}

		return $compiled;
	}
}
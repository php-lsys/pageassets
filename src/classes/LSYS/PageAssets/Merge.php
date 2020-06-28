<?php
namespace LSYS\PageAssets;
use LSYS\PageAssets;
use LSYS\Config;
use LSYS\Exception;
use LSYS\Core;

class Merge{
	protected $_page_assets;
	protected $_config;
	
	protected $_file_path;
	protected $_merge_path;
	protected $_merge_url;
	
	
	protected $_header_css=[];
	protected $_header_js=[];
	protected $_footer_js=[];
	
	protected $_bulid;
	/**
	 * 合并压缩css　js
	 * $is_bulid　是否进行编译操作 默认null　当为产品环境不编译其他环境编译
	 */
	public function __construct($is_bulid=NULL){
	    if ($is_bulid===null)$is_bulid=!Core::envIs(Core::PRODUCT);
		$is_bulid=boolval($is_bulid);
		$this->_bulid=$is_bulid;
	}
	/**
	 * 设置配置
	 * @param PageAssets $page_assets
	 * @param Config $config
	 * @return \LSYS\PageAssets\Merge
	 */
	public function setPageAssets(PageAssets $page_assets,Config $config){
		$this->_page_assets=$page_assets;
		$this->_config=$config;
		$_defdir="./";
		$this->_file_path=$config->get("file_path",$_defdir);
		$this->_merge_url=(array)$config->get("merge_url","assets/bulid/");
		$this->_merge_path=$config->get("merge_path",$_defdir."bulid/");
		return $this;
	}
	/**
	 * 获取设置的PageAssets
	 * @return \LSYS\PageAssets
	 */
	public function getPageAssets(){
		return $this->_page_assets;
	}
	/**
	 * 添加合并的css
	 * @param CSSFile $css
	 * @param string $attr
	 * @return \LSYS\PageAssets\Merge
	 */
	public function addHeaderCss(CSSFile $css,$attr=[]){
		$this->_header_css[]=func_get_args();
		return $this;
	}
	
	public function bulidCss(CSSFile $css):string{
		return $this->_bulidFile($css,'css',"css_url",$this->_file_path,function($data)use($css){
			return $data;
		});
	}
	protected function _fileGetTime(string $file):int{
		if (!is_file($file))return 0;
		return filemtime($file);
	}
	protected function _bulidFile(AssetsFile $file,?string $type,$tag_handle,?string $path,callable $callback):string{
		if (!$file->isLocal())return $file->path();
		$url=$file->path();
		
		$_bulid_file=$type.'_'.str_replace(array("/","\\",':','.','?','&'),"_", $url).'.'.$type;
		
		if ($this->_bulid){
			$_file=$path.$file->path(false);
			if (is_file($_file)){
				$bulid_file=$this->_merge_path.$_bulid_file;
				if (is_file($bulid_file)){
					if (filemtime($_file)>$this->_fileGetTime($bulid_file)){
						$is_bulid=true;
					}
				}else $is_bulid=true;
				if (isset($is_bulid)&&$is_bulid){
					$_data=file_get_contents($_file);
					$data=call_user_func($callback,$_data);
					if($_data==$data){
						return call_user_func(array($this->_page_assets,$tag_handle),$file);
					}
					$this->_writeBulid($_bulid_file, $data);
				}
			}else{
				return $this->_notfindUrl($file);
			}
		}
		return $this->_bulidUrl($_bulid_file);
	}
	/**
	 * 得到本地文件未找到路径
	 * @param string $url
	 * @return string
	 */
	protected function _notfindUrl($file):string{
		$url=$this->_page_assets->url($file);
		return $this->_setNotfindUrl($url);
	}
	/**
	 * 得到本地文件未找到路径
	 * @param string $url
	 * @return string
	 */
	protected function _setNotfindUrl($url):string{
		$notfind='notfind';
		if (strpos($url, '?')===false)return $url."?".$notfind;
		else return $url."&".$notfind;
	}
	/**
	 * 指定的url是否是本对象生成出来的未找到本地文件的路径
	 * @param string $url
	 * @return boolean
	 */
	public function isNotfindUrl(string $url):bool{
		if (substr($url, -7)=='notfind')return true;
		else return false;
	}
	/**
	 * 添加合并的js[顶部]
	 * @param JSFile $css
	 * @param string $attr
	 * @return \LSYS\PageAssets\Merge
	 */
	public function addHeaderJs(JSFile $css,$attr=[]){
		$this->_header_js[]=func_get_args();
		return $this;
	}
	/**
	 * 添加合并的js[底部]
	 * @param JSFile $css
	 * @param string $attr
	 * @return \LSYS\PageAssets\Merge
	 */
	public function addFooterJs(JSFile $css,$attr=[]){
		$this->_footer_js[]=func_get_args();
		return $this;
	}
	
	public function bulidJs(JSFile $js):string{
		return $this->_bulidFile($js,'js',"js_url", $this->_file_path,function($data){
			return $data;
		});
	}
	/**
	 * 把添加的css或js渲染成html
	 * @param array $files
	 * @param string $path
	 * @param string $ext
	 * @param callable $tag_handle
	 * @param callable $file_handle
	 * @return string
	 */
	protected function _renderFile(array $files,$path,$ext,$tag_handle,$file_handle):string{
		$attrs=$_header=$_file=[];
		foreach ($files as $k=>$v){
			/**
			 * @var AssetsFile $_
			 */
			list($_,$attr)=$v;
			if ($_->isLocal()){
				$_file[]=$_->path();
			}else{
				unset($files[$k]);
				$_header[]=call_user_func(array($this->_page_assets,$tag_handle),$this->_page_assets->url($_), $attr);
			}
			$attrs[]=$attr;
		}
		
		$attrs=implode(" ", $attrs);
		if (count($_file)>0){
			$_files=implode(",", $_file);
			$bulid_file=md5($_files).".".$ext;
			if ($this->_bulid){
				if (!is_file($this->_merge_path.$bulid_file))$is_bulid=true;
				$bulid_time=$this->_fileGetTime($this->_merge_path.$bulid_file);
				foreach ($files as $k=>$v){
					list($_file)=$v;
					if (!is_file($path.$_file->path(false))||@filemtime($path.$_file->path(false))>$bulid_time){
						$is_bulid=true;break;
					}
				}
				if (isset($is_bulid)&&$is_bulid){
					$filebody=[];
					foreach ($files as $v){
						list($_file)=$v;
						if (!is_file($path.$_file->path(false))){//文件不存在 不参与编译
							$_header[]=call_user_func(array($this->_page_assets,$tag_handle),$this->_notfindUrl($_file), $attr);
							continue;
						}
						$body=file_get_contents($path.$_file->path(false));
						$filebody[]=call_user_func($file_handle,$_file,$body);
					}
					$filebody=implode("\n", $filebody);
					$filebody="/*file:{$_files}*/\n{$filebody}";
					$this->_writeBulid($bulid_file, $filebody);
				}
			}
			$url=$this->_bulidUrl($bulid_file);
			$_header[]=call_user_func(array($this->_page_assets,$tag_handle),$url, $attr);
		}
		return implode("", $_header);
	}
	/**
	 * 得到完整url
	 * @param string $file
	 * @return string
	 */
	protected function _bulidUrl(string $file):string{
		if (count($this->_merge_url)==0)$url='./';
		else $url=$this->_merge_url[rand(0,count($this->_merge_url)-1)];
		return $url.$file;
	}
	/**
	 * 合并和压缩js和css
	 * @param string $html
	 * @return mixed
	 */
	public function render(string $html):string{
		if (count($this->_header_css)==0
			&&count($this->_header_js)==0
			&&count($this->_footer_js)==0)return $html;
		$css_header=$this->_renderFile(
			$this->_header_css, 
			$this->_file_path, 
			"css", 
			"css_tag",
			function($css,$body){
				return $body;
			});
		$js_header=$this->_renderFile(
			$this->_header_js, 
			$this->_file_path, 
			"js", 
			"js_tag",
			function($js,$body){
				return $body;
			});
		$js_footer=$this->_renderFile(
			$this->_footer_js, 
			$this->_file_path, 
			"js", 
			"js_tag",
			function($js,$body){
				return $body;
			});
		$pos=stripos($html, '</head>');//HEAD结束位置
		if ($pos===false){//没有head,加到HTML标签后
			$pos=stripos($html, '<html');
			if($pos!==false) $pos=stripos($html, '>',$pos);
		}
		if($pos===false)$pos=0;
		$header=substr($html, 0,$pos);
		
		
		$_pos=stripos($header,"<style");
		if ($_pos!==false)$pos=$_pos;
		if ($pos===false)$html=$css_header.$html;
		else{
			$html=substr_replace($html, $css_header, $pos,0);
			$pos=$pos+strlen($css_header);
			$header=substr($html, 0,$pos);
		}
		
		$_pos=stripos($header,"<script");
		if ($_pos!==false) $pos=$_pos;
		if ($pos===false)$html=$html.$js_header;
		else {
			$html=substr_replace($html, $js_header, $pos,0);
		}
		unset($header);
		$pos=stripos($html, '</body>');
		if ($pos===false)$pos=stripos($html, '</html>');
		$html=substr_replace($html, $js_footer, $pos,0);
		return $html;
	}
	/**
	 * 写压缩缓存
	 * @param string $file
	 * @param string $data
	 * @throws Exception
	 */
	protected function _writeBulid(string &$file,string $data):void{
		$file=str_replace(["'",'"'], "-", $file);
		$dir=dirname($this->_merge_path.$file);
		if (!is_dir($dir)) throw new Exception(strtr("assets bulid dir not find,plase check your assets config [:dir]",array(":dir"=>$dir)));
		if (is_file($this->_merge_path.$file)&&!is_writable($this->_merge_path.$file)){
			throw new Exception(strtr("file can't be write [:file]",array(":file"=>$this->_merge_path.$file)));
		}
		if(!@file_put_contents($this->_merge_path.$file,$data)){
			throw new Exception(strtr("write file fail [:file]",array(":file"=>$this->_merge_path.$file)));
		}
	}
}

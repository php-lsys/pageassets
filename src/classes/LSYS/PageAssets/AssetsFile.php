<?php
namespace LSYS\PageAssets;
class AssetsFile{
	protected $_is_local;
	protected $_path;
	protected $_version;
	public function __construct(string $path,?string $version=null){
		$this->_is_local=!$this->_isRemote($path);
		$this->_path=$path;
		$this->_version=$version;
	}
	protected function _isRemote($url):bool{
		foreach (['//','http://','https://'] as $v){
			if (strncmp($url,$v,strlen($v))===0)return true;
		}
		return false;
	}
	protected function _urlVersion(string $url):string{
		if (empty($this->_version))return $url;
		if (strpos($url,'?')===false)return $url.'?'.$this->_version;
		return $url.'&'.$this->_version;
	}
	/**
	 * 资源相对路径
	 * @param string $version
	 * @return string
	 */
	public function path(bool $version=true):string{
		if (!$version)return $this->_path;
		return $this->_urlVersion($this->_path);
	}
	/**
	 * 设置为非本地资源
	 * @return \LSYS\PageAssets\AssetsFile
	 */
	public function setNotLocal(){
		$this->_is_local=false;
		return $this;
	}
	/**
	 * 是否是本地资源
	 * @return bool
	 */
	public function isLocal():bool{
		return $this->_is_local;
	}
}
<?php
/**
 * 字符串代理
 *
 * 实现字符串的各种可能操作及过滤，内容的格式化等
 *
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: string.php 1 2011-04-08 07:42:35Z xiaoping1 $
 * @copyright
 * @license
 */

class Plugin_String extends Leb_Plugin_Abstract
{

	/**
	 * 字符串分页
	 *
	 * 返回一个二维数组，
	 *
	 * @param string $string
	 * @param int $size
	 * @param boolean|array
	 * @return array
	 */
	static public function paginString($string, $size, $withTag=false)
	{
		$count = mb_strlen($string);
		$paginator = Leb_Page::getInstance();
		$paginator->setTotalSize($count);
		$paginator->setPageSize($size);
		// 偏移点
		$offset = $paginator->getRecordOffset();
		if (!$withTag) {
			$string = strip_tags($string);
		}
		$subString = mb_substr($string, $offset, $size);

		// 分页供显示
		$array = $paginator->toArray();
		$pager = array();
		// 当前url
		$request = Leb_Request::getInstance();
		$currentUrl = $request->getUri();
		$currentUrl = preg_replace("/[&?]?{$array['tag']}=[^$?]*/", '',$currentUrl);

		if (false === strstr($currentUrl, '?')) {
			$pagerLinker = '?';
		} else {
			$pagerLinker = '&';
		}
		$pageUrl = $currentUrl . $pagerLinker . $array['tag'] . "=";
		// 每一页
		for($i=1;$i<=$array['totalPage'];$i++){
			$pager['all'][$i] = $pageUrl . $i;
		}

		// 上一页
		if ($array['currentPage']<=1) {
			$prePage = 1;
		} else {
			$prePage = $array['currentPage']-1;
		}
		$pager['pre'] = $pageUrl . $prePage;

		// 下一页
		if ($array['currentPage']>=$array['totalPage']) {
			$nextPage = $array['totalPage'];
		} else {
			$nextPage = $array['currentPage']+1;
		}
		$pager['next'] = $pageUrl . $nextPage;

		// 尾页
		$pager['end'] = $pageUrl . $array['totalPage'];

		// 首页
		$pager['first'] = $pageUrl . 1;

		// 当前页
		$pager['current'] = $pageUrl .$array['currentPage'];

		// 分开的内容
		$pager['content'] = $subString;
		return $pager;
	}

	/**
	 * 把内容格式化为WML支持的格式并且美化
	 *
	 * @param string $string
	 * @return string
	 */
	static public function formatAsWML($string)
	{

		return $string;
	}

	/**
	 * 格式化为代码格式
	 *
	 * @param string $string
	 * @return string
	 */
	static public function formatAsCode($string)
	{
		return $string;
	}

	/**
	 * 格式化里边的链接及替换字符为相关
	 * @param string $string 内容
	 * @param array $replaceTag 要替换的格式
	 * @param array $replaceWith 替换成
	 * @return string
	 */
	static public function superFormat($string, $replaceTag=array(), $replaceWith=array())
	{
		return $string;
	}
}


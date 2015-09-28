<?php
/**
 * 分页处理类
 *
 * @category   Leb
 * @package    Leb_Plugin
 * @author 	   liuxp
 * @version    $Id: pager.php 1 2011-04-08 07:42:35Z xiaoping1 $
 * @copyright
 * @license
 */

class Plugin_Pager{
    // 起始行数
    public $firstRow;
    // 列表每页显示行数
    public $pageSize;
    // 页数跳转时要带的参数
    public $parameter;
    // 分页总页面数
    protected $totalPages;
    // 总行数
    protected $totalRows;
    // 当前页数
    public $nowPage;

    // 分页的栏的总页数
    protected $coolPages;
    // 分页栏每页显示的页数
    protected $rollPage;
	// 分页显示定制
    protected $config =	array(
    							'pageTag'  => 'page', //分页标签
    							'rollPage' => '10',   //显示页号数
    							'pageSize' => '10',   //每页记录数
    							'header'   => '条记录',
    							'prev'     => '上一页',
    							'next'     => '下一页',
    							'first'    => '第一页',
    							'last'     => '最后一页',
    							'theme'    => "共 %totalRow% %header% 第 %nowPage%/%totalPage% 页 %upPage% %first% %prePage% %linkPage% %nextPage% %downPage% %end%"
    					);

    /**
     * 架构函数
     *
     * @access public
     *
     * @param int $totalRows    总的记录数
     * @param int $pageSize     每页显示记录数
     * @param array $parameter  分页跳转的参数
     *
     */
    public function __construct($totalRows,$pageSize=10,$parameter=array()) {
        $this->totalRows = $totalRows;
        $this->parameter = http_build_query($parameter);
        $this->rollPage = $this->config['rollPage'];
        $this->pageSize = !empty($pageSize)?$pageSize:$this->config['pageSize'];
        $this->totalPages = ceil($this->totalRows/$this->pageSize); //总页数
        $this->coolPages  = ceil($this->totalPages/$this->rollPage);
        $pageTag = $this->config['pageTag'];
        $this->nowPage  = !empty($_GET[$pageTag])?$_GET[$pageTag]:1;

        if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        $this->firstRow = $this->pageSize*($this->nowPage-1);
    }

    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }


    /**
     * 分页显示输出
     *
     * @access public
     *
     */
    public function show($url = '') {
        if(0 == $this->totalRows) {
            return '';
        }
        $p = $this->config['pageTag'];
        $nowCoolPage = ceil($this->nowPage/$this->rollPage);
        if ('' == $url) {
            $url = $_SERVER['REQUEST_URI'];
        }
        
        $url .= (strpos($url,'?')?  '&': "?") . $this->parameter;
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params[$p]);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
        //上下翻页字符串
        $upRow   = $this->nowPage-1;
        $downRow = $this->nowPage+1;
        if ($upRow>0){
            $upPage="<a href='".$url."&".$p."=$upRow'>".$this->config['prev']."</a>";
        }else{
            $upPage="";
        }

        if ($downRow <= $this->totalPages){
            $downPage="<a href='".$url."&".$p."=$downRow'>".$this->config['next']."</a>";
        }else{
            $downPage="";
        }
        // << < > >>
        if($nowCoolPage == 1){
            $theFirst = "";
            $prePage = "";
        }else{
            $preRow =  $this->nowPage-$this->rollPage;
            $prePage = "<a href='".$url."&".$p."=$preRow' >上".$this->rollPage."页</a>";
            $theFirst = "<a href='".$url."&".$p."=1' >".$this->config['first']."</a>";
        }

        if($nowCoolPage == $this->coolPages){
            $nextPage = "";
            $theEnd="";
        }else{
            $nextRow = $this->nowPage+$this->rollPage;
            $theEndRow = $this->totalPages;
            $nextPage = "<a href='".$url."&".$p."=$nextRow' >下".$this->rollPage."页</a>";
            $theEnd = "<a href='".$url."&".$p."=$theEndRow' >".$this->config['last']."</a>";
        }
        // 1 2 3 4 5
        $linkPage = "";
        for($i=1;$i<=$this->rollPage;$i++){
            $page=($nowCoolPage-1)*$this->rollPage+$i;
            if($page!=$this->nowPage){
                if($page<=$this->totalPages){
                    $linkPage .= "&nbsp;<a href='".$url."&".$p."=$page'>&nbsp;".$page."&nbsp;</a>";
                }else{
                    break;
                }
            }else{
                if($this->totalPages != 1){
                    $linkPage .= "&nbsp;<span class='current'>".$page."</span>";
                }
            }
        }

        $pageStr = str_replace(
            array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
            array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),$this->config['theme']);

        return $pageStr;
    }

}

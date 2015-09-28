<?php
/**
 * 帖子中奖名单模型
 *
 * @category   Leb
 * @package    Leb_Model
 * @author 	lihui
 * @version   $Id: thread_raffle_winners.php 1 2015-03-24 10:42 $
 * @copyright
 * @license
 */
class Thread_raffle_winners extends Leb_Model
{
    protected $_pk = 'id';
    protected $_tableName = 'a_thread_raffle_winners';
    protected $_daoType = false;

    //添加中奖名单
    public function addWinners($rid,$opt=array())
    {
        if(empty($rid) || empty($opt)) return false;

        $opt['rid'] = $rid;
        $opt['createtime'] = date("Y-m-d H:i:s",time());
        return $this->add($opt);
    }

}
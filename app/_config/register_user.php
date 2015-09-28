<?php
/**
 * 用户权限配置
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: register_user.php 2014-09-03 9:58:00 lihui
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
return $_RIGHTS = array(
    '1' => array(
        'name'=>'admin',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36,38),
            2=>array(14),3=>array(15,16,17,23,34,35,37),4=>array(18,19,20,21,22,30, 39), '40'=>array(25, 27, 36, 8, 41)),
        'default'=>'5'),
    '2' => array(
        'name'=>'周晨捷',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36,38),
            2=>array(14),3=>array(15,16,17,23,34,35,37),4=>array(18,19,20,21,22,30), '40'=>array(25, 27, 36, 8, 41)),
        'default'=>'5'),
    '3' => array(
        'name'=>'张骅',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36,38),2=>array(14),3=>array(15,16,17,23,34,35,37),4=>array(18,19,20,21,22,30)),
        'default'=>'5'),
    '4' => array(
        'name'=>'李慰',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36,38),2=>array(14),3=>array(15,16,17,23,34,35,37),4=>array(18,19,20,21,22,30)),
        'default'=>'5'),
    '5' => array(
        'name'=>'赵靖',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36,38),2=>array(14),3=>array(15,16,17,23,34,35,37),4=>array(18,19,20,21,22,30)),
        'default'=>'5'),
    '6' => array(
        'name'=>'陈丹',
        'limit'=> array(1=>array(5,6,7,8,9,10,13,26),2=>array(14),3=>array(15,16,35,37),4=>array(19,22,30)),
        'default'=>'5'),
    '7' => array(
        'name'=>'袁悦',
        'limit'=> array(1=>array(6,7,8,11,13,32,33),2=>array(14),3=>array(15,16,35),4=>array(19,22)),
        'default'=>'6'),
    '8' => array(
        'name'=>'李茜',
        'limit'=> array(1=>array(6,7,8,9,11,13,25,27,28,29,31,36),2=>array(14),3=>array(15,16),4=>array(19,22)),
        'default'=>'6'),
    '9' => array(
        'name'=>'杨毅',
        'limit'=> array(1=>array(5,6,7,8,10,11,13,36),2=>array(14),3=>array(15,16,17,34),4=>array(19,22)),
        'default'=>'6'),
    '10' => array(
        'name'=>'关晶',
        'limit'=> array(1=>array(6,10,11,13),2=>array(14),3=>array(16),4=>array(22)),
        'default'=>'6'),
    '11' => array(
        'name'=>'徐碧穗',
        'limit'=> array(1=>array(5,6,8,10,11,13,33,7,12,28,31,38),2=>array(14),3=>array(15,16),4=>array(19,22,30)),
        'default'=>'5'),
    '12' => array(
        'name'=>'李辉',
        'limit'=> array(1=>array(6,8,10,11,12,13),3=>array(16),4=>array(20,22)),
        'default'=>'6'),
    '13' => array('default'=>'6'),
    '14' => array(
        'name'=>'王玥',
        'limit'=> array(1=>array(5,6,7,10,11,12,13,28,31,33),2=>array(14),3=>array(16),4=>array(22)),
        'default'=>'6'),
    '15' => array(
        'name'=>'韩广辉',
        'limit'=> array(1=>array(5,6,8,10,11,13,33),2=>array(14),3=>array(15,16),4=>array(19,22,30)),
        'default'=>'5'),
    '16' => array(
        'name'=>'杨广璐',
        'limit'=> array(1=>array(7),4=>array(22)),
        'default'=>'7'),
    '17' => array(
        'name'=>'吕明洋',
        'limit'=> array(1=>array(6,8,26,32),2=>array(14),3=>array(15,34,37),4=>array(19,22)), //
        'default'=>'8'),
    '18' => array(
        'name'=>'樊阳',
        'limit'=> array(1=>array(24),4=>array(22)),
        'default'=>'24'),
    '19' => array(
        'name'=>'张毅鹏',
        'limit'=> array(1=>array(6,7,8,11,13,25,27,28,29,31,36),2=>array(14),3=>array(15,16),4=>array(19,22,25,30)),
        'default'=>'6'),
    '20' => array(
        'name'=>'穆煜',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36,38),2=>array(14),3=>array(15,16,17,23,34,35),4=>array(22,19)),
        'default'=>'5'),
    '21' => array(
        'name'=>'汪慧',
        'limit'=> array(1=>array(6,7,8,10,11,13,24),2=>array(14),3=>array(15,16,17,23),4=>array(18,19,20,21,22)),
        'default'=>'6'),
    '22' => array('default'=>'6'),

    '23' => array(
        'name'=>'张亚庆',
        'limit'=> array(1=>array(6,7,8,10,11,13,24),2=>array(14),3=>array(15,16,17,23),4=>array(18,19,20,21,22)),
        'default'=>'6'),
    '24' => array(
        'name'=>'史盼',
        'limit'=> array(1=>array(6,10,11,13,31),2=>array(14),3=>array(15,16),4=>array(19,22)),
        'default'=>'6'),
    '25' => array(
        'name'=>'范娟',
        'limit'=> array(1=>array(5,6,7,10,11,12,13,28,31),2=>array(14),3=>array(15,16,37),4=>array(19,22)),
        'default'=>'5'),
    '26' => array(
        'name'=>'张庆',
        'limit'=> array(1=>array(13),2=>array(14),3=>array(15),4=>array(22)),
        'default'=>'13'),
    '27' => array(
        'name'=>'李雪玲',
        'limit'=> array(1=>array(6,8),2=>array(14),3=>array(15,23,34,35,37),4=>array(22)),
        'default'=>'8'),
    '28' => array(
        'name'=>'邱咏霞',
        'limit'=> array(1=>array(6,8),2=>array(14),3=>array(15,35,37),4=>array(22)),
        'default'=>'6'),
    '29' => array(
        'name'=>'刘强',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,38),2=>array(14),3=>array(15,16,17,23),4=>array(18,19,20,21,22,30)),
        'default'=>'5'),
    '30' => array(
        'name'=>'王龙飞',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36),2=>array(14),3=>array(15,16,17,23,35,37),4=>array(18,19,20,21,22,30)),
        'default'=>'5'),
    '31' => array(
        'name'=>'陈祖发',
        'limit'=> array(1=>array(6,7,8,9,11,13,25,31, 27),2=>array(14),3=>array(15,16),4=>array(19,22)),
        'default'=>'6'),
    '32' => array(
        'name'=>'王朗',
        'limit'=> array(1=>array(6,10,11,13),2=>array(14),3=>array(15,16),4=>array(19,22)),
        'default'=>'6'),
    '33' => array(
        'name'=>'张旭嵩',
        'limit'=> array(1=>array(6,10,11,13),2=>array(14),3=>array(15,16),4=>array(19,22)),
        'default'=>'6'),
    '34' => array('default'=>'6'),

    '35' => array(
        'name'=>'倪景田',
        'limit'=> array(1=>array(5,6,7,8,9,10,11,12,13,24,25,26,27,28,29,31,32,33,36),2=>array(14),3=>array(15,16,17,23,35),4=>array(18,19,20,21,22,30)),
        'default'=>'5'),
    '36' => array(
        'name'=>'王磊',
        'limit'=> array(1=>array(7,8,12,28,31),2=>array(14),3=>array(15,34,37),4=>array(19,22)),
        'default'=>'8'),
    '37' => array(
        'name'=>'刘嘉祺',
        'limit'=> array(1=>array(6,8,26,32),2=>array(14),3=>array(15,34),4=>array(19,22)),
        'default'=>'8'),
    '38' => array(
        'name'=>'马德岭',
        'limit'=> array(1=>array(6,8,26,32),2=>array(14),3=>array(15,34),4=>array(19,22)),
        'default'=>'8'),

    '39' => array(
        'name'=>'栾雪',
        'limit'=> array(1=>array(6,7,12,28,31),2=>array(14),3=>array(15),4=>array(22)),
        'default'=>'8'),
    '40' => array(
        'name'=>'张淼',
        'limit'=> array(1=>array(6,10,11,13),2=>array(14),3=>array(15,16),4=>array(19,22)),
        'default'=>'6'),
    '41' => array(
        'name'=>'蔺月霞',
        'limit'=> array(1=>array(8),2=>array(14),3=>array(15),4=>array(22)),
        'default'=>'6'),

);
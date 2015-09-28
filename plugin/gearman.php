<?php
/**
 * gearman client 类封装
 *
 * @author liuguagnzhao
 * $Id: gearman.php 1 2012-05-23 10:52:35Z guangzhao $
 */
class plugin_gearman
{
    private $m_config = null;
    private $m_client = null;
    private $m_timeout = 5000; // msec
    private $m_servers = null;
    // private $m_servers = // array('10.207.16.251:4730');
    // array('10.207.0.247:4730', '10.207.0.248:4730');
    // array('10.207.0.248:4730');

    ///< 并行执行时异步返回记录结构
    private $m_nseq = 0;
    private $m_ntasks = array();

    public function __construct()
    {
        $this->m_config = require(_CONFIG_ . 'gearman.php');
        $this->m_timeout = $this->m_config['timeout'];
        $this->m_servers = $this->m_config['servers'];

        $this->m_client = new GearmanClient();
        $this->addServers(implode(',', $this->m_servers));
    }


    public function __destruct()
    {

    }

    /**
     *
     *
     */
    protected function addServers($servers)
    {
        $this->m_servers = explode(',', $servers);
        $this->m_client->addServers($servers);
        $this->m_client->setTimeout($this->m_timeout);
    }

    /**
     * 获取最后一次错误信息
     *
     */
    public function getError()
    {
        return $this->m_client->error();
    }

    /**
     * 获取最后一次错误号
     *
     */
    public function getErrno()
    {
        return $this->m_client->getErrno();
    }

    /**
     * 异步执行任务
     *
     * @param string $function_name 异步任务名称
     * @param mixed $workload 异步任务参数
     * @return mixed 异步任务执行结果
     *
     * 在集群中任意worker结点上执行，只执行一次，不等待返回结果
     */
    public function runAsync($function_name, $workload)
    {
        $json_result = $this->m_client->doBackground($function_name,
                                                     $this->serial_workload(empty($workload) ? $function_name : $workload),
                                                     $this->make_task_unique($function_name));
        $result = json_decode($json_result, true);

        return $result;
    }

    /**
     * 在指定的worker结点上异步执行一个任务
     *
     * 每个结点上只执行一次，多个host执行多次，不等待返回结果
     */
    public function runAsyncHosts($function_name, $workload, $hosts)
    {
        $thosts = array();
        if (!is_array($hosts)) {
            $thosts[0] = $hosts;
        } else {
            $thosts = $hosts;
        }

        $results = array();
        if (count($thosts)) {
            foreach ($thosts as $idx => $host) {
                $hostip = $host;
                if (strstr(':', $host)) {
                    $ary = explode(':', $host);
                    $hostip = $ary[0];
                }
                $fname = $this->make_specific_worker($function_name, $hostip);
                $results[$ary[0]] = $this->runAsync($fname, $workload);
            }
        }

        return $results;
    }

    /**
     * 在集群中所有的worker结点上异步执行一个任务
     *
     * 每个结点上只执行一次，多个host执行多次，不等待返回结果
     */
    public function runAsyncAllHosts($function_name, $workload)
    {
        return $this->runAsyncHosts($this->m_servers, $function_name, $workload);
    }

    /**
     * 在指定的$hosts内选择任意一个worker结点上异步执行一个任务
     *
     * 每个结点上只执行一次，仅有其中一个host会执行，不等待返回结果
     */
    public function runAsyncAnyHosts($function_name, $workload, $hosts)
    {
        $thosts = array();
        if (!is_array($hosts)) {
            $thosts[0] = $hosts;
        } else {
            $thosts = $hosts;
        }

        $host_count = count($thosts);
        $results = array();
        if ($host_count) {
            $host = $thosts[rand(0, $host_count - 1)];
            $hostip = $host;
            if (strstr(':', $host)) {
                $ary = explode(':', $host);
                $hostip = $ary[0];
            }
            $fname = $this->make_specific_worker($function_name, $hostip);
            $results = $this->runAsync($fname, $workload);
        }

        return $results;
    }

    /*
     * 立刻同步执行一个任务
     * 
     * 只执行一次，并等待返回执行结果
     */
    public function run($function_name, $workload)
    {
        $json_result = $this->m_client->doNormal($function_name,
                                                 $this->serial_workload(empty($workload) ? $function_name : $workload),
                                                 $this->make_task_unique($function_name));
        $result = json_decode($json_result, true);

        return $result;
    }

    /**
     * 在指定的worker结点上同步执行一个任务
     *
     * 每个结点上只执行一次，多个host则执行多次，并等待返回结果
     */
    public function runHosts($function_name, $workload, $hosts)
    {
        $thosts = array();
        if (!is_array($hosts)) {
            $thosts[0] = $hosts;
        } else {
            $thosts = $hosts;
        }

        $results = array();
        if (count($thosts)) {
            foreach ($thosts as $idx => $host) {
                $hostip = $host;
                if (strstr(':', $host)) {
                    $ary = explode(':', $host);
                    $hostip = $ary[0];
                }

                $fname = $this->make_specific_worker($function_name, $hostip);
                $results[$ary[0]] = $this->run($fname, $workload);
            }
        }

        return $results;
    }

    /**
     * 在所有的worker结点上同步执行一个任务
     *
     * 每个只执行一次，多个host则执行多次，并等待返回结果
     */
    public function runAllHosts($function_name,  $workload)
    {
        return $this->runHosts($this->m_servers, $function_name, $workload);
    }


    /**
     * 在指定$hosts中选择任意一个worker结点上同步执行一个任务
     *
     * @param $hosts array(ip1,ip2 ...)
     *
     * 每个只执行一次，仅其中一个host执行，并等待返回结果
     */
    public function runAnyHosts($function_name,  $workload, $hosts)
    {
        $thosts = array();
        if (!is_array($hosts)) {
            $thosts[0] = $hosts;
        } else {
            $thosts = $hosts;
        }

        $host_count = count($hosts);
        $results = array();
        if ($host_count) {
            $host = $thosts[rand(0, $host_count - 1)];
            $hostip = $host;
            if (strstr(':', $host)) {
                $ary = explode(':', $host);
                $hostip = $ary[0];
            }

            $fname = $this->make_specific_worker($function_name, $hostip);
            $results = $this->run($fname, $workload);
        }

        return $results;
    }

    /**
     * 并行执行多个异步任务
     *
     * @param $tasks array(function_name => $workload, ...)
     * @return array(function_name => returnvalue, ...)
     */
    public function runAsyncParallel($tasks)
    {
        $nseq = $this->m_nseq = $this->m_nseq + 1;
        foreach ($tasks as $function_name => $workload) {
            $gmtask = $this->m_client->addTaskBackground($function_name, 
                                               $this->serial_workload(empty($workload) ? $function_name : $workload),
                                               $nseq,
                                               $this->make_task_unique($function_name));

        }
        $this->m_client->setCompleteCallback(array($this, 'task_complete'));

        // block here，wait for all task done.
        $bret = $this->m_client->runTasks();
        if (!$bret) {
            return false;
        }

        // 放nan 1615
    }

    /**
     * 并行执行多个同步任务
     *
     * 执行时间为用时最长的任务
     *
     * @param $tasks array(function_name => $workload, ...)
     * @return array(function_name => returnvalue, ...)
     */
    public function runParallel($tasks)
    {
        $nseq = $this->m_nseq = $this->m_nseq + 1;
        $this->m_ntasks[$nseq] = array();
        foreach ($tasks as $function_name => $workload) {
            $gmtask = $this->m_client->addTask($function_name, 
                                               $this->serial_workload(empty($workload) ? $function_name : $workload),
                                               $nseq,
                                               $this->make_task_unique($function_name));

        }
        $this->m_client->setCompleteCallback(array($this, 'p_task_complete'));

        // block here，wait for all task done.
        $bret = $this->m_client->runTasks();
        if (!$bret) {
            return false;
        }

        $tresults = $this->m_ntasks[$nseq];
        unset($this->m_ntasks);

        foreach ($tresults as $tseq => $result) {
            
        }

        return $tresults;
    }

    /*
     * private方法不能用作回调函数
     */
    public function p_task_complete($task, $nseq)
    {
        $data = $task->data();

        $count = count($this->m_ntasks[$nseq]);
        $this->m_ntasks[$nseq][$count]['task'] = $task;        
        $this->m_ntasks[$nseq][$count]['data'] = $data;
    }

    /*
     * 
     */
    public function p_task_created($task, $nseq)
    {

    }

    public function p_task_data($task, $nseq)
    {

    }

    public function p_task_workload($task, $nseq)
    {

    }

    public function p_task_exception($task, $nseq)
    {

    }

    public function p_task_fail($task, $nseq)
    {
    }

    public function p_task_status($task, $nseq)
    {

    }

    /**
     * 设定每日定时执行的异步任务 (experimental)
     *
     * 定时不停止执行，直接取消此任务，不等待，无返回结果
     *
     * @param $cron_on crontab 中的时间部分，如"5 * * * *"
     */
    public function runCron($function_name,  $workload, $cron_on)
    {
        $cron_args = array('op' => 'U', 'on' => $cron_on
                           , 'cmd' => $function_name
                           , 'name' => $function_name
                           , 'args' => $workload
                           );
        $mres = $this->run('crontab', $cron_args);

        return $mres;
    }

    /**
     * 取消一个定时任务 (experimental)
     *
     *
     */
    public function cancelCronJob($function_name,  $workload)
    {
        $cron_args = array('op' => 'D', 'on' => ''
                           , 'cmd' => $function_name
                           , 'name' => $function_name
                           , 'args' => $function_name
                           );
        $mres = $this->runAllHosts('crontab', $cron_args);
        
        return $mres;
    }

    /**
     * 获取集群中有效的定时任务列表 (experimental)
     *
     *
     */
    public function getAllCrons($function_name)
    {
        $cron_args = array('op' => 'R', 'on' => ''
                           , 'cmd' => $function_name
                           , 'name' => $function_name
                           , 'args' => $function_name
                           );
        $mres = $this->runAllHosts('crontab', $cron_args);

        return $mres;
    }
    
    /**
     * 在未来某时间执行异步任务  (experimental)
     *
     * 只执行不次，不等待，无返回结果
     */
    public function runDelay($function_name, $workload, int $delay)
    {
        
    }
    
    /**
     * 取消未来某时间f捃的异步任务  (experimental)
     *
     */
    public function cancelDelayJob($function_name)
    {
        
    }

    /**
     * 获取当前有效的延时任务  (experimental)
     */
    public function getAllDelayJobs($function_name)
    {

    }

    /**
     * 集群管理相关方法
     */

    /** 
     * 获取gearmand(job server)节点列表
     */
    public function jobNodes()
    {
        $worker = 'get_nodes';
        $result = $this->run($worker, $worker);
        
        return $result;
    }


    /**
     * 获取worker server节点列表
     *
     */
    public function workerNodes()
    {
        $worker = 'get_workers';
        $result = $this->run($worker, $worker);

        $worker_ips = array_keys($result);
        return $worker_ips;
    }

    /**
     * 获取所有可用worker名列表
     *
     */
    public function workers()
    {
        $worker = 'get_workers';
        $result = $this->run($worker, $worker);

        $worker_names = array();
        
        if (!empty($result)) {
            foreach ($result as $wip => $names) {
                if (!empty($names)) {
                    foreach ($names as $k => $name) {
                        if (!in_array($name, $worker_names)) {
                            $worker_names[] = $name;
                        }
                    }
                }
            }
        }

        return $worker_names;
        return $result;
    }

    /**
     * 生成任务unique编号
     *
     * 格式： gearman_task_uid_函数名_随机uid_时间秒
     */
    private function make_task_unique($function_name)
    {
        $pure_function_name = $function_name;
        if (strpos($function_name, 'gmworker_node_') === 0) {
            $mats = array();
            // echo $function_name; // gmworker_node_10.207.0.247:4370_dummy
            $function_name = preg_match('/^gmworker\_node\_([0-9\.]+)\_(.+)/i',
                                        $function_name, $mats);
            $pure_function_name = $mats[2];
        }
        $uid = $pure_function_name . '_' . uniqid() . '_' . date('YmdHis');
        // $uid = uniqid() . '_' . date('YmdHis');
        return $uid;
    }

    private function make_specific_worker($function_name, $host)
    {
        $hostip = $host;
        if (strstr(':', $host)) {
            $ary = explode(':', $host);
            $hostip = $ary[0];
        }
        $fname = "gmworker_node_" . $hostip . '_' . $function_name;
        return $fname;
    }

    private function serial_workload($workload)
    {
        if (function_exists('json_encode')) {
            $jworkload = null;
            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                $jworkload = json_encode($workload, JSON_UNESCAPED_UNICODE);
            } else {
                $jworkload = json_encode($workload);
            }
            return $jworkload;
        } else {
            return $workload;
        }
        // should be no use
        assert(1 == 2);
        return $workload;
    }

    private function unserial_workload($jworkload)
    {
        if (function_exists('json_decode')) {
            $workload = null;
            if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                $workload = json_decode($jworkload, true);
            } else {
                $workload = json_decode($jworkload, true);
            }
            return $workload;
        } else {
            return $jworkload;
        }
        // should be no use
        assert(1 == 2);
        return $jworkload;
    }
}


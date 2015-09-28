<?php
/**
 * 渠道统计
 *
 * @category   Leb
 * @package    Leb_Action
 * @author     lihui
 * @version    $Id: channel.php 2015-06-029 9:58:00 zw
 * @copyright (c) 2014 dianjoy.com
 * @license
 */
class expenseController extends Application
{

    private $expenseModel;

    private $error;


    public function  execute($plugins)
    {
        $this->expenseModel = $this->loadModel('Expense');
        $this->error = [];

    }

    public function indexAction()
    {
        $company = daddslashes($this->reqVar('company', ''));

        $page = (int)$this->reqVar('page', 1);
        $s_n = 60 * ($page - 1); // 每页取60条数据
        $e_n = 60 * ($page);

        $pageUrl = "/admin/expense/";

        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', date('Y-m-d', time())));
        if (!empty($startTime))
        {
            $pageUrl .= "&start_time=$startTime";
        }

        if (!empty($endTime))
        {
            $endTime = date("Y-m-d", strtotime($endTime . ' + 1 day'));

            $pageUrl .= "&end_time=$endTime";
        }

        if (!empty($company))
        {
            $pageUrl .= "&company=$company";

            $List = $this->expenseModel->query("SELECT DISTINCT(company) FROM t_company_expense ;");
            $company_list = " AND company in (";

            foreach ($List as $var)
            {
                if (strstr($var['company'], $company))
                    $company_list .= "'" . $var['company'] . "', ";
            }
            $company_list .= "'_')";

            $expenseList = $this->expenseModel->query("SELECT * FROM t_company_expense WHERE flag ='1' {$company_list} AND ctime > '$startTime' AND ctime < '$endTime' ORDER BY id DESC LIMIT $s_n, $e_n");
            $expenseCount = $this->expenseModel->query("SELECT COUNT(*) FROM t_company_expense WHERE flag ='1' {$company_list} AND ctime > '$startTime' AND ctime < '$endTime' ;")[0]["COUNT(*)"];
        }
        else{
            $sql = "SELECT * FROM t_company_expense WHERE flag ='1' AND ctime > '$startTime' AND ctime < '$endTime' ORDER BY id DESC LIMIT $s_n, $e_n";
            $expenseList = $this->expenseModel->query($sql);
            $sql = "SELECT COUNT(*) FROM t_company_expense WHERE flag ='1' AND ctime > '$startTime' AND ctime < '$endTime';";
            $expenseCount = $this->expenseModel->query($sql)[0]["COUNT(*)"];

        }
        foreach ($expenseList as &$var)
        {
            if($var['remark'] == '0')
                $var['remark'] = '';
            if($var['num_promo'] == '0')
                $var['num_promo'] = '';
            if($var['price'] == '0')
                $var['price'] = '';
        }

        $expensePages = pages($expenseCount, $page, 60, $pageUrl, $array = array());

        $this->assign('company', $company);
        $this->assign('startTime', $startTime);
        $endTime = date("Y-m-d", strtotime($endTime . ' - 1 day'));
        $this->assign('endTime', $endTime);

        $this->assign('expensePages', $expensePages);
        $this->assign("expenseList", $expenseList);
        $this->assign('adminId', UID);
        $this->getViewer()->needLayout(false);

        $this->render('expense_stat_list');
    }

    public function addAction()
    {

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $this->getViewer()->needLayout(false);
            $this->render('expense_stat_add');
        }
        else
        {
            $company = $_POST["company"] ? $_POST["company"] : "";
            $channel = $_POST["channel"] ? $_POST["channel"] : "";
            $ctime = date("Y-m-d H:i:s", time());
            $start_time = $_POST["start_time"] ? $_POST["start_time"] : "";
            $end_time = $_POST["end_time"] ? $_POST["end_time"] : "";
            $num_promo = $_POST["num_promo"] ? $_POST["num_promo"] : "";
            $price = $_POST["price"] ? $_POST["price"] : "";
            $expense = $_POST["expense"] ? $_POST["expense"] : "";
            $person = $_POST["person"] ? $_POST["person"] : "";
            $remark = $_POST["remark"] ? $_POST["remark"] : "";
            $remark = $_POST["code"] ? $_POST["code"] : "";  // 合同编号

            $this->expenseModel->execute("INSERT INTO t_company_expense (company, channel, ctime, start_time, end_time, num_promo, price, expense, person, remark, flag)
                                                VALUES ('$company', '$channel', '$ctime', '$start_time', '$end_time', $num_promo, $price, $expense, '$person', '$remark','1')
                                                    ");

            $this->getViewer()->needLayout(false);
            $this->redirect("添加成功", "/admin/expense");
        }

    }


    public function uploadAction()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $this->getViewer()->needLayout(false);
            $this->render('expense_upload');
        } else
        {
            $fileUpload = $_FILES['file_uplode'];
            $kk = $this->uploadFile($fileUpload);

            $this->getViewer()->needLayout(false);
            if(!empty($this->error))
                $str = var_export($this->error, TRUE);
            else
                $str = '无';
            $this->redirect("{$kk[0]}条添加成功, {$kk[1]}条添加失败, 失败:{$str}", "/admin/expense", 0);
        }
    }


    private function uploadFile($file_upload)
    {
        if (empty($file_upload))
        {
            die("上传文件失败");
            return false;
        }

        $path = dirname(dirname(__FILE__)) . "/data/expense/"; //上传路径
        if (!file_exists($path))
        {
            mkdir("$path", 0700);
        }
        //允许上传的文件格式
        $tp = array("text/plain", "text/json", "text/csv", "application/octet-stream", "application/vnd.ms-excel");
        if (!in_array($file_upload["type"], $tp))
        {
            var_dump($file_upload["type"]);

            die("上传文件失败");
            return false;
        }
        $list_from_file = array();
        $kk[0] = 0;
        $kk[1] = 0;

        if ($file_upload["name"])
        {
            $tmp_name = explode(".", $file_upload["name"]);
            $file2name = md5_file($file_upload["tmp_name"]) . '.' . $tmp_name[1];
            $file2 = $path . $file2name;
            $result = move_uploaded_file($file_upload["tmp_name"], $file2);

            $handle = @fopen($file2, "r");

            if ($handle)
            {
                while (!feof($handle))
                {
                    $bufferz = fgets($handle, 4096);  // 按行读取
                    $buffer = trim($bufferz);
                    if (empty($buffer))
                        continue;
                    $buffer_c2 = explode(",", $buffer);
                    $buffer_c = explode("\t", $buffer);
                    $buffer = count($buffer_c) > count($buffer_c2) ? $buffer_c : $buffer_c2;

                    if(strstr($buffer[0], '业务负责人'))
                        continue;

                    if (count($buffer) >= 11)
                    {
                        $kk[0] += 1;
                        if (count($buffer) > 11)
                        {
                            $tmp = $buffer[9] . $buffer[10];
                            $tmp = trim($tmp, '"');
                            $tmp = trim($tmp);

                            $buffer[9] = $tmp;
                            $buffer[10] = $buffer[11];

                        }
                        $list_from_file[] = $buffer;
                    }
                    else
                    {
                        $kk[1] += 1;
                        $this->error[] = $bufferz;
                    }
                }
                fclose($handle);
            }
        }
        $toDay = date("Y-m-d H:i:s", time());

        foreach ($list_from_file as $var)
        {
            $sql = "INSERT INTO t_company_expense (person, code, company, type, start_time, end_time, channel, num_promo, price, expense, remark, ctime, flag)
                                          VALUES ('{$var[0]}', '{$var[1]}', '{$var[2]}', '{$var[3]}', '{$var[4]}', '{$var[5]}', '{$var[6]}', '{$var[7]}', '{$var[8]}' ,'{$var[9]}', '{$var[10]}', '{$toDay}' ,'1')";
            $this->expenseModel->execute($sql);
        }
        return $kk;
    }


    public function export_dataAction()
    {
        $company = daddslashes($this->reqVar('company', ''));

        $startTime = daddslashes($this->reqVar('start_time', ''));
        $endTime = daddslashes($this->reqVar('end_time', date('Y-m-d', time())));

        if (!empty($endTime))
        {
            $endTime = date("Y-m-d", strtotime($endTime . ' + 1 day'));
        }

        if (!empty($company))
        {
            $List = $this->expenseModel->query("SELECT DISTINCT(company) FROM t_company_expense ;");
            $company_list = " AND company in (";

            foreach ($List as $var)
            {
                if (strstr($var['company'], $company))
                    $company_list .= "'" . $var['company'] . "', ";
            }
            $company_list .= "'_')";

            $expenseList = $this->expenseModel->query("SELECT * FROM t_company_expense WHERE flag ='1' {$company_list} AND ctime > '$startTime' AND ctime < '$endTime' ORDER BY id DESC LIMIT 5000");
        }
        else{
            $sql = "SELECT * FROM t_company_expense WHERE flag ='1' AND ctime > '$startTime' AND ctime < '$endTime' ORDER BY id DESC LIMIT 5000";
            $expenseList = $this->expenseModel->query($sql);
            $expenseCount = $this->expenseModel->query("SELECT COUNT(*) FROM t_company_expense WHERE flag ='1' AND ctime > '$startTime' AND ctime < '$endTime';")[0]["COUNT(*)"];
        }

        $excelContent = $this->export_template($expenseList);
        if (empty($excelContent))
        {
            $this->redirect('导出失败,没有导出内容!', '', 1);
            die();
        }
        $excelData = $excelContent;
        header('Content-type:application/vnd.ms-excel;charset=utf-8');
        header("Content-Disposition:filename=expense.csv");
        echo $excelData;
    }

    private function export_template($expenseList)
    {
        if (!$expenseList) return;

        $replaceArr = array("・", "&nbsp;", " ", "•");
        $excelContent = "'业务负责人,合同编号,收款公司名称,费用类型,结算开始日期,结算结束日期,渠道号,结算数据,结算单价（元）,结算金额（元）\r\n";
        foreach ($expenseList as $key => $val)
        {
//            person, code, company, type, start_time, end_time, channel, num_promo, price, expense,
            $excelContent .= $val['person'] . ',' . $val['code'] . ',' . $val['company'] . ',' . $val['type']
                . ',' . substr($val['start_time'], 0, 10) . ',' . substr($val['end_time'], 0, 10). ',' . $val['channel'] . ',' . $val['num_promo']
                . ',' . $val['price'] . ',' . $val['expense'] . "\r\n";
        }
        return $excelContent;
    }


    public function delAction()
    {
        $midArr = daddslashes($this->postVar('mid', ''));
        if (!empty($midArr))
        {
            foreach($midArr as $key => $val){
                $this->expenseModel->execute("UPDATE t_company_expense SET flag = '0' where id = $val");
            }
        }
        $this->getViewer()->needLayout(false);
        $this->redirect("删除成功","/admin/expense");
    }
}

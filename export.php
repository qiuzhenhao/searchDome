<?php
//    $user = "root";
//    // $pass = "";
//    $pass = "123456";
//    try {
//        $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
//    } catch(Exception $e) {
//        die('Connect Failed Message: ' . $e->getMessage());
//    }
    // 连接本地memcached
//    $memcache = new Memcache();
//    $memcache->connect('127.0.0.1',11211) or die('shit');

    require ('model.php');
    $memcache = dbLink::memcache();
    $showAmountArr = json_decode($memcache->get('sdTimeAmountListJson'), true);
    $wayList = json_decode($memcache->get('sdWayListJson'), true);
    $tableHead = $memcache->get('sdTableHead');
    $statu = $_GET['statu'];        //公共参数  $statu = 1全部导出   $statu = 2按需导出

    require_once dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel.php';
    $objPHPExcel = new PHPExcel();
    //居中
    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    //自动设置列宽度
    $objPHPExcel -> getActiveSheet() -> getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(0)) -> setAutoSize(true);
    if($statu == 1){
        $title = "导出所有详情";
        //表头
        foreach ($wayList as $key => $value){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
            if($value['name'] == ""){
                $value['name'] = "ios";
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($tableHead[$key].'1', $value['name']);
        }
        //内容
        $rowPosition = 2;   //定位行
        foreach ($showAmountArr as $k => $v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$rowPosition, $k);
            $amountPosition = 0;    //定位列
            foreach ($v as $k_amount => $v_amount){

                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($tableHead[$amountPosition].$rowPosition, $v_amount);
                $amountPosition ++;
            }
            $rowPosition ++;
        }

    }else if($statu == 2){
        $needWay = json_decode($_COOKIE['needWay']);
        $needTime = json_decode($_COOKIE['needTime']);
        //展示数据
        $showList = [];
        foreach ($needTime as $k_nt=>$v_nt){
            $rowShow = [];
            $rowShow[] = $v_nt;
            if($k_nt < count($showAmountArr)){
                foreach ($needWay as $k_nw=>$v_nw){
                    $rowShow[] = $showAmountArr[$v_nt][$v_nw];
                }
            }

            $showList[] = $rowShow;
        }
        $title = "按需导出详情";
        //表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
        foreach ($needWay as $key => $value){
            if($value == ""){
                $value = "ios";
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($tableHead[$key].'1', $value);
        }

        //内容
        $rowPosition = 2;    //定位行
        foreach ($showList as $key=>$value){
            foreach ($value as $k_c =>$v_c){
                $num = 0;
                if($k_c == 0){
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$rowPosition, $v_c);
                }else{
                    $num = (int)$k_c - 1;

                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($tableHead[$num].$rowPosition, $v_c);
                }
            }
            $rowPosition ++;
        }
    }

    //定义配置
    $xlsTitle = iconv('utf-8', 'gb2312', $title);//文件名称
    $fileName = $title.date('_YmdHis');//文件名称

 	 //一致
    $obwrite = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Content-Type:application/force-download');
    header('Content-Type:application/vnd.ms-execl');
    header('Content-Type:application/octet-stream');
    header('Content-Type:application/download');
    header('Content-Disposition:attachment;filename="'.$fileName.'.xls"');
    header('Content-Transfer-Encoding:binary');
    $obwrite->save('php://output');exit;
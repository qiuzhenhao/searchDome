<?php
//    $user = "root";
//    // $pass = "";
//    $pass = "123456";
//    try {
//        $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
//    } catch(Exception $e) {
//        die('Connect Failed Message: ' . $e->getMessage());
//    }

    session_start();
	$way = $_GET['way'];        //渠道
	$time = $_GET['time'];      //日期
    $statu = $_GET['statu'];    //1:导出全部    2:按时间导出    3:按渠道导出

    require_once dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel.php';
    $objPHPExcel = new PHPExcel();
    //居中
    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    //自动设置列宽度
    $objPHPExcel -> getActiveSheet() -> getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(0)) -> setAutoSize(true);
    if($statu == 1){
        $title = "导出所有详情";
        //表头
        foreach ($_SESSION['way'] as $key => $value){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
            if($value['name'] == ""){
                $value['name'] = "无";
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$key].'1', $value['name']);
        }
        //内容
        $lineNum = 2;   //定位行
        foreach ($_SESSION['amount'] as $k => $v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$lineNum, $k);
            $amountPosition = 0;    //定位列
            foreach ($v as $k_amount => $v_amount){

                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$amountPosition].$lineNum, $v_amount);
                $amountPosition ++;
            }
            $lineNum ++;
        }

    }else if($statu == 2){
        $title = "按时间导出详情";
        //表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', $time);
        foreach ($_SESSION['way'] as $key => $value){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
            if($value['name'] == ""){
                $value['name'] = "无";
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$key].'1', $value['name']);
        }
        //内容
        $amountPosition = 0;    //定位列
        foreach ($_SESSION['amount'][$time] as $k_amount => $v_amount){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$amountPosition].'2', $v_amount);
            $amountPosition ++;
        }
    }else{
        $title = "按渠道导出详情";
        if($way == "无"){
            $way = "";
        }
        //表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', $way);
        //内容
        $lineNum = 2;
        foreach ($_SESSION['date'] as $key => $value){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$lineNum, $value['time']);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$lineNum, $_SESSION['amount'][$value['time']][$way]);
            $lineNum ++;
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
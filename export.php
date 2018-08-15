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
    $statu = $_GET['statu'];        //公共参数  $statu = 1全部导出   $statu = 2按需导出
    $needWay = json_decode($_COOKIE['needWay']);
    //判断展示行数
    $listNum = $_COOKIE['listNum'];
    //展示数据
    $showList = [];
    for($i = 0; $i < $listNum; $i ++){
        $showList[] = json_decode($_COOKIE['showList'.$i]);
    }

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
                $value['name'] = "ios";
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$key].'1', $value['name']);
        }
        //内容
        $rowPosition = 2;   //定位行
        foreach ($_SESSION['amount'] as $k => $v){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$rowPosition, $k);
            $amountPosition = 0;    //定位列
            foreach ($v as $k_amount => $v_amount){

                $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$amountPosition].$rowPosition, $v_amount);
                $amountPosition ++;
            }
            $rowPosition ++;
        }

    }else if($statu == 2){
        $title = "按需导出详情";
        //表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '日期');
        foreach ($needWay as $key => $value){
            if($value == ""){
                $value = "ios";
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$key].'1', $value);
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

                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($_SESSION['tableHead'][$num].$rowPosition, $v_c);
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
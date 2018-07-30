<?php
/**
 * Created by PhpStorm.
 * User: xiaoqiu
 * Date: 2018/7/23
 * Time: 17:12
 */
//$user = "root";
//// $pass = "";
//$pass = "123456";
//try {
//    $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
//} catch(Exception $e) {
//    die('Connect Failed Message: ' . $e->getMessage());
//}
//
session_start();
$name = $_POST["name"];
$time = $_POST["time"];
//$sql = "select count(*) amount from app_channel where channel_name = '{$name}' and  date_format(created,'%Y-%m-%d') = '{$time}'";
////var_dump($sql);
//$amount = $dbh->query($sql);
////$amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
//$rs = $amount->fetchAll();
?>
<script type="text/javascript">
<?php
	if($name == -1 || $time == -1){
		if($name == -1){
?>		
			alert("请选择渠道")
<?php
		}
		if($time == -1){
?>
			alert("请选择日期")
<?php
		}
	}
?>
</script>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>search</title>
</head>
<style>
    body{
        margin-top: 10%;
            margin-left: 40%;
    }
    button{
        width:100px;
    }
    a{

        text-decoration: none;
        margin-right:10px;
    }
</style>
<body>
    <div>
        <p>日期 : <?php echo $time?></p>
        <p>渠道 : <?php echo $name?></p>
        <?php
            if($name == "无"){
                $name = "";
            }
        ?>
        <p>数量 : <?php echo $_SESSION['amount'][$time][$name]?></p>
        <br>
        <a href="index.php">
            <button type="button">返回</button>
        </a>
    </div>

</body>

</html>

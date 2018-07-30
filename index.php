<?php
    $lifeTime = 24 * 3600;
    session_set_cookie_params($lifeTime);
    session_start();

    if($_SESSION['amount'] == null || $_SESSION['way'] == null || $_SESSION['date'] == null){

        $user = "root";
        //$pass = "";
        $pass = "123456";
        $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));

        $sqlName = "select channel_name name from app_channel GROUP BY channel_name";
        $sqlTime = "select date_format(created,'%Y-%m-%d') AS time from app_channel GROUP BY time";

        //获取时间,并设置session
        $date = $dbh->query($sqlTime);
        $date->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
        $rsDate = $date->fetchAll();
        $_SESSION['date'] = $rsDate;
        //获取渠道,并设置session
        $way = $dbh->query($sqlName);
        $way->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
        $rsWay = $way->fetchAll();
        $_SESSION['way'] = $rsWay;

        //由日期和渠道获取数量，储存为二维数组， [日期][渠道]
        foreach ($rsDate as $keyDate=>$valueDate){
            foreach ($rsWay as $keyWay=>$valueWay){
                $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}' 
                    and  date_format(created,'%Y-%m-%d') = '{$valueDate['time']}'";

                $amount = $dbh->query($sql);
                $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
                $rsAmount = $amount->fetchAll();
                $reByWay[$valueWay['name']] = $rsAmount[0]['amount'];
            }
            $reByAll[$valueDate['time']] = $reByWay;

        }
        $_SESSION['amount'] = $reByAll;
    }

    //表格纵列
    $tableHead = [];
    $num = 0;
    for($i = 0; $i < count($_SESSION['way']); $i ++){
        $ch = "";
        $num = $i;
        if($i < 25){
            $ch = chr(ord('B')+$num);
        }else if($i > 24 && $i < 51){
            $num = $num - 25;
            $ch = 'A'.chr(ord('A')+$num);
        }else{
            $num = $num - 51;
            $ch = 'B'.chr(ord('A')+$num);

        }
        $tableHead[] = $ch;
    }
    $_SESSION['tableHead'] = $tableHead;


?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>search</title>
    <link rel="stylesheet" href="amazeui.min.css">
</head>
<style>
    body{
        margin-top: 10%;
        margin-left: 40%;
    }
    select{
        margin-right: 20px;
    }
    input,button{
        width:100px;
        margin-right: 20px;
    }
    .buttonGroup{
        padding-top: 50px;
    }
    .am-modal-bd{
        height: 100%;
        width:100%;
        background-color: white;
    }
    .centent{
        padding-top: 50px;
    }
</style>
<body>
    <div>
        <form action="search.php" method="post">
            <select name="name">
                <option value ="-1">渠道选择</option>
                <?php
                    foreach ($_SESSION['way'] as $row) {
                        if($row['name'] == null || $row['name'] == ""){
                            $row['name'] = "无";
                        }
                ?>
                 <option value ="<?php echo $row['name']; ?>"><?php echo $row['name'];?></option>
                <?php
                    }
                ?>
            </select>

            <select name="time">
                <option value ="-1">日期选择</option>
                <?php
                foreach ($_SESSION['date'] as $row) {
                    if($row['time'] == null || $row['time'] == ""){
                        $row['time'] = "无";
                    }
                    ?>
                    <option value ="<?php echo $row['time']; ?>"><?php echo $row['time'];?></option>
                    <?php
                }
                ?>
            </select>
            <input type="submit"  class="am-btn am-btn-primary am-round" value="查询">
            <button
                    type="button"
                    class="am-btn am-btn-secondary am-round"
                    data-am-modal="{target: '#export', closeViaDimmer: 0, width: 450, height: 200}">
                导出
            </button>
        </form>
    </div>
    <!--   弹出窗 -->
    <div class="am-modal am-modal-no-btn" tabindex="-1" id="export">
        <div class="am-modal-dialog">
            <div class="am-modal-hd">导出选择
                <a href="javascript: void(0)" class="am-close am-close-spin" data-am-modal-close>&times;</a>
            </div>
            <div class="am-modal-bd">
                <div class="buttonGroup">
                    <button type="button" class="am-btn am-btn-success am-round" onclick="exportAll()">导出全部</button>
                    <button type="button" class="am-btn am-btn-secondary am-round" onclick="exportByTime()">按日期导出</button>
                    <button type="button" class="am-btn am-btn-primary am-round" onclick="exportByWay()">按渠道导出</button>
                </div>
            </div>

        </div>
    </div>
    <!--   根据日期导出弹出窗 -->
    <div class="am-modal am-modal-no-btn" tabindex="-1" id="exportByTime">
        <div class="am-modal-dialog" style="width:450px; height: 200px">
            <div class="am-modal-hd">根据日期导出
                <a href="javascript: void(0)" class="am-close am-close-spin" data-am-modal-close>&times;</a>
            </div>
            <div class="am-modal-bd">
                <div class="centent">
                    <select name="time" id="selectTimeValue">
                        <option value ="-1">日期选择</option>
                        <?php
                        foreach ($_SESSION['date'] as $row) {
                            if($row['time'] == null || $row['time'] == ""){
                                $row['time'] = "无";
                            }
                            ?>
                            <option value ="<?php echo $row['time']; ?>"><?php echo $row['time'];?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <button type="button" class="am-btn am-btn-success am-btn-sm" onclick="exportByTimeConfirm()">确定</button>
                </div>

            </div>
        </div>
    </div>
    <!--   根据渠道导出弹出窗 -->
    <div class="am-modal am-modal-no-btn" tabindex="-1" id="exportByWay">
        <div class="am-modal-dialog" style="width:450px; height: 200px">
            <div class="am-modal-hd">根据渠道导出
                <a href="javascript: void(0)" class="am-close am-close-spin" data-am-modal-close>&times;</a>
            </div>
            <div class="am-modal-bd">
                <div class="centent">
                    <select name="name" id="selectWayValue"">
                        <option value ="-1">渠道选择</option>
                        <?php
                        foreach ($_SESSION['way'] as $row) {
                            if($row['name'] == null || $row['name'] == ""){
                                $row['name'] = "无";
                            }
                            ?>
                            <option value ="<?php echo $row['name']; ?>"><?php echo $row['name'];?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <button type="button" class="am-btn am-btn-primary am-btn-sm" onclick="exportByWayConfirm()">确定</button>
                </div>

            </div>
        </div>
    </div>
    <script src="jquery.min.js"></script>
    <script src="amazeui.min.js"></script>
    <script type="text/javascript">
//statu = 1 导出全部    statu = 2 按时间导出    statu = 3 按渠道导出
        function exportAll() {
            window.location = "export.php?statu=1";
            $('#export').modal('close');
        }
        function exportByTime() {
            $('#export').modal('close');
            $('#exportByTime').modal('open');
        }
        function exportByWay() {
            $('#export').modal('close');
            $('#exportByWay').modal('open');
        }
        function exportByTimeConfirm() {
            var time = $('#selectTimeValue option:selected') .val();//选中的值
            if(time == "-1"){
                alert("请先选择时间！！！");
                return;
            }
            window.location = "export.php?statu=2&time="+time;
            $('#exportByTime').modal('close');
        }
        function exportByWayConfirm() {
            var way = $('#selectWayValue option:selected') .val();//选中的值
            if(way == "-1"){
                alert("请先选择渠道！！！");
                return;
            }
            window.location = "export.php?statu=3&way="+way;
            $('#exportByWay').modal('close');
        }
    </script>
</body>
</html>
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
        foreach ($rsWay as $key => $value){
            $rsWay[$key]['check'] = false;
        }

        $reWayIson = json_encode($rsWay);

        $_SESSION['way'] = $reWayIson;

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
    <link rel="stylesheet" href="admin.css">
</head>
<style>

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
    /*.centent{*/
        /*padding-top: 50px;*/
    /*}*/
    .content{
        margin-left: 20%;
        margin-top:2%;
        margin-bottom: 5%;
    }
    .tableSet{
        margin-top: 15px;
        width: 100%;
        height: 100%;
    }
    .tableSet tr{
        height: 50px;
    }
    .tableSet td{
        font-size: 20px;
        border-top:1px dashed #c2c2c2;
        border-bottom:1px dashed #c2c2c2;
    }
    #boxscrol {
        width: 72%;
        height: 850px;
        overflow: auto;
        margin-bottom:20px;
    }
</style>
<body>
    <div id="vueApp">
<!--        <form action="search.php" method="post">-->
<!--            -->
<!--            <select name="name">-->
<!--                <option value ="-1">渠道选择</option>-->
<!--                --><?php
//                    foreach ($_SESSION['way'] as $row) {
//                        if($row['name'] == null || $row['name'] == ""){
//                            $row['name'] = "无";
//                        }
//                ?>
<!--                 <option value ="--><?php //echo $row['name']; ?><!--">--><?php //echo $row['name'];?><!--</option>-->
<!--                --><?php
//                    }
//                ?>
<!--            </select>-->
<!---->
<!--            <select name="time">-->
<!--                <option value ="-1">日期选择</option>-->
<!--                --><?php
//                foreach ($_SESSION['date'] as $row) {
//                    if($row['time'] == null || $row['time'] == ""){
//                        $row['time'] = "无";
//                    }
//                    ?>
<!--                    <option value ="--><?php //echo $row['time']; ?><!--">--><?php //echo $row['time'];?><!--</option>-->
<!--                    --><?php
//                }
//                ?>
<!--            </select>-->
<!--                 <input type="submit"  class="am-btn am-btn-primary am-round" value="查询">-->
<!--        </form>-->

        <div class="content">
            <div class="am-g">
                <div class="am-u-sm-5">
                    <button
                            type="button"
                            class="am-btn am-btn-success "
                            data-am-modal="{target: '#export', closeViaDimmer: 0, width: 450, height: 200}">
                        导出
                    </button>
                    <button type="button" class="am-btn am-btn-secondary  "value="查询"  @click="select()">查询</button>
                </div>

                <div class="am-u-sm-4">
                    <div class="am-g">
                        <div class="am-u-sm-6">
                            <button type="button" class="am-btn am-btn-warning am-margin-right" id="my-start">开始日期</button><span id="my-startDate">2018-7-11</span>
                        </div>
                        <div class="am-u-sm-6">
                            <button type="button" class="am-btn am-btn-warning am-margin-right" id="my-end">结束日期</button><span id="my-endDate"><?php echo date("Y-m-d")?></span>
                        </div>
                    </div>
                </div>
                <div class="am-u-sm-3"></div>
            </div>
            <div id="boxscrol">
                <table class="tableSet">
                    <tr style="background: #BEBEBE">
                        <td  style="width: 50px">
                            <input type="checkbox" id="checkAll" v-model="ischeckAll" @click="checkAll()" style="zoom:130%;">
                        </td>
                        <td>渠道</td>
                    </tr>
                        <tr v-for="(key,value) in wayList">
                            <td style="width: 50px"><input type="checkbox" id="{{key}}" v-model="value.check" style="zoom:130%;"></td>
                            <td ><span v-text="value.name ==''?'无':value.name"></span></td>
                        </tr>
                </table>
            </div>

        </div>
        

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
    <script src="jquery.nicescroll.min.js"></script>
    <script src="vue/vue.min.js"></script>
    <script src="vue/vue-resource.min.js"></script>
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
        //滚动条
        $(document).ready(function() {
            $("#boxscrol").niceScroll("#boxscroll4 .tableSet",{boxzoom:true}); // First scrollable DIV
        });
        //时间组件    startDate起始时间，默认为2018.7.11   endDate结束时间，默认到现在
        $(function() {
            var nowTemp = new Date();
            var startDate = new Date(2018, 6, 11);
            var endDate = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate());
            var nowDay = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0).valueOf();
            var nowMoth = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), 1, 0, 0, 0, 0).valueOf();
            var nowYear = new Date(nowTemp.getFullYear(), 0, 1, 0, 0, 0, 0).valueOf();
            $('#my-start').datepicker({
                onRender: function(date, viewMode) {
                    // 默认 days 视图，与当前日期比较
                    var viewDate = startDate.getTime();
                    switch (viewMode) {
                        // moths 视图，与当前月份比较
                        case 1:
                            viewDate = nowMoth;
                            break;
                        // years 视图，与当前年份比较
                        case 2:
                            viewDate = nowYear;
                            break;
                    }
                    return date.valueOf() < viewDate ? 'am-disabled' : '';
                }
            }).on('changeDate.datepicker.amui', function(event) {
                if (event.date.valueOf() > endDate.valueOf()) {
                    alert('开始日期应小于结束日期！');
                } else {
                    startDate = new Date(event.date);
                    $('#my-startDate').text($('#my-start').data('date'));
                }
                $(this).datepicker('close');
            });
            $('#my-end').datepicker({
                onRender: function(date, viewMode) {
                    // 默认 days 视图，与当前日期比较
                    var viewDate = nowDay;
                    switch (viewMode) {
                        // moths 视图，与当前月份比较
                        case 1:
                            viewDate = nowMoth;
                            break;
                        // years 视图，与当前年份比较
                        case 2:
                            viewDate = nowYear;
                            break;
                    }
                    return date.valueOf() > viewDate ? 'am-disabled' : '';
                }
            }).on('changeDate.datepicker.amui', function(event) {
                if (event.date.valueOf() < startDate.valueOf()) {
                    alert('结束日期应大于开始日期！');
                } else {
                    endDate = new Date(event.date);
                    $('#my-endDate').text($('#my-end').data('date'));
                }
                $(this).datepicker('close');
            });
        });

        //
        // vue
        //
        var vm = new Vue({
            el: '#vueApp',
            data: {
                //全选
                ischeckAll:false,
                //渠道列表
                wayList:[],
            },
            created: function(){
                this.getList();
            },
            methods: {
                //获取列表
                getList: function(){
                    this.wayList = <?php echo $_SESSION['way']?>
                },
                //全选或者反选
                checkAll:function () {
                    for(var i = 0; i < this.wayList.length; i++){
                        this.wayList[i].check = !this.ischeckAll
                    }
                },
                //查询
                select:function () {
                    //判断是否有选择渠道
                    var amount = 0;
                    for(var i = 0; i < this.wayList.length; i++){
                        if(this.wayList[i].check){
                            amount++;
                        }
                    }
                    if(amount == 0){
                        alert("请先选择渠道");
                    }
                    //获取起始时间和结束时间
                    var startTime = $("#my-startDate").text();
                    var endTime = document.getElementById("my-endDate").innerText;
                    console.log(startTime);
                }
            }
        })
    </script>
</body>
</html>
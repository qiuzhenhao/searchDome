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
        $reWayJson = json_encode($rsWay);
        $_SESSION['way'] = $reWayJson;

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
        $reAmountJson = json_encode($reByAll);
        $_SESSION['amount'] = $reAmountJson;
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
    .selectPage{
        margin-left: 17%;
        margin-top:2%;
        margin-bottom: 5%;
    }
    .selectPage .top{
        width: 80%;
        height: 80px;
        text-align: center;
    }
    .selectPage .top button{
        float:right;
        margin-top:30px;
        margin-right:50px;
    }
    .selectPage .tableSetSelect{
        margin-top: 15px;
    }
    .selectPage .tableSetSelect tr td{
        word-break: keep-all;
        white-space:nowrap;
        padding: 0px 8px 0px 8px;
        height: 70px;
        text-align: center;
    }
    #boxscrol2 {
        width: 1250px;
        height: 850px;
        overflow: auto;
        margin-bottom:20px;
    }
    table td{

    }
</style>
<body>
    <div id="vueApp">
        <div class="content" v-if="statu == 1">
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
                            <button type="button" class="am-btn am-btn-warning am-margin-right" id="my-start">开始日期</button><span id="my-startDate">2018-7-10</span>
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
        <div class="selectPage" v-if="statu == 2">
            <div class="top">
                <span style="font-size: 50px; color:#87CEEB">查询结果</span>
                <button type="button" class="am-btn am-btn-secondary am-round" @click="backList()">返回列表</button>
            </div>
            <div id="boxscrol2">
                <table class="tableSetSelect" border="1" cellspacing="0">
                    <tr>
                        <td>日期</td>
                        <td v-for="way in needSelectWay" v-text="way == ''?'无':way"></td>
                    </tr>
                    <tr v-for="time in showList">
                        <td v-for="amount in time" v-text="amount"></td>
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
            $("#boxscrol").niceScroll("#boxscrol .tableSet",{boxzoom:true}); // First scrollable DIV
            $("#boxscrol2").niceScroll();
        });
        //时间组件    startDate起始时间，默认为2018.7.10   endDate结束时间，默认到现在
        $(function() {
            var nowTemp = new Date();
            var startDate = new Date(2018, 6, 10);
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
                //时间和渠道获取数量列表
                amountList:[],
                //判断是哪个界面 1列表界面  2查询界面
                statu:1,
                //渠道列表
                wayList:[],
                //需要查询的渠道
                needSelectWay:[],
                //查询时间列表
                timeList:[],
                //查询展示列表
                showList:[],
            },
            created: function(){
                this.getList();
            },
            methods: {
                //获取列表
                getList: function(){
                    this.wayList = <?php echo $_SESSION['way']?>;
                    this.amountList = <?php echo $_SESSION['amount']?>;
                    console.log(this.amountList);
                    this.statu = 1;
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
                            this.needSelectWay.push(this.wayList[i].name)
                            amount++;
                        }
                    }
                    console.log(this.needSelectWay);
                    if(amount == 0){
                        alert("请先选择渠道"); return;
                    }
                    this.showList = [];
                    //获取起始时间和结束时间
                    var startTime = $("#my-startDate").text();
                    var endTime = $("#my-endDate").text();
                    this.timeList = this.getDayAll(startTime, endTime);
                    this.dealShowList();
//                    console.log( this.timeList);
                    this.statu = 2;
                },
                //分解时间
                getDayAll:function (startTime, endTime) {
                    var dateAllArr = new Array();
                    var ab = startTime.split("-");
                    var ae = endTime.split("-");
                    var db = new Date();
                    db.setUTCFullYear(ab[0], ab[1]-1, ab[2]);
                    var de = new Date();
                    de.setUTCFullYear(ae[0], ae[1]-1, ae[2]);
                    var unixDb=db.getTime();
                    var unixDe=de.getTime();
                    for(var k=unixDb;k<=unixDe;){
                        var date = new Date(k);
                        var Y = date.getFullYear() + '-';
                        var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
                        var D = date.getDate();
                        dateAllArr.push((Y+M+D).toString());
                        k=k+24*60*60*1000;
                    }
                    return dateAllArr;
                },
                //返回列表
                backList:function () {
                    this.statu = 1;
                    this.ischeckAll = false;
                    for(var i = 0; i < this.wayList.length; i++){
                        this.wayList[i].check = this.ischeckAll
                    }
                    this.needSelectWay = [];
                },
                //处理时间和渠道列表的显示数组
                dealShowList:function () {
                    for(var i = 0; i < this.timeList.length; i ++){
                        var item = [];
                        //如果查询的时间数据库中不存在的话数据就全部赋0， 否则将数据库的中数量读出
                        var statuForTime = 0;
                        item.push(this.timeList[i]);
                        for(var key in this.amountList){
                            if(key == this.timeList[i]){
                                statuForTime ++ ;
                            }
                        }
                        if(statuForTime != 0){
                            for(var j = 0; j < this.needSelectWay.length; j ++){
                                item.push(this.amountList[this.timeList[i]][this.needSelectWay[j]]);
                            }
                        }else{
                            for(var j = 0; j < this.needSelectWay.length; j ++){
                                item.push(0);
                            }
                        }
                        this.showList.push(item);
                    }
                }
            },
            watch: {
                'statu':function () {
                    $('#my-start').datepicker();
                    $('#my-end').datepicker();
                }
            }
        })
    </script>
</body>
</html>
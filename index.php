<?php
    //连接本地redis
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->select(1);
     /**************************************************************
      * timeList    设置存储时间的列表(list)
      * wayList    设置渠道列表(list)
      * llen(key)    获取list的长度
      * lindex(key, index)    根据index获取list的value
      * rpush(key, value)    添加value值到列表最右边
      * lrem(key, 0, value)    移除list中所有value
      * ************************************************************
      * 设置存储时间和数量关系的列表
      * timeAmountList    时间和数量表（hash）
      * hGetAll(key)   获取hashkeykey->value
      * hset(key, hashkey, value)    设置hashkey->value
      * hkeys(key)   获取所有hashkey
      * hlen(key)    获取hashkey的数量
      * hexists(key, hashkey)    判断hashkey是否存在
      * hdel(key, hashkey)    删除hashkey
      **************************************************************
      * 设置当天的更新时间
      * updateTime    更新时间（string）
      **************************************************************/

    /**
     * 判断是否对redis进行修改(每小时可修改一次)
     */
    $nowDate = time();
    if(!$redis->get('updateTime') || $redis->get('updateTime')+60*60 < $nowDate){
        //连接数据库
        $user = "root";
        //$pass = "";
        $pass = "123456";
        $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
        // 获取时间
        $sqlTime = "select date_format(created,'%Y-%m-%d') AS time from app_channel GROUP BY time";
        $searchTime = $dbh->query($sqlTime);
        $searchTime->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
        $rsDate = $searchTime->fetchAll();
        // 获取渠道
        $sqlWay = "select channel_name name from app_channel GROUP BY channel_name";
        $way = $dbh->query($sqlWay);
        $way->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
        $rsWay = $way->fetchAll();

        // 判断数据库的时间数量与redis中缓存的时间数量，只有当缓存的时间数少于数据库的时间数才进入
        $timeIndex = $redis->lLen('timeList');
        //    $redis->del('timeList');
        //    var_dump( $timeIndex = $redis->lLen('timeList'));exit;
        if(count($rsDate) > $timeIndex){
            $timeBR = $rsDate[$timeIndex]['time'];
            $redis->rPush('timeList', $timeBR);
            // 判断timeAmountList的长度是否为0，为0则表示数据不存在，则执行数据库操作
            for($i = 0; $i < $redis->lLen('timeList'); $i ++){
                $timeAR = $redis->lIndex('timeList', $i);
                if($redis->hlen($timeAR) == 0){
                    foreach ($rsWay as $keyWay=>$valueWay){
                        //由时间获取 渠道的数量
                        $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}' 
                        and  date_format(created,'%Y-%m-%d') = '{$timeAR}'";
                        $amount = $dbh->query($sql);
                        $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
                        $rsAmount = $amount->fetchAll();
                        $redis->hset($timeAR, $valueWay['name'], $rsAmount[0]['amount']);    //设置hashkey->value
                    }
                    break;
                }
            }
        }

        // 当数据库的时间数量与$redis中缓存时间数量相同时，证明是今天，即判断更新时间时候大于一小时, 是则进入
        if(count($rsDate) == $timeIndex){
            $selectTime = $rsDate[$timeIndex - 1]['time'];
            $redis->set('updateTime', $nowDate);
            // 先删除hashkey
            if($redis->get('updateTime')){
                $redis->hDel($selectTime);
            }
            foreach ($rsWay as $keyWay=>$valueWay){
                //由时间获取 渠道的数量
                $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}' 
                    and  date_format(created,'%Y-%m-%d') = '{$selectTime}'";
                $amount = $dbh->query($sql);
                $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
                $rsAmount = $amount->fetchAll();
                $redis->hset($selectTime, $valueWay['name'], $rsAmount[0]['amount']);    //设置hashkey->value
            }
        }

        // 更新渠道
        $redis->del('wayList');
        foreach ($rsWay as $k_way => $v_way){
            $redis->rPush('wayList', $v_way['name']);
        }
    }

    /**
     * 设置缓存的时间，若未更新到最新的话，则缓存时间应为0
     */
    if(!$redis->get('updateTime')){
        $lifeTime = 0;
    }else{
        $lifeTime = 1 * 3600;
    }
    // 缓存时间也设置为1小时
    session_set_cookie_params($lifeTime);
    session_start();
    if($_SESSION['amount'] == null || $_SESSION['way'] == null || $_SESSION['date'] == null){
    //        $user = "root";
    //        //$pass = "";
    //        $pass = "123456";
    //        $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
    //
    //        $sqlName = "select channel_name name from app_channel GROUP BY channel_name";
    //        $sqlTime = "select date_format(created,'%Y-%m-%d') AS time from app_channel GROUP BY time";
    //
    //        //获取时间,并设置session
    //        $date = $dbh->query($sqlTime);
    //        $date->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
    //        $rsDate = $date->fetchAll();
    //        $_SESSION['date'] = $rsDate;
    //        //获取渠道,并设置session,json数据用于VUE赋值，用于页面展示
    //        $way = $dbh->query($sqlName);
    //        $way->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
    //        $rsWay = $way->fetchAll();
    //        foreach ($rsWay as $key => $value){
    //            $rsWay[$key]['check'] = false;
    //        }
        // 添加渠道session
        $popWayList = [];
        for($wayNum = 0; $wayNum < $redis->lLen('wayList'); $wayNum ++){
            $popWayList[$wayNum]['name'] = $redis->lIndex('wayList', $wayNum);
            $popWayList[$wayNum]['check'] = false;
        }
        $_SESSION['way'] = $popWayList;
        $reWayJson = json_encode($popWayList);
        $_SESSION['jsonWay'] = $reWayJson;
        // 添加时间session 和 添加日期和渠道获取数量的session
        $popTimeList = [];
        $popAmountList = [];
        for($timeNum = 0; $timeNum < $redis->lLen('timeList'); $timeNum ++){
            $linshiTime = $redis->lIndex('timeList', $timeNum);
            $popTimeList[$timeNum]['time'] = $linshiTime;
            $popAmountList[$linshiTime] = $redis->hGetAll($linshiTime);

        }
        $_SESSION['date'] = $popTimeList;
        $_SESSION['amount'] = $popAmountList;
        $reAmountJson = json_encode($popAmountList);
        $_SESSION['jsonAmount'] = $reAmountJson;

        //由日期和渠道获取数量，储存为二维数组， [日期][渠道]
    //        foreach ($rsDate as $keyDate=>$valueDate){
    //            foreach ($rsWay as $keyWay=>$valueWay){
    //                $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}'
    //                    and  date_format(created,'%Y-%m-%d') = '{$valueDate['time']}'";
    //                $amount = $dbh->query($sql);
    //                $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
    //                $rsAmount = $amount->fetchAll();
    //                $reByWay[$valueWay['name']] = $rsAmount[0]['amount'];
    //            }
    //            $reByAll[$valueDate['time']] = $reByWay;
    //        }
    //        $_SESSION['amount'] = $reByAll;
    //        $reAmountJson = json_encode($reByAll);
    //        $_SESSION['jsonAmount'] = $reAmountJson;
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
    <link rel="stylesheet" href="css/amazeui.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/search.css">
</head>
<body>
    <div id="vueApp">
        <div class="timeRight">
            <span>更新时间：</span>
            <span v-text="nowDateFloat"></span>
        </div>
        <div class="content" v-if="statu == 1">
            <div class="am-g">
                <div class="am-u-sm-5">
                    <button type="button" class="am-btn am-btn-success" value="全部导出" @click="exportAll()">全部导出</button>
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
                        <td ><span v-text="value.name ==''?'ios':value.name"></span></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="selectPage" v-if="statu == 2">
            <div class="top">
                <span style="font-size: 50px; color:#87CEEB">查询结果</span>
                <div class="am-btn-group" data-am-button>
                    <button type="button" class="am-btn am-btn-secondary" @click="backList()">返回列表</button>
                    <button type="button" class="am-btn am-btn-success"value="按需导出"  @click="exportPart()">按需导出</button>
                </div>

            </div>
            <div id="boxscrol2">
                <table class="tableSetSelect" border="1" cellspacing="0">
                    <tr>
                        <td>日期</td>
                        <td v-for="way in needSelectWay" v-text="way == ''?'ios':way"></td>
                    </tr>
                    <tr v-for="time in showList">
                        <td v-for="amount in time" v-text="amount"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <!--   弹出窗 -->
<!--    <div class="am-modal am-modal-no-btn" tabindex="-1" id="export">-->
<!--        <div class="am-modal-dialog">-->
<!--            <div class="am-modal-hd">导出选择-->
<!--                <a href="javascript: void(0)" class="am-close am-close-spin" data-am-modal-close>&times;</a>-->
<!--            </div>-->
<!--            <div class="am-modal-bd">-->
<!--                <div class="buttonGroup">-->
<!--                    <button type="button" class="am-btn am-btn-success am-round" onclick="exportAll()">导出全部</button>-->
<!--                    <button type="button" class="am-btn am-btn-secondary am-round" onclick="exportByTime()">按日期导出</button>-->
<!--                    <button type="button" class="am-btn am-btn-primary am-round" onclick="exportByWay()">按渠道导出</button>-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--        </div>-->
<!--    </div>-->

    <script src="js/jquery.min.js"></script>
    <script src="js/amazeui.min.js"></script>
    <script src="js/jquery.nicescroll.min.js"></script>
    <script src="js/vue/vue.min.js"></script>
    <script src="js/vue/vue-resource.min.js"></script>
<!--    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>-->
    <script type="text/javascript">
        //滚动条
        $(document).ready(function() {
            $("#boxscrol").niceScroll(); // First scrollable DIV
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
                //目前跟新到的日期
                nowDateFloat:'',
            },
            created: function(){
                this.getList();
            },
            methods: {

                //获取列表
                getList: function(){
                    this.wayList = <?php echo $_SESSION['jsonWay']?>;
                    this.amountList = <?php echo $_SESSION['jsonAmount']?>;
                    this.statu = 1;
                    this.nowDateFloat = "<?php echo $redis->lIndex('timeList', $redis->lLen('timeList') - 1)?>";
                    console.log(this.nowDateFloat);

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
                    if(amount == 0){
                        alert("请先选择渠道"); return;
                    }
                    this.showList = [];
                    //获取起始时间和结束时间
                    var startTime = $("#my-startDate").text();
                    var endTime = $("#my-endDate").text();
                    this.timeList = this.getDayAll(startTime, endTime);
                    this.dealShowList();
                    console.log(this.amountList);
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
                        var D = date.getDate() < 10 ? '0'+date.getDate() : date.getDate();
                        dateAllArr.push((Y+M+D).toString());
                        k=k+24*60*60*1000;
                    }
                    return dateAllArr;
                },
                //返回列表
                backList:function () {
                    window.location = "index.php";
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
                },
                //导出全部
                exportAll:function () {
                    window.location = "export.php?statu=1";
                },
                //按需导出
                exportPart:function () {
//                    console.log(this.needSelectWay);
                    //设置临时cookie
                    //获取当前时间
                    var date=new Date();
                    //设置5S的过期时间
                    date.setTime(date.getTime()+500*1000);
                    document.cookie="needWay="+JSON.stringify(this.needSelectWay)+"; expires="+date.toGMTString();
                    //将二维数组的展示列表转给Json
                    var num = 0;
                    for(var i = 0; i < this.showList.length; i ++){
                        document.cookie="showList"+i+"="+JSON.stringify(this.showList[i])+"; expires="+date.toGMTString();
                        num ++;
                    }
                    document.cookie="listNum="+num+"; expires="+date.toGMTString();
                    window.location = "export.php?statu=2";
//                    this.$http.post('export.php',{
//                        statu:2,
//                        way:this.needSelectWay,
//                        showList:this.showList,
//                    })
//                    axios({
//                        method: 'post',
//                        url: 'export.php',
//                        data: {
//                            firstName: 'Fred',
//                            lastName: 'Flintstone'
//                        }
//                    });
                },

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
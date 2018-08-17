<?php


    // 连接本地memcached
    $memcache = new Memcache();
    $memcache->connect('127.0.0.1',11211) or die('shit');
    /**************************************************************
     * **********  变量表 **********
     * sdTimeList    设置存储时间的列表
     * sdWayListJson    渠道列表的json
     * sdTimeAmountListJson    时间和数量表Json
     * sdUpdateTime    设置当天的更新时间（格式为时间搓）
     * sdnowDate    用于页面显示时间（刷新所至显示数据的时间）
     * sdnowTime    最新刷新的时间，用于判断是否需要更新前一天的数据，如不是当天的时间，则需要更新前一天的数据（格式为Y-m-d）
     * sdTableHead    用于导出设置表列
     * ************************************************************
     * **********  操作表  *********
     * set(key, value)    更新该key所对应的原来的数据
     * get(key)     获取该key对应的value, 如key不存在则返回false
     * delete(key)    删除key, 存在则返回true, 否则返回false
     **************************************************************/
    $nowDate = time();
    if(!$memcache->get('sdUpdateTime') || $memcache->get('sdUpdateTime')+60*60 < $nowDate){
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
        foreach ($rsWay as $k_reWay=>$v_reWay){
            $rsWay[$k_reWay]['check'] = false;
        }

        //获取memcached 中的时间列表, 判断数据库的时间数量与缓存的时间数量，只有当缓存的时间数少于数据库的时间数才进入
        $getMemcachedTimeList = $memcache->get('sdTimeList');
        if($getMemcachedTimeList){
            $memcacheTimeListNum = count($getMemcachedTimeList);
        }else{
            $memcacheTimeListNum = 0;
            $getMemcachedTimeList = [];
        }
        //获取memcached 中的渠道列表
        $getMemcachedWayList = $memcache->get('sdWayListJson');
        if(!$getMemcachedWayList){
            $getMemcachedWayList = [];
        }else{
            $getMemcachedWayList = json_decode($getMemcachedWayList, true);
        }
        //获取memcached 时间和数量表
        $getMemcachedTimeAmountList = $memcache->get('sdTimeAmountListJson');
        if(!$getMemcachedTimeAmountList){
            $getMemcachedTimeAmountList = [];
        }else{
            $getMemcachedTimeAmountList = json_decode($getMemcachedTimeAmountList, true);
        }
        //获取memcached 的当天更新时间
        $getMemcachedUpdateTime = $memcache->get('sdUpdateTime');
        if(!$getMemcachedUpdateTime){
            $getMemcachedUpdateTime = '';
        }
        //获取最新刷新时间
        $newF5Time = $memcache->get('sdnowTime');
        if(!$newF5Time){
            $newF5Time = date("Y-m-d");
        }
        if($newF5Time != date("Y-m-d") ){
            $refreshTime = $getMemcachedTimeList[count($getMemcachedTimeList) - 1];
            foreach ($rsWay as $keyWay=>$valueWay){
                //由时间获取 渠道的数量
                $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}'
                    and  date_format(created,'%Y-%m-%d') = '{$refreshTime}'";
                $amount = $dbh->query($sql);
                $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
                $rsAmount = $amount->fetchAll();
                $getMemcachedTimeAmountList[$refreshTime][$valueWay['name']] = $rsAmount[0]['amount'];
            }
        }

        //当数据库的最新日期不是今天时进入
        if(count($rsDate) > $memcacheTimeListNum){
            $memcache->delete('sdUpdateTime');
            $timeBR = $rsDate[$memcacheTimeListNum];
            $memcache->set('sdnowDate', $timeBR['time']);
            array_push($getMemcachedTimeList, $timeBR);
            for(; $memcacheTimeListNum < count($getMemcachedTimeList); $memcacheTimeListNum ++){
                $timeAR = $getMemcachedTimeList[$memcacheTimeListNum]['time'];
                foreach ($rsWay as $keyWay=>$valueWay){
                    //由时间获取 渠道的数量
                    $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}'
                    and  date_format(created,'%Y-%m-%d') = '{$timeAR}'";
                    $amount = $dbh->query($sql);
                    $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
                    $rsAmount = $amount->fetchAll();
                    $getMemcachedTimeAmountList[$timeAR][$valueWay['name']] = $rsAmount[0]['amount'];
                }
            }
        }
        // 当数据库的时间数量与$redis中缓存时间数量相同时，证明是今天，即判断更新时间时候大于一小时, 是则进入
        if(count($rsDate) == $memcacheTimeListNum){
            $selectTime = $rsDate[$memcacheTimeListNum - 1]['time'];
            $memcache->set('sdnowDate', $selectTime);
            $memcache->set('sdUpdateTime', $nowDate);
            foreach ($rsWay as $keyWay=>$valueWay){
                //由时间获取 渠道的数量
                $sql = "select count(*) amount from app_channel where channel_name = '{$valueWay['name']}'
                    and  date_format(created,'%Y-%m-%d') = '{$selectTime}'";
                $amount = $dbh->query($sql);
                $amount->setFetchMode(PDO::FETCH_ASSOC);    //设置结果集返回格式,此处为关联数组,即不包含index下标
                $rsAmount = $amount->fetchAll();
                $getMemcachedTimeAmountList[$selectTime][$valueWay['name']] = $rsAmount[0]['amount'];
            }
        }
        //更新memcached
        $memcache->set('sdTimeList', $getMemcachedTimeList);
//        $memcache->set('sdWayList', $rsWay);
        $memcache->set('sdWayListJson', json_encode($rsWay));
//        $memcache->set('sdTimeAmountList', $getMemcachedTimeAmountList);
        $memcache->set('sdTimeAmountListJson', json_encode($getMemcachedTimeAmountList));
        $memcache->set('sdnowTime', date("Y-m-d"));

//        /*****************************************
//         * session
//         *****************************************/
//        $lifeTime = 1 * 3600;    //设置过期时间为1小时
//        session_set_cookie_params($lifeTime);
//        session_start();
//        // 添加渠道session
//        $popWayList = $memcache->get('sdWayList');
//        for($wayNum = 0; $wayNum < count($popWayList); $wayNum ++){
//            $popWayList[$wayNum]['check'] = false;
//        }
//        $_SESSION['way'] = $popWayList;
//        $reWayJson = json_encode($popWayList);
//        $_SESSION['jsonWay'] = $reWayJson;
//        // 添加时间session 和 添加日期和渠道获取数量的session
//        $popTimeList = $memcache->get('sdTimeList');
//        $popAmountList = $memcache->get('sdTimeAmountList');
//        $_SESSION['date'] = $popTimeList;
//        $_SESSION['amount'] = $popAmountList;
//        $reAmountJson = json_encode($popAmountList);
//        $_SESSION['jsonAmount'] = $reAmountJson;
        //表格纵列
        $tableHead = [];
        $num = 0;
        for($i = 0; $i < count($rsWay); $i ++){
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
        $memcache->set('sdTableHead', $tableHead);
//        $_SESSION['tableHead'] = $tableHead;

    }
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
    <script src="js/jquery.min.js"></script>
    <script src="js/amazeui.min.js"></script>
    <script src="js/jquery.nicescroll.min.js"></script>
    <script src="js/vue/vue.min.js"></script>
    <script src="js/vue/vue-resource.min.js"></script>
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

        //######//
        // vue //
        //#####//
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
                    this.wayList = <?php echo $memcache->get('sdWayListJson')?>;
                    console.log(this.wayList)
                    this.amountList = <?php echo $memcache->get('sdTimeAmountListJson')?>;
                    this.statu = 1;
                    this.nowDateFloat = "<?php echo $memcache->get('sdnowDate')?>";
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
                    //设置临时cookie
                    //获取当前时间
                    var date=new Date();
                    //设置2S的过期时间
                    date.setTime(date.getTime()+2*1000);
                    //需要导出的渠道
                    document.cookie="needWay="+JSON.stringify(this.needSelectWay)+"; expires="+date.toGMTString();
                    //需要导出的时间
                    document.cookie="needTime="+JSON.stringify(this.timeList)+"; expires="+date.toGMTString();
                    window.location = "export.php?statu=2";
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
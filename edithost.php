
<script>
	var interval = -1;
	var time = 0;
	var str = "Updating";
	var Infostr = str+", please wait";
	var InfoItem = "#info";
	function updateInfo(){
		time++;
		if(time >= 80){
			time=0;
			Infostr = str+", please wait";
		}else{
			Infostr = Infostr+".";
		}
		$(InfoItem).html(Infostr);
	}
	
	function updateFPGA(itemname) {
		if(interval != -1){
			alert("Important processing is under way, please try later!");
			return;
		}
		var mydata = "host="+$("#host").val();
		if(!confirm("Are you sure to update FPGA?\r\n"))
		{ 
			return;
		}else{
			str = "Updating FPGA";
			Infostr = str+", please wait";
			InfoItem = itemname;
            alert('Do not refresh your browser when updating, it may take a few minutes!');
			interval = window.setInterval(updateInfo, 200);
        }
		$.ajax({
	        url: "updateFPGA.php",
	        type: "post",
	        data: mydata,
	        //dataType: "json",
	        error: function(){
				clearInterval(interval);
				interval = -1;
	            alert('Error loading restart XML document');  
	        },  
	        success: function(data){   
				clearInterval(interval);
				interval = -1;
				$(itemname).html("");
	         	alert(data);
				window.location.href='index.php';
	        }
	     });
	}

	function rollbackFPGA(itemname) {
		if(interval != -1){
			alert("Important processing is under way, please try later!");
			return;
		}
		var mydata = "host="+$("#host").val();
		if(!confirm("Are you sure to rollback FPGA?\r\n"))
		{ 
			return;
		}
		str = "Rollbacking FPGA";
		Infostr = str+", please wait";
		InfoItem = itemname;
		interval = window.setInterval(updateInfo, 200);
		$.ajax({
	        url: "rollbackFPGA.php",  
	        type: "post",
	        data:mydata,
	        //dataType: "json",
	        error: function(){
				clearInterval(interval);
				interval = -1;
	            alert('Error loading restart XML document');  
	        },  
	        success:function(data){
				clearInterval(interval);
				interval = -1;
				$(itemname).html("");
				//$.ajax({url:"restart.php",type:"post",data:mydata,error:function(){},success:function(data){}});
				alert(data);
				window.location.href='index.php';
	        }
	    });
	}

	function restoreFPGA(itemname) {
		if(interval != -1){
			alert("Important processing is under way, please try later!");
			return;
		}
		var mydata = "host="+$("#host").val();
		if(!confirm("Are you sure to restore the FPGA to factory version?\r\n"))
		{ 
			return;
		}
		str = "Restoring FPGA";
		Infostr = str+", please wait";
		InfoItem = itemname;
		interval = window.setInterval(updateInfo, 200);
		$.ajax({
	        url: "restoreFPGA.php",  
	        type: "post",
	        data: mydata,
	        //dataType: "json",
	        error: function(){
				clearInterval(interval);
				interval = -1;
	            alert('Error loading restart XML document');
	        },  
	        success: function(data){
				clearInterval(interval);
				interval = -1;
				$(itemname).html("");
				alert(data);
				window.location.href='index.php';
	        }
	    });
	}
	
	function updateMiner(itemname) {
		if(interval != -1){
			alert("Important processing is under way, please try later!");
			return;
		}
		var mydata = "host="+$("#host").val();
		if(!confirm("Are you sure to update cgminer version?\r\n"))
		{ 
			return;
		}else{
            alert('Do not refresh your browser when updating, it may take a few minutes!');
			str = "Updating Miner";
			Infostr = str+", please wait";
			InfoItem = itemname;
			interval = window.setInterval(updateInfo, 200);
        }
		$.ajax({
	        url: "update.php",  
	        type: "post",
	        data: mydata,
	        //dataType: "json",
	        error: function(){
				clearInterval(interval);
				interval = -1;
	            alert('Error loading restart php document');  
	        },  
	        success: function(data){
				clearInterval(interval);
				interval = -1;
				$(itemname).html("");
	        	alert(data);
	        }
	    });
	}

	function rollbackMiner(itemname) {
		if(interval != -1){
			alert("Important processing is under way, please try later!");
			return;
		}
		var mydata = "host="+$("#host").val();
		if(!confirm("Are you sure to rollback cgminer version?\r\n"))
		{ 
			return;
		}
		str = "Rollbacking Miner";
		Infostr = str+", please wait";
		InfoItem = itemname;
		interval = window.setInterval(updateInfo, 200);
		$.ajax({
	        url: "rollback.php",
	        type: "post",
	        data:mydata,
	        //dataType: "json",
	        error: function(){
				clearInterval(interval);
				interval = -1;
	            alert('Error loading rollback php document');  
	        },  
	        success: function(data){
				clearInterval(interval);
				interval = -1;
				$(itemname).html("");
				alert(data);
	        }
	    });
	}

	function restoreMiner(itemname) {
		if(interval != -1){
			alert("Important processing is under way, please try later!");
			return;
		}
		var mydata = "host="+$("#host").val();
		if(!confirm("Are you sure to restore factory settings and cgminer version?\r\n"))
		{ 
			return;
		}
		str = "Restoring Miner";
		Infostr = str+", please wait";
		InfoItem = itemname;
		interval = window.setInterval(updateInfo, 200);
		$.ajax({
	        url: "restore.php",  
	        type: "post",
	        data:mydata,
	        //dataType: "json",
	        error: function(){
				clearInterval(interval);
				interval = -1;
	            alert('Error loading restore php document');
	        },  
	        success: function(data){
				clearInterval(interval);
				interval = -1;
				$(itemname).html("");
	        	alert(data);
	        }
	    });
	}
    
    function confirmAsk(str){
        if(confirm(str)){ 
			return true;
		}else{
            return false;
        }
    }
    
    function checkPll(){
        value = parseInt($("#globalpll").val())
        if(value < 30 || value > 800){
            alert("pll must be 30~800");
            return false;
        }
        
        if(confirm("After setting restart to take effect, are you sure to set pll?")){ 
			return true;
		}else{
            return false;
        }
    }

    function checkVoltage(){
        value = parseInt($("#globalvoltage").val())
        if(confirm("Do you really want to set voltage to "+value+"?")){ 
			return true;
		}else{
            return false;
        }
    }
	
	function changeAlgorithm(){
		if(confirm("Change algorithm will restart miner, do you really want to "+$("#chgAlgorithm").val()+"?")){
			if($("#chgAlgorithm").val().indexOf('Sia') != -1) 
				$method="1";
			else
				$method="0";
			$("#algorithm").val($method);
			return true;
		}else{
			return false;
		}
	}
	
	function changeAlgorithm2(){
		if(confirm("Change algorithm will restart miner, do you really want to "+$("#chgAlgorithm2").val()+"?")){
			if($("#chgAlgorithm2").val().indexOf('Hsr') != -1) 
				$method="2";
			else
				$method="0";
			$("#algorithm").val($method);
			//alert($method);
			//return false;
			return true;
		}else{
			return false;
		}
	}
</script>

<?php
require("auth.inc.php");
require("config.inc.php");
require("func.inc.php");
global $voltageSet;

$dbh = anubis_db_connect();
$config = get_config_data();

if (isset($_POST['delete']) && isset($_POST['savehostid']))
{
	$id = 0 + $_POST['savehostid'];
	$id_quote = $dbh->quote($id);
	$delq = "DELETE FROM hosts WHERE id = $id_quote";
	$delr = $dbh->exec($delq);
    db_error();
}

if (isset($_POST['savehostid']) && !isset($_POST['delete'])) 
{
  $id = 0 + $_POST['savehostid'];
  $id_quote = $dbh->quote($id);
  $newname = $dbh->quote($_POST['macname']);
  $address = $dbh->quote($_POST['ipaddress']);
  $port = $dbh->quote($_POST['port']);
  $mhash = $dbh->quote($_POST['mhash']);

  if ($newname && $newname !== "" && $address && $address !== "")
  {
    $updq = "UPDATE hosts SET name = $newname, address = $address, port = $port, mhash_desired = $mhash WHERE id = $id_quote";
    $dbh->exec($updq);
    db_error();
  }
}

if (!isset($id))
  $id = 0 + $_GET['id'];
if (!$id || $id == 0) 
{
	echo "Need a Host to deal with !";
	die;
}


if($host_data = get_host_data($id))
{
  if($host_alive = get_host_status($host_data))
  {
    /* Determine if we can change values on this host */
    $privileged = get_privileged_status($host_data);
    $locked = get_lockconfig_status($host_data);
  
    if ($privileged&&!$locked)
    {
      if (isset($_POST['startpga']))
      {
        $pga_id = filter_input(INPUT_POST, 'startpga', FILTER_SANITIZE_NUMBER_INT);
        $arr = array ('command'=>'pgaenable','parameter'=>$pga_id);
        $dev_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }

      if (isset($_POST['stoppga']))
      {
        $pga_id = filter_input(INPUT_POST, 'stoppga', FILTER_SANITIZE_NUMBER_INT);
        $arr = array ('command'=>'pgadisable','parameter'=>$pga_id);
        $dev_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }
      
      if (isset($_POST['flashpga']))
      {
      	$pga_id = filter_input(INPUT_POST, 'flashpga', FILTER_SANITIZE_NUMBER_INT);
      	$arr = array ('command'=>'pgaidentify','parameter'=>$pga_id);
      	$dev_response = send_request_to_host($arr, $host_data);
      	sleep(2);
      }

      if (isset($_POST['viewpool']))
      {
        $sel_pool = filter_input(INPUT_POST, 'viewpool', FILTER_SANITIZE_NUMBER_INT);
        $update_pool = $sel_pool;
      }

      if (isset($_POST['toppool']))
      {
        $pool_id = filter_input(INPUT_POST, 'toppool', FILTER_SANITIZE_NUMBER_INT);
        $arr = array ('command'=>'switchpool','parameter'=>$pool_id);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }

      if (isset($_POST['stoppool']))
      {
        $pool_id = filter_input(INPUT_POST, 'stoppool', FILTER_SANITIZE_NUMBER_INT);
        $arr = array ('command'=>'disablepool','parameter'=>$pool_id);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }
  
      if (isset($_POST['startpool']))
      {
        $pool_id = filter_input(INPUT_POST, 'startpool', FILTER_SANITIZE_NUMBER_INT);
        $arr = array ('command'=>'enablepool','parameter'=>$pool_id);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }

      if (isset($_POST['rempool']))
      {
        $pool_id = filter_input(INPUT_POST, 'rempool', FILTER_SANITIZE_NUMBER_INT);
        $arr = array ('command'=>'removepool','parameter'=>$pool_id);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }

      if (isset($_POST['addpool']))
      {
        $pool_url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
        $pool_user = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
        $pool_pass = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_STRING);

        $arr = array ('command'=>'addpool','parameter'=>$pool_url.','.$pool_user.','.$pool_pass);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }

      if (isset($_POST['updatepool']))
      {
        $pool_id = filter_input(INPUT_POST, 'selpool', FILTER_SANITIZE_NUMBER_INT);
        $pool_url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_URL);
        $pool_user = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
        $pool_pass = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_STRING);

        $arr = array ('command'=>'updatepool','parameter'=>$pool_id.','.$pool_url.','.$pool_user.','.$pool_pass);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
      }

      if (isset($_POST['setemailserver']))
      {
        $email_server = filter_input(INPUT_POST, 'emailserver', FILTER_SANITIZE_URL);
        $email_user = filter_input(INPUT_POST, 'emailuser', FILTER_SANITIZE_STRING);
        $email_pass = filter_input(INPUT_POST, 'emailpass', FILTER_SANITIZE_STRING);

        $arr = array ('command'=>'setemailserver','parameter'=>$email_server.','.$email_user.','.$email_pass);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);
      }
      
       if (isset($_POST['noticeset']))
      {
        $warnemail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
        $warnhash = filter_input(INPUT_POST, 'hash', FILTER_SANITIZE_STRING);
        $warnreject = filter_input(INPUT_POST, 'reject', FILTER_SANITIZE_STRING);
        $warntime = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);
        $warnstarttime = filter_input(INPUT_POST, 'starttime', FILTER_SANITIZE_STRING);
        $timezone = filter_input(INPUT_POST, 'timezone', FILTER_SANITIZE_STRING);
        $temptime = filter_input(INPUT_POST, 'temptime', FILTER_SANITIZE_STRING);
        $warntemp = filter_input(INPUT_POST, 'warntemp', FILTER_SANITIZE_STRING);
        $safetemp = filter_input(INPUT_POST, 'safetemp', FILTER_SANITIZE_STRING);
        if (isset($_POST['tempfaultchk']))
      	  	$tempfault = 1;
        else
       	$tempfault = 0;
        if (isset($_POST['tempfaultchk']))
      	  	$staticip = 1;
        else
       	$staticip = 0;
        
        /*$arr = array ('command'=>'pgawarntime','parameter'=>$warntime);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);
         
        $arr = array ('command'=>'pgawarnstarttime','parameter'=>$warnstarttime);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);
      
        $arr = array ('command'=>'pgawarnreject','parameter'=>$warnreject);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);

        $arr = array ('command'=>'pgawarnhash','parameter'=>$warnhash);
        $pool_response = send_request_to_host($arr, $host_data);
         sleep(1);*/

        $arr = array ('command'=>'pgatimezone','parameter'=>$timezone);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);
       
        $arr = array ('command'=>'pgasettemptime','parameter'=>$temptime);
        $pool_response = send_request_to_host($arr, $host_data);
        //sleep(1);
       
        $arr = array ('command'=>'pgasetwarntemp','parameter'=>$warntemp);
        $pool_response = send_request_to_host($arr, $host_data);
        //sleep(1);
        
        $arr = array ('command'=>'pgasetsafetemp','parameter'=>$safetemp);
        $pool_response = send_request_to_host($arr, $host_data);
        //sleep(1);

        /*$arr = array ('command'=>'pgasettempfault','parameter'=>$tempfault);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);*/
        
        $arr = array ('command'=>'pgasetemail','parameter'=>$warnemail);
        $pool_response = send_request_to_host($arr, $host_data);
        //sleep(1);
     }
       if (isset($_POST['setstaticip']))
      {
        $stripport = filter_input(INPUT_POST, 'ipport', FILTER_SANITIZE_STRING);
        $strnetmask = filter_input(INPUT_POST, 'netmask', FILTER_SANITIZE_STRING);
        $strgateway = filter_input(INPUT_POST, 'gateway', FILTER_SANITIZE_STRING);
        $strapiport = filter_input(INPUT_POST, 'apiport', FILTER_SANITIZE_STRING);
        
        /*$arr = array ('command'=>'pgasetapiport','parameter'=>$strapiport);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);*/
        
        if (isset($_POST['staticipchk']))
      	  	$staticipen = "1";
        else
            $staticipen = "0";
	  /*$arr = array ('command'=>'pgasetstaticip','parameter'=>$staticipen);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);*/
	
        $arr = array ('command'=>'pgasetstaticipparam','parameter'=>$staticipen.','.$stripport.','.$strnetmask.','.$strgateway);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(1);
	}
	
       if (isset($_POST['pwmsub']))
      {
        $strpwm = filter_input(INPUT_POST, 'fanspwm', FILTER_SANITIZE_STRING);
        $strpwm = $strpwm - 1;
        $arr = array ('command'=>'pgasetfanspwm','parameter'=>$strpwm.','.$strnetmask.','.$strgateway);
        $pool_response = send_request_to_host($arr, $host_data);
	}
       if (isset($_POST['pwmadd']))
      {
        $strpwm = filter_input(INPUT_POST, 'fanspwm', FILTER_SANITIZE_STRING);
        $strpwm = $strpwm + 1;
        $arr = array ('command'=>'pgasetfanspwm','parameter'=>$strpwm.','.$strnetmask.','.$strgateway);
        $pool_response = send_request_to_host($arr, $host_data);
	}
    if (isset($_POST['pwmdone']))
    {
        $strpwm = filter_input(INPUT_POST, 'fanspwm', FILTER_SANITIZE_STRING);
        $arr = array ('command'=>'pgasetfanspwm','parameter'=>$strpwm.','.$strnetmask.','.$strgateway);
        $pool_response = send_request_to_host($arr, $host_data);
	}
	if (isset($_POST['voltageset']))
	{
		$strvoltage = filter_input(INPUT_POST, 'globalvoltage', FILTER_SANITIZE_STRING);
		//$voltageSet=$strvoltage;
        $arr = array ('command'=>'pgasetvoltage','parameter'=>$strvoltage);
		$pool_response = send_request_to_host($arr, $host_data);
	}
    
    if( isset($_POST['setpll']))
    {   
        $arr = array ('command'=>'pgaGlobalPllset','parameter'=>$_POST['globalpll']);
        $gpu_response[0] = send_request_to_host($arr, $host_data);
    }
	
    if (isset($_POST['saveconf']))
    {
	  $pi_apiport = get_api_port($host_data);
        $conf_path = "config";//filter_input(INPUT_POST, 'confpath', FILTER_SANITIZE_STRING);
        
        // add configuration file path to db table. It'll just fail if it's already there.
        $alter = "ALTER TABLE `hosts` ADD `conf_file_path` varchar(255) NULL";
        $dbh->exec($alter);

        $updq = "UPDATE hosts SET conf_file_path = '$conf_path' WHERE id = $id";
        $dbh->exec($updq);
        db_error();

	 $updq = "UPDATE hosts SET port = $pi_apiport WHERE id = $id";
	 $dbh->exec($updq);
	 db_error();

        $arr = array ('command'=>'save','parameter'=>$conf_path);
        $pool_response = send_request_to_host($arr, $host_data);
        sleep(2);
        
        // as host data is updated, re-load it.
        $host_data = get_host_data($id);
      }
    }
	
	if(isset($_POST['chgAlgorithm']))
	{
		$strmethod = filter_input(INPUT_POST, 'algorithm', FILTER_SANITIZE_STRING);
		$arr = array ('command'=>'chgAlgorithm','parameter'=>$strmethod);
		$gpu_response[0] = send_request_to_host($arr, $host_data);
		sleep(1);
		echo "<script>window.location.href = 'index.php';</script>";
	}
	if(isset($_POST['chgAlgorithm2']))
	{
		$strmethod = filter_input(INPUT_POST, 'algorithm', FILTER_SANITIZE_STRING);
		$arr = array ('command'=>'chgAlgorithm','parameter'=>$strmethod);
		$gpu_response[0] = send_request_to_host($arr, $host_data);
		sleep(1);
		echo "<script>window.location.href = 'index.php';</script>";
	}
	
    if ($privileged)
    {
      /*if (isset($_POST['restartbut']) && isset($_POST['restartchk']))
      {
        $arr = array ('command'=>'restart','parameter'=>'');
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(2);
      }
      
      if (isset($_POST['quitbut']) && isset($_POST['quitchk']))
      {
        $arr = array ('command'=>'reboot','parameter'=>'');     // do not keep "quit", just reboot system by ZWW
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(2);
      }*/
	  if (isset($_POST['restartbut']) && isset($_POST['restartchk']))
      {
        $arr = array ('command'=>'updateFPGA','parameter'=>'');
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(10);
      }
      if (isset($_POST['quitbut']) && isset($_POST['quitchk']))
      {
        $arr = array ('command'=>'rollbackFPGA','parameter'=>'');     // do not keep "quit", just reboot system by ZWW
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(5);
      }
	  if (isset($_POST['restoreFPGAbut']) && isset($_POST['restoreFPGAchk']))
      {
        $arr = array ('command'=>'restoreFPGA','parameter'=>'');
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(5);
      }
      
      if (isset($_POST['updatebut']) && isset($_POST['updatechk']))
      {
        $arr = array ('command'=>'update','parameter'=>'');
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(10);
      }
      
      if (isset($_POST['rollbackbut']) && isset($_POST['rollbackchk']))
      {
        $arr = array ('command'=>'rollback','parameter'=>'');
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(3);
      }
      
      if (isset($_POST['restorebut']) && isset($_POST['restorechk']))
      {
        $arr = array ('command'=>'restore','parameter'=>'');
        send_request_to_host($arr, $host_data);
        $host_alive = FALSE;
        sleep(3);
      }
    }
  }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>iBelinkMiner - a cgminer web frontend</title>

<?php require('stylesheets.inc.php'); ?>

<script type="text/javascript" src="scripts/jquery.min.js"></script>
<script type="text/javascript" src="scripts/ddsmoothmenu.js">


/***********************************************
* Smooth Navigational Menu- (c) Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/

</script>


<script type="text/javascript">

ddsmoothmenu.init({
	mainmenuid: "templatemo_menu", //menu DIV id
	orientation: 'h', //Horizontal or vertical menu: Set to "h" or "v"
	classname: 'ddsmoothmenu', //class added to menu's outer DIV
	//customtheme: ["#1c5a80", "#18374a"],
	contentsource: "markup" //"markup" or ["container_id", "path_to_menu_file"]
})

</script>

</head>
<body>

<div id="templatemo_wrapper">

<?php include ('header.inc.php'); ?>

    <div id="templatemo_main">
    	<div class="col_fw">
        	<div class="templatemo_megacontent">
            	<h2>Host detail</h2>
<?php
##				 if ($host_alive)
##                   echo "<a href='hoststat.php?id=".$id."'>View host stats</a>";
?>
                <div class="cleaner h20"></div>
<?php
if ($host_data)
{  
  echo "<table id=\"hostsum\" class='acuity' summary='HostSummary' align='center'>";
  echo create_host_header();
  echo get_host_summary($host_data);
  echo "</table>";

  if ($host_alive)
  {
    echo "<form name=pool action='edithost.php?id=".$id."' method='post'>";
    echo "<table id=\"devsum\" class='acuity' summary='DevsSummary' align='center'>";
    echo create_devs_header();
    echo process_devs_disp($host_data, FALSE);

    if (isset($dev_response))
    {
      if ($dev_response['STATUS'][0]['STATUS'] == 'S')
        $dev_message = "Action successful: ";
      else if ($dev_response['STATUS'][0]['STATUS'] == 'I')
         $dev_message = "Action info: ";
      else if ($dev_response['STATUS'][0]['STATUS'] == 'W')
         $dev_message = "Action warning: ";
      else
         $dev_message = "Action error: ";

      echo "<thead><tr>
              <th colspan='16'  scope='col' class='rounded-company'>"
                . $dev_message . $dev_response['STATUS'][0]['Msg'].
             "</th>
            </tr></thead>";
    }
    echo "</table>";

    echo "<table class='acuity' id=\"poolsum\" summary='PoolSummary' align='center'>";
    if(!$locked){
	    echo create_pool_header();
	    echo process_pools_disp($host_data, $privileged);
        echo get_warning_info($host_data);
    }
    if ((version_compare($API_version, 1.11, '>=')) && $privileged&&!$locked)
    {
?>
    <thead>
     	<tr>
            <th  colspan="3">Pool URL</th>
            <th colspan="3">Pool User</th>
            <th colspan="3">Pool Password</th>
            <th colspan="3">Action</th>
        </tr>
      	<tr>
            <th colspan="3">
            <input type="text" name="url"  value="<?php echo $sel_url?>"  size="30"></th>
            <th colspan="3"><input type="text" name="user" value="<?php echo $sel_user?>"  size="30"></th>
            <th colspan="3"><input type="text" name="pass" value="<?php echo $sel_passwd?>"  size="15"></th>
            <th colspan="3">
              <!--<input type="submit" class="button"  value="Edit Pool" name="updatepool" >-->
              <input type="submit" class="button"  value="Add Pool" name="addpool" style="width:140px">
            </th>
        </tr>
        
     	<tr>
            <th colspan="3">Email Server</th>
            <th colspan="3">Email User</th>
            <th colspan="3">Email Password</th>
            <th colspan="3">Action</th>
        </tr>
      	<tr>
            <th colspan="3"><input type="text" name="emailserver" value="<?php echo $emailserver?>" size="30"></th>
            <th colspan="3"><input type="text" name="emailuser" value="<?php echo $emailuser?>" size="30"></th>
            <th colspan="3"><input type="password" name="emailpass" value="<?php echo $emailpwd?>" size="15"></th>
            <th colspan="3"><input type="submit" class="button" value="Set Email Server" name="setemailserver" style="width:140px"></th>
        </tr>

      	<tr>
            <th colspan="3">Email Target</th>
            <th colspan="3">Low Hashrate Threshold(MH)</th>
            <th colspan="3">High Reject Threshold(%)</th>
            <th colspan="3"><!--Action--></th>
        </tr>
        <tr>
            <th colspan="3"><input type="text" name="email" value="<?php echo $warnemail?>" size="30"></th>
            <th colspan="3"><input type="text" name="hash" disabled="disabled" value="<?php echo $warnhash?>" size="15"></th>
            <th colspan="3"><input type="text" name="reject" disabled="disabled" value="<?php echo $warnreject?>" size="15"></th>
            <th colspan="3"><!--<input type="submit" class="button"  value="Set Warning Param" name="noticeset" style="width:140px">--></th>
        </tr>
        
        <tr>
            <th  colspan="3">Low Hashrate & High Reject Warning Duration(m)</th>
            <th  colspan="3">Start Time(m)</th>
            <th  colspan="3">Time Zone</th>
            <th  colspan="3"></th>
        </tr>
        <tr>
            <th  colspan="3"><input type="text" name="time" disabled="disabled" value="<?php echo $warntime?>" size="15"></th>
            <th  colspan="3"><input type="text" name="starttime" disabled="disabled" value="<?php echo $warnstarttime?>" size="15"></th>
            <th  colspan="3"><input type="text" name="timezone" value="<?php echo $timezone?>" size="15"></th>
            <th  colspan="3"></th>
        </tr>
         
        <tr>
        <th  colspan="3">Temperature Check Interval(s)</th>
        <th  colspan="3">Warning Temperature(&deg;C)</th>
        <th  colspan="3">Safe Temperature(&deg;C)</th>
        <th  colspan="3"><!--Sensor Fault Warning-->Action</th>
        </tr>
        <tr>
        <th  colspan="3"><input type="text" name="temptime" disabled="disabled" value="<?php echo $temptime?>" size="15"></th>
        <th  colspan="3"><input type="text" name="warntemp" value="<?php echo $warntemp?>" size="15"></th>
        <th  colspan="3"><input type="text" name="safetemp" value="<?php echo $safetemp?>" size="15"></th>
        <th  colspan="3">
        <!--<input type="checkbox" value="tempfault" name="tempfaultchk" <?php echo $strsensorfault?>> Enable-->
        <input type="submit" class="button" value="Set Warning Param" name="noticeset" style="width:140px">
        </th>
        </tr>

        <tr>
        <th  colspan="3">Static IP </th>
        <th  colspan="3">Netmask</th>
        <th  colspan="3">Gateway</th>
        <th  colspan="3"><!--Action--></th>
        </tr>
        <tr>
        <th  colspan="3"><input type="text" name="ipport" value="<?php echo $ip?>" size="15"></th>
        <th  colspan="3"><input type="text" name="netmask" value="<?php echo $netmask?>" size="15"></th>
        <th  colspan="3"><input type="text" name="gateway" value="<?php echo $gateway?>" size="15"></th>
        <th  colspan="3"><!--<input type="submit" class="button"  value="Set Static IP" name="setstaticip" style="width:140px">--></th>
        </tr>

        <tr>
        <th  colspan="3">Static IP Enable </th>
        <th  colspan="3"><!--API Port--></th>
        <th  colspan="3"></th>
        <th  colspan="3">Action</th>
        </tr>
        <tr>
        <th  colspan="3"><input type="checkbox" value="staticip" name="staticipchk" <?php echo $strstaticip?>>Enable</th>
        <th  colspan="3"><!--<input type="text" name="apiport" value="<?php echo $port?>" size="15">--></th>
        <th  colspan="3"></th>
        <th  colspan="3">
        <!--<input type="submit" class="button"  value="Set Static IP" name="setstaticip" style="width:140px">-->
        <input type="submit" class="button"  value="Set IP Config" name="setstaticip" style="width:140px">
        </th>
        </tr>
        
        <tr>
        <th colspan="3">Pll(30~800)</th>
        <th colspan="3"></th>
        <th colspan="3"></th>
        <th colspan="3">Action</th>
        </tr>
        <tr>
        <th colspan="3"><input type="text" name="globalpll" id="globalpll" value="<?php echo $pll?>" size="15"></th>
        <th colspan="3"></th>
        <th colspan="3"></th>
        <th colspan="3"><input type="submit" class="button" value="Set PLL" name="setpll" onclick = 'return checkPll()' style="width:140px"></th>
        </tr>

        <tr>
        <th  colspan="3"><div>Voltage</div></th>
        <th  colspan="3"></th>
        <th  colspan="3"></th>
        <th  colspan="3">Save All Configuration</th>
        </tr>

        <tr>
        <th  colspan="3">
        <!---<input type="submit" class="button" value="-" name="pwmsub"> 
        <input type="text" name="fanspwm" value="<?php echo $fanspwm?>" size="8">
        <input type="submit" class="button" value="+" name="pwmadd">
        <input type="submit" class="button" value="Done" name="pwmdone">-->
		<input type="text" name="voltage" id="voltage" disabled="disabled" value="<?php echo $voltageReal?>" size="15">
		<input type="text" name="globalvoltage" id="globalvoltage" value="<?php echo $voltageSet?>" size="5" style="<?php echo $voltageDis?>">
		<input type="submit" class="button" value="SetVoltage" name="voltageset" id="voltageset" onclick="checkVoltage();" style="<?php echo $voltageDis?>">
        </th>
        <th  colspan="3"></th>
        <th  colspan="3"></th>
        <th  colspan="3"><input type="submit" class="button" value="Save Configuration" name="saveconf" style="width:140px"></th>
        </tr>
	<tr>
		<th  colspan="3"></th>
		<th  colspan="3"><input type="submit" class="button" value="<?php echo $chgAlg?>" name="chgAlgorithm" id="chgAlgorithm" onclick = 'return changeAlgorithm()' style="width:140px; <?php echo $chgAlgDisplay?>"></th>
        <th  colspan="3"><input type="submit" class="button" value="<?php echo $chgAlg2?>" name="chgAlgorithm2" id="chgAlgorithm2" onclick = 'return changeAlgorithm2()' style="width:140px; <?php echo $chgAlg2Display?>"></th>
        <th  colspan="3"><input type="text" name="algorithm" id="algorithm" style="visibility:hidden" size="15"></th>
    </tr>
<?php
      if (isset($pool_response))
      {
        if ($pool_response['STATUS'][0]['STATUS'] == 'S')
          $pool_message = "Action successful: ";
        else if ($pool_response['STATUS'][0]['STATUS'] == 'I')
           $pool_message = "Action info: ";
        else if ($pool_response['STATUS'][0]['STATUS'] == 'W')
           $pool_message = "Action warning: ";
        else
           $pool_message = "Action error: ";

        echo "<tr>
                <th colspan='12'  scope='col' class='rounded-company'>"
                  . $pool_message . $pool_response['STATUS'][0]['Msg'].
               "</th>
              </tr>";
      }
      echo "</thead>";
    }
    echo "</table>";
    
    if ((version_compare($API_version, 1.7, '>=')) && $privileged)
    {
?>
      <table class='acuity' summary='cgminerreset' align='center'>
        <tr>
      	  <th colspan="6"  scope="col" class="rounded-company">
      	    <!--To restart cgminer or reboot system or update/rollback/restore cgminer version, click the checkbox, then press the button. Current version <?php echo get_miner_version($host_data);?>-->
			To updateMiner/rollbackMiner/restoreMiner/updataFPGA/rollbackFPGA/restoreFPGA press the corresponding button. Current Miner <?php echo get_miner_version($host_data);?>, Current FPGA <?php echo get_fpga_version($host_data);?><br>
			<div align=center id="info2"></div>
      	  </th>
      	</tr>
      	<tr>
      	   <!--<th>
             <input type="checkbox" value="Restart" name="restartchk">&nbsp;
             <input type="submit" class="button" value="Restart" name="restartbut" >
      	   </th>
      	   <th>
             <input type="checkbox" value="Quit" name="quitchk">&nbsp;
             <input type="submit" class="button" value="Reboot" name="quitbut">
           </th>-->
		   <!--<th>
             <input type="checkbox" value="update" name="updatechk">&nbsp;
             <input type="submit" class="button" value="UpdateMiner" name="updatebut">
           </th>
      	   <th>
             <input type="checkbox" value="rollback" name="rollbackchk">&nbsp;
             <input type="submit" class="button" value="RollbackMiner" name="rollbackbut">
           </th>
      	   <th>
             <input type="checkbox" value="restore" name="restorechk">&nbsp;
             <input type="submit" class="button" value="RestoreMiner" name="restorebut">
           </th>
		   
		   <th>
             <input type="checkbox" value="Restart" name="restartchk">&nbsp;
             <input type="submit" class="button" value="UpdateFPGA" name="restartbut" >
      	   </th>
      	   <th>
             <input type="checkbox" value="Quit" name="quitchk">&nbsp;
             <input type="submit" class="button" value="RollbackFPGA" name="quitbut">
           </th>
		   <th>
             <input type="checkbox" value="RestoreFPGA" name="restoreFPGAchk">&nbsp;
             <input type="submit" class="button" value="RestoreFPGA" name="restoreFPGAbut">
      	   </th>-->
		   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="updatefw2" onclick="updateMiner('#info2');">UpdateMiner</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="rollback2" onclick="rollbackMiner('#info2');">RollbackMiner</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="restore2" onclick="restoreMiner('#info2');">RestoreMiner</button>
           </th>
		   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="updateFPGA2" onclick="updateFPGA('#info2');">UpdateFPGA</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="rollbackFPGA2" onclick="rollbackFPGA('#info2');">RollbackFPGA</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="restoreFPGA2" onclick="restoreFPGA('#info2');">RestoreFPGA</button>
           </th>
        </tr>      
      </table>
<?php
    }
    
    echo "</form>";
    
  }
    if (!$privileged)
    {
?>
      <table class='acuity' summary='cgminerreset' align='center'>
        <tr>
      	  <th colspan="6"  scope="col" class="rounded-company">
      	    Press the button to updateMiner/rollbackMiner/restoreMiner/updateFPGA/rollbackFPGA/restoreFPGA Miner or FPGA. Current Miner <?php echo get_miner_version($host_data);?>, Current FPGA <?php echo get_fpga_version($host_data);?><br>
			<div align=center id="info"></div>
      	  </th>
      	</tr>
      	<tr>
		   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="updatefw" onclick="updateMiner('#info');">UpdateMiner</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="rollback" onclick="rollbackMiner('#info');">RollbackMiner</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="restore" onclick="restoreMiner('#info');">RestoreMiner</button>
           </th>
		   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="updateFPGA" onclick="updateFPGA('#info');">UpdateFPGA</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="rollbackFPGA" onclick="rollbackFPGA('#info');">RollbackFPGA</button>
           </th>
      	   <th>
			<button style="color:#FFFFFF;background:#FF0000;" id="restoreFPGA" onclick="restoreFPGA('#info');">RestoreFPGA</button>
           </th>
        </tr>      
      </table>
<?php    }
?>
<!--
<form name=save action="edithost.php?id=<?php echo $id?>" method="post">
<table id="savetable" align=center>
    <thead>
    	<tr>
        	<th scope="col" class="rounded-company">Name</th>
            <th scope="col" class="rounded-q1">IP / Hostname</th>
            <th scope="col" class="rounded-q1">Port</th>
            <th scope="col" class="rounded-q1">MH/s desired</th>
        </tr>
        <tr>
          <td align=center><input type="text" name="macname" value="<?php echo $host_data['name']?>"></td>
          <td align=center><input type="text" name="ipaddress" value="<?php echo $host_data['address']?>"></td>
          <td align=center><input type="text" name="port" value="<?php echo $host_data['port']?>"></td>
          <td align=center><input type="text" name="mhash" value="<?php echo $host_data['mhash_desired']?>"></td>
        </tr>
        <tr>
        <td colspan=4 align=center><input type=hidden name="savehostid" value="<?php echo $id?>"><input type="submit" class="button" value="Save" name="save"><input type="submit" class="button" value="Delete this host" name="delete"></td>
        </tr>
    </thead>
</table>

</form>
-->
<?php
}
else {
	echo "Host not found or you just deleted the host !<BR>";
}
?>
                <div class="cleaner h20"></div>
<!--                 <a href="#" class="more float_r"></a> -->
            </div>

            <div class="cleaner"></div>
		</div>

        <div class="cleaner"></div>
        </div>
    </div>
    
    <div class="cleaner"></div>

<div id="templatemo_footer_wrapper">
    <div id="templatemo_footer">
        <?php include("footer.inc.php"); ?>
        <div class="cleaner"></div>
    </div>
</div> 

<script>
$(function() {
  setInterval(update, 1600 * <?php echo $config->updatetime ?>);
});
function update() {
	$('#hostsum').load('edithost.php?id=<?php echo $id?> #hostsum');
	$('#devsum').load('edithost.php?id=<?php echo $id?> #devsum');
	$('#poolsum').load('edithost.php?id=<?php echo $id?> #poolsum');
}
</script>
  
</body>
</html>

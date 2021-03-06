<?php
error_reporting('E_ALL');
ini_set('display_errors','On'); 
// Globals
$host_data = null;
$host_alive = false;
$privileged = false;
$voltageSet=0;
$voltageReal=0;
$voltageDis="visibility:hidden";
$chgAlg="Mine Sia";
$chgAlg2="Mine Hsr";
$chgAlgDisplay="visibility:hidden";
$chgAlg2Display="visibility:hidden";
$data_totals_btc = array('hosts'=>0,
                     'devs'=>0,
                     'activedevs'=>0,
                     'maxtemp'=>0, 
                     'desmhash'=>0,
                     'utility'=>0,
                     'Wutility'=>0,
					 'fivesmhash'=>0,
                     'avemhash'=>0,
                     'getworks'=>0,
                     'accepts'=>0, 
                     'rejects'=>0, 
                     'discards'=>0,
                     'stales'=>0, 
                     'getfails'=>0,
                     'remfails'=>0);
$data_totals_ltc = array('hosts'=>0,
                     'devs'=>0,
                     'activedevs'=>0,
                     'maxtemp'=>0, 
                     'desmhash'=>0,
                     'utility'=>0,
                     'Wutility'=>0,
					 'fivesmhash'=>0,
                     'avemhash'=>0,
                     'getworks'=>0,
                     'accepts'=>0, 
                     'rejects'=>0, 
                     'discards'=>0,
                     'stales'=>0, 
                     'getfails'=>0,
                     'remfails'=>0);
// Number of significant digits
$sigdigs = 2;

$API_version = 0;
$CGM_version = "0.0.0";
$pools_in_use = array();
$debug_param_arr = array('Silent', 'Quiet', 'Verbose', 'Debug', 'RPCProto', 'PerDevice', 'WorkTime');

/*****************************************************************************
/*  Function:    get_config_data()
/*  Description: Gets the config data
/*  Inputs:      none
/*  Outputs:     return - config object
*****************************************************************************/
function get_config_data()
{
  global $dbh;
  $config = null;

  $result = $dbh->query("SELECT * FROM configuration");
  if ($result)
    $config = $result->fetch(PDO::FETCH_OBJ);

  return $config;
}

/*****************************************************************************
/*  Function:    get_host_data()
/*  Description: Gets the host data given a host ID
/*  Inputs:      $host_id - the ID of the host
/*  Outputs:     return - data of host in array format
/*               'id', 'address', 'port', 'name', 'mhash_desired'
*****************************************************************************/
function get_host_data($host_id)
{
  global $dbh;
  $host_data = null;

  $result = $dbh->query("SELECT * FROM hosts WHERE id = $host_id");
  if ($result)
    $host_data = $result->fetch(PDO::FETCH_ASSOC);

  return $host_data;
}

/*****************************************************************************
/*  Function:    getsock()
/*  Description: Connects to a port on a remote system
/*  Inputs:      address - IP address to connect to
/*               port - Port to connect to
/*  Outputs:     return - socket
*****************************************************************************/
function getsock($addr, $port)
{
  global $socket_timeout;

  $socket = null;
  $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  if ($socket === false || $socket === null)
  {
    return null;
  }

  socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $socket_timeout, 'usec' => '0'));
  socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $socket_timeout, 'usec' => '0'));

  $res = @socket_connect($socket, $addr, $port);
  if ($res === false)
  {
    socket_close($socket);
    return null;
  }
  return $socket;
}

/*****************************************************************************
/*  Function:    readsockline()
/*  Description: Reads data back from a socket
/*  Inputs:      socket - socket to read from
/*  Outputs:     return - data
*****************************************************************************/
function readsockline($socket)
{
  $line = '';
  while (true)
  {
    $byte = socket_read($socket, 1024);
    if ($byte == '')
       break;
    $line .= $byte;
  }

  return trim($line);
}

/*****************************************************************************
/*  Function:    send_request_to_host()
/*  Description: Sends / Receives data to from a specified host
/*  Inputs:      cmd_array - command, in array format, to send
/*               host_data - host data array from database
/*  Outputs:     return - data received from host in array format
*****************************************************************************/
function send_request_to_host($cmd_array, $host_data)
{
  $socket = getsock($host_data['address'], $host_data['port']);
  
  if ($socket != null)
  {
    $cmd = json_encode($cmd_array);

    socket_write($socket, $cmd, strlen($cmd));
    $line = readsockline($socket);
    socket_close($socket);

    if (strlen($line) == 0)
      return null;
    //echo "<script type='text/javascript'> alert('".$line."'); </script>";
    if (substr($line,0,1) == '{')
      $data = json_decode($line, true);
    else
      return null;
  }
  else
  {
    return null;
  }

  return $data;
}


/*****************************************************************************
/*  Function:    get_host_status()
/*  Description: returns the status of a specified host
/*  Inputs:      host_data - host data array from database
/*  Outputs:     return - true if host cgminer is talking, false if not
*****************************************************************************/
function get_host_status($host_data)
{
  global $API_version;
  global $CGM_version;

  $arr = array ('command'=>'version','parameter'=>'');
  $version_arr = send_request_to_host($arr, $host_data);

  if ($version_arr)
  {
    if ($version_arr['STATUS'][0]['STATUS'] == 'S')
    {
      $API_version = $version_arr['VERSION'][0]['API'];
      $CGM_version = $version_arr['VERSION'][0]['CGMiner'];
      
      if (version_compare($API_version, 1.0, '>='))
        return true;
    }
  }
  return false;
}

/*****************************************************************************
/*  Function:    get_privileged_status()
/*  Description: returns the privilege status of a specified host
/*  Inputs:      host_data - host data array from database
/*  Outputs:     return - true if we can change values, false if not
*****************************************************************************/
function get_privileged_status($host_data)
{
  global $API_version;
  global $CGM_version;

  if ($API_version >= 1.2 )
  {
    $arr = array ('command'=>'privileged','parameter'=>'');
    $response = send_request_to_host($arr, $host_data);

    if ($response['STATUS'][0]['STATUS'] == 'S')
      return true;
  }
  else 
    return true;

  return false;
}


/*****************************************************************************
/*  Function:    get_lockconfig_status()
/*  Description: returns the lock config status of a specified host
/*  Inputs:      host_data - host data array from database
/*  Outputs:     return - true if we can change values, false if not
*****************************************************************************/
function get_lockconfig_status($host_data)
{
    $arr = array ('command'=>'pgalockconfig','parameter'=>'');
    $response = send_request_to_host($arr, $host_data);

    if ($response['PGAS'][0]['LOCKED'] == '1')
      return true;

  return false;
}

// Input: A decimal number as a String.
// Output: The equivalent hexadecimal number as a String.
function dec2hex($number,$sum)
{
    $hexvalues = array('0','1','2','3','4','5','6','7',
               '8','9','a','b','c','d','e','f');
    $hexval = '';
    $loop=0;
    if($sum<=32)
     $max_loop=8;
    else
     $max_loop=12;

    while($loop<$max_loop)
     {
        $hexval = $hexvalues[bcmod($number,'16')].$hexval;
        $number = $number/16; //bcdiv($number,'16',0);//
        $loop=$loop+1;
    }
    return $hexval;
}

/*****************************************************************************
/*  Function:    set_color_high()
/*  Description: Sets the color (Red/yellow/green) according to high number
/*               is red, lowest is green
/*  Inputs:      value - value to be tested
/*               yellow_limit - location of yellow/green border
/*               red_limit - location of yellow/red boarder
/*  Ouputs:      return - color class
*****************************************************************************/
function set_color_high($value, $yellow_limit, $red_limit)
{
    settype ($value , "float");
    settype ($yellow_limit , "float");
    settype ($red_limit , "float");

	if ($value == -1)
	  return null;
	if ($value < $yellow_limit)
		return "class=green";
	if ($value < $red_limit)
		return "class=yellow";
	else
		return "class=red";
}

/*****************************************************************************
/*  Function:    set_color_low()
/*  Description: Sets the color (Red/yellow/green) according to high number
/*               is green, lowest is red
/*  Inputs:      value - value to be tested
/*               yellow_limit - location of yellow/green border
/*               red_limit - location of yellow/red boarder
/*  Ouputs:      return - color class
*****************************************************************************/
function set_color_low($value, $yellow_limit, $red_limit)
{
    settype ($value , "float");
    settype ($yellow_limit , "float");
    settype ($red_limit , "float");

	if ($value == -1)
	  return null;
	if ($value <= $red_limit)
		return "class=red";
	if ($value <= $yellow_limit)
		return "class=yellow";
	else
		return "class=green";
}

/*****************************************************************************
/*  Function:    set_share_colour()
/*  Description: processes the summary array of a host for html display
/*  Inputs:      shares_array - array containing the shares data.
/*                              This could be summary or pool arrays, as
/*                              they have the same element names
/*  Outputs:     return - an array with colours set
*****************************************************************************/
/*
function set_share_colour($shares_array)
{
  global $config, $sigdigs;
  $share_types = array('Accepted', 'Rejected', 'Discarded', 'Stale', 'Get Failures', 'Remote Failures');
  $shares = array('absolute' => $share_types, 'percentage' => $share_types, 'color' => $share_types);

  $accepted =   $shares_array['Accepted'];
  $rejected =   $shares_array['Rejected'];
  $discarded =  $shares_array['Discarded'];
  $stale =      $shares_array['Stale'];
  $getfail =    $shares_array['Get Failures'];
  $remfail =    $shares_array['Remote Failures'];

  if (isset($accepted) && $accepted !== 0)
  {
    $rejects = number_format(100 / $accepted * $rejected, $sigdigs, ".", "");
    $discards = number_format(100 / $accepted * $discarded,$sigdigs, ".", "");
    $stales = number_format(100 / $accepted * $stale, $sigdigs, ".", "");
    $getfails = number_format(100 / $accepted * $getfail, $sigdigs, ".", "");
    $remfails = number_format(100 / $accepted * $remfail, $sigdigs, ".", "");
  }

  $rejectscol = set_color_high($rejects, $config->yellowrejects, $config->maxrejects);      // Rejects
  $discardscol = set_color_high($discards, $config->yellowdiscards, $config->maxdiscards);  // Discards
  $stalescol = set_color_high($stales, $config->yellowstales, $config->maxstales);          // Stales
  $getfailscol = set_color_high($getfails, $config->yellowgetfails, $config->maxgetfails);  // Get fails
  $remfailscol = set_color_high($remfails, $config->yellowremfails, $config->maxremfails);  // Rem fails

  return $shares;

}
*/

/*****************************************************************************
/*  Function:    create_host_header()
/*  Description: Creates the header bar for host information
/*  Inputs:      none
/*  Outputs:     return - host header in html
*****************************************************************************/
function create_host_header()
{
  $header =
    "<thead>
    	<tr>
        	<th scope='col' class='topleft rounded-company'>Address</th>
            <th scope='col' class='rounded-q1'>Devs</th>
            <th scope='col' class='rounded-q1'>Temp</th>
            <th scope='col' class='rounded-q1'>Fans</th>
            <th scope='col' class='rounded-q1'>Util</th>
            <th scope='col' class='rounded-q1'>GH/s 5s</th>
            <th scope='col' class='rounded-q1'>GH/s avg</th>
            <th scope='col' class='rounded-q1'>Rec</th>
            <th scope='col' class='rounded-q1'>Acc</th>
            <th scope='col' class='rounded-q1'>Rej</th>
            <th scope='col' class='rounded-q1'>H/W</th>
            <th scope='col' class='rounded-q1'>Gets</th>
            <th scope='col' class='rounded-q1'>Disc</th>
            <th scope='col' class='rounded-q1'>Stales</th>
            <th scope='col' class='rounded-q1'>Get Fails</th>
            <th scope='col' class='topright rounded-q1'>Rem Fails</th>
        </tr>
    </thead>";
    
    return $header;
}

/*****************************************************************************
/*  Function:    process_host_devs()
/*  Description: processes the array of devices from a host
/*               Retreives the number of devices, total 5s hash rate and max 
/*               temperature of the devices attached to the host
/*  Inputs:      dev_data_array - the array of devices
/*  Outputs:     return - number of devices
/*               activedevs - number of actively mining devices
/*               host5shash - total host 5s hash rate
/*               maxtemp - temperature of hottest device
*****************************************************************************/
function process_host_devs($dev_data_array, &$activedevs, &$host5shash, &$maxtemp, &$activechip)
{
  global $pools_in_use;
  
  $devs = 0;
  $activedevs = 0;
  $host5shash = 0;
  $maxtemp = 0;
  $pools_in_use = array();
  $activechip = 0;

  while(isset($dev_data_array['DEVS'][$devs]))
  {
    # Handle -l parameters in cgminer that change this key from MHS 5s to 2s
    # and such.
    $def5shash = preg_grep('/MHS \d/', array_keys($dev_data_array['DEVS'][$devs]));
    # We have to find the value for the key we just found
if(is_array(array_values($def5shash)) and 
   is_array($dev_data_array['DEVS']) and 
   is_array($dev_data_array['DEVS'][$devs])) {
    $index = array_values($def5shash);
    $index = $index[0];
    $dev5shash = $dev_data_array['DEVS'][$devs][$index];
    $host5shash += $dev5shash;
} else {
    $def5shash = 0;
}

    if (/*$dev_data_array['DEVS'][$devs]['Status'] == "Alive" && */$dev_data_array['DEVS'][$devs]['Enabled'] == "Y")		// by ZWW
    {
      $activedevs++;
    }
    $temp = $dev_data_array['DEVS'][$devs]['Temperature'];

    if ($maxtemp < $temp)
      $maxtemp = $temp;
    
    /* Find which pools are in use */
    $pools_in_use[$dev_data_array['DEVS'][$devs]['Last Share Pool']] = true;
    
    $activechip += $dev_data_array['DEVS'][$devs]['Asics'];
    
    $devs++;
  }

  return $devs;
}


/*****************************************************************************
/*  Function:    process_host_info()
/*  Description: processes the host information such as uptime, version etc.
/*  Inputs:      host_data - the host data array
/*  Outputs:     return - the table of info
*****************************************************************************/
function process_host_info($host_data)
{
  global $API_version;
  global $CGM_version;

  $arr = array ('command'=>'config','parameter'=>'');
  $config_arr = send_request_to_host($arr, $host_data);
  
  $arr = array ('command'=>'summary','parameter'=>'');
  $summary_arr = send_request_to_host($arr, $host_data);
  
  $up_time = $summary_arr['SUMMARY']['0']['Elapsed'];
  $days = floor($up_time / 86400);
  $up_time -= $days * 86400;
  $hours = floor($up_time / 3600);
  $up_time -= $hours * 3600;
  $mins = floor($up_time / 60);
  $seconds = $up_time - ($mins * 60);
  
  $output = "
      <tr>
        <th>CG ver</th>
        <th>API ver</th>
        <th>Up time</th>
        <th>Found H/W</th>
        <th>ADL</th>
        <th>Pools and Strategy</th>
        <th>Supported Devs</th>
        <th>OS</th>
        <th>Scan Time</th>
        <th>Queue</th>
        <th>Expiry</th>
      </tr>
      <tr>
        <td>".$CGM_version."</td>
        <td>".$API_version."</td>
        <td>".$days."d ".$hours."h ".$mins."m ".$seconds."s</td>
        <td>".$config_arr['CONFIG']['0']['CPU Count']." CPUs, ".$config_arr['CONFIG']['0']['GPU Count']." GPUs, ".$config_arr['CONFIG']['0']['PGA Count']." FPGAs</td>
        <td>".$config_arr['CONFIG']['0']['ADL in use']."</td>
        <td>".$config_arr['CONFIG']['0']['Pool Count']." pools, using ".$config_arr['CONFIG']['0']['Strategy']."</td>
        <td>".$config_arr['CONFIG']['0']['Device Code']."</td>
        <td>".$config_arr['CONFIG']['0']['OS']."</td>
        <td><input type='text' name='ScanTime_dro' value='".$config_arr['CONFIG']['0']['ScanTime']."' style='border:0;' size='3' /></td>
        <td><input type='text' name='Queue_dro' value='".$config_arr['CONFIG']['0']['Queue']."' style='border:0;' size='3' /></td>
        <td><input type='text' name='Expiry_dro' value='".$config_arr['CONFIG']['0']['Expiry']."' style='border:0;' size='3' /></td>
      </tr>
  	  <tr>
  	    <th colspan='11'><button type='submit' name='config_submit' value='config_submit'>Submit</button></th>
      </tr>";
  

  return $output;
}

/*****************************************************************************
/*  Function:    process_host_disp()
/*  Description: processes the summary array of a host for html display
/*  Inputs:      desmhash - desires hash rate
/*               summary_data_array - the summary in array form
/*               dev_data_array - the devs list in array form
/*  Outputs:     return - the rows of devices in html
*****************************************************************************/
function process_host_disp($host_data, $desmhash, $summary_data_array, $dev_data_array)
{
  global $data_totals_btc;
  global $data_totals_ltc;
  global $config, $sigdigs;
  global $receive, $hwerror;
  global $pll;
  global $radio;
  global $voltageSet;
  global $voltageReal;
  global $voltageDis;
  global $chgAlgDisplay;
  global $chgAlg2Display;
  $pll=0;
  $radio=1;

  $devs = $activedevs = $max_temp = $fivesmhash = $fivesmhashper = $avgmhper = 0;
  $fivesmhashcol = $avgmhpercol = $rejectscol = $discardscol = $stalescol = $getfailscol = $remfailscol = "";
  $rejects = $discards = $stales = $getfails = $remfails = '---';
  $row = "";

  if ($summary_data_array != null)
  {
    if ($dev_data_array != null){
		if(isset($dev_data_array['DEVS'][$devs])){
			if(isset($dev_data_array['DEVS'][$devs]['TestID2'])){
				$radio=2.4;			// if 2 45G so we set to 2.4 for 5 blade dual-die about 54.9G
			}
		}
        $devs = process_host_devs($dev_data_array, $activedevs, $fivesmhash, $max_temp, $activechips);
    }
    $pll = $summary_data_array['SUMMARY'][0]['PLL'];
	if(isset($summary_data_array['SUMMARY'][0]['Hash Method'])){
		$bladeCnt = 5;
		if(isset($dev_data_array['DEVS'][0])){
			if(isset($dev_data_array['DEVS'][0]['TestID2'])){
				$radio=2;			// for 5 blade dual-die dcr&sia
			}
			$bladeCnt = count($dev_data_array['DEVS']);
		}
		if($summary_data_array['SUMMARY'][0]['Hash Method'] == "blake256")
			$desmhash=$pll*32*48.0*$bladeCnt/1000*$radio;
		else
			$desmhash=$pll*16*48.0*$bladeCnt/1000*$radio;
	}else{
		$desmhash=$pll*16.0/56*$desmhash*$radio;
	}
	if(isset($summary_data_array['SUMMARY'][0]['VOLTAGESET'])){
		$voltageSet = $summary_data_array['SUMMARY'][0]['VOLTAGESET'];
	}
	if(isset($summary_data_array['SUMMARY'][0]['VOLTAGE'])){
		$voltageReal=$summary_data_array['SUMMARY'][0]['VOLTAGE'];
	}
	if($voltageSet==0)
		$voltageDis="visibility:hidden";
	else
		$voltageDis="visibility:visible";
	
	if(isset($summary_data_array['SUMMARY'][0]['DUALMINE'])){
		if($summary_data_array['SUMMARY'][0]['DUALMINE'] == "true")
			$chgAlgDisplay="visibility:visible";
	}
	
	if(isset($summary_data_array['SUMMARY'][0]['HCASH']))
		$chgAlg2Display="visibility:visible";
    
    $avgmhash =   $summary_data_array['SUMMARY'][0]['MHS av'];
    $accepted =   $summary_data_array['SUMMARY'][0]['Accepted'];
    $rejected =   $summary_data_array['SUMMARY'][0]['Rejected'];
    $discarded =  $summary_data_array['SUMMARY'][0]['Discarded'];
    $stale =      $summary_data_array['SUMMARY'][0]['Stale'];
    $getfail =    $summary_data_array['SUMMARY'][0]['Get Failures'];
    $remfail =    $summary_data_array['SUMMARY'][0]['Remote Failures'];
    $utility =    $summary_data_array['SUMMARY'][0]['Utility'];
    $Wutility =    $summary_data_array['SUMMARY'][0]['Work Utility'];
    $getworks =    $summary_data_array['SUMMARY'][0]['Getworks'];
    $receive =   $summary_data_array['SUMMARY'][0]['Received'];
    $hwerror =   $summary_data_array['SUMMARY'][0]['Hardware Errors'];
    /*$temp1 =   round($summary_data_array['SUMMARY'][0]['Temp1'],1);
    $temp2 =   round($summary_data_array['SUMMARY'][0]['Temp2'],1);
    $temp3 =   round($summary_data_array['SUMMARY'][0]['Temp3'],1);
    $temp4 =   round($summary_data_array['SUMMARY'][0]['Temp4'],1);
    $temp5 =   round($summary_data_array['SUMMARY'][0]['Temp5'],1);
    $temp6 =   round($summary_data_array['SUMMARY'][0]['Temp6'],1);
    $temp7 =   round($summary_data_array['SUMMARY'][0]['Temp7'],1);
    $temp8 =   round($summary_data_array['SUMMARY'][0]['Temp8'],1);*/
    $temp1 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp1']);
    $temp2 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp2']);
    $temp3 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp3']);
    $temp4 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp4']);
    $temp5 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp5']);
    $temp6 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp6']);
    $temp7 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp7']);
    $temp8 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp8']);
	if($summary_data_array['SUMMARY'][0]['Temp9'] != null){
		$temp9 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp9']);
		$temp10 =   sprintf("%.01f", $summary_data_array['SUMMARY'][0]['Temp10']);
		$tempx = "9:$temp9&deg;C—10:$temp10&deg;C<BR>";
	}else{
		$tempx = "";
	}
    $pwm = $summary_data_array['SUMMARY'][0]['PWM'];
  
    if (isset($accepted) && $accepted !== 0)
    {
      $efficency = number_format(100 / ($accepted + $rejected) * $accepted, $sigdigs, ".", "") . " %";
      $rejects = number_format(100 / ($accepted + $rejected) * $rejected, $sigdigs, ".", "") . " %";
      $errorper = number_format(100 / ($receive) * $hwerror, $sigdigs, ".", "") . " %";
      $discards = number_format(100 / $accepted * $discarded, $sigdigs, ".", "") . " %";
      $stales = number_format(100 / $accepted * $stale, $sigdigs, ".", "") . " %";
      $getfails = number_format(100 / $accepted * $getfail, $sigdigs, ".", "") . " %";
      $remfails = number_format(100 / $accepted * $remfail, $sigdigs, ".", "") . " %";
      
      $rejectscol = set_color_high($rejects, $config->yellowrejects, $config->maxrejects);     // Rejects
      $discardscol = set_color_high($discards, $config->yellowdiscards, $config->maxdiscards); // Discards
      $stalescol = set_color_high($stales, $config->yellowstales, $config->maxstales);         // Stales
      $getfailscol = set_color_high($getfails, $config->yellowgetfails, $config->maxgetfails); // Get fails
      $remfailscol = set_color_high($remfails, $config->yellowremfails, $config->maxremfails); // Rem fails
    }

    if ($desmhash > 0)
    {
      // Desired Mhash vs. 5s mhash
      $fivesmhashper = number_format(100 / $desmhash * $fivesmhash, $sigdigs, ".", "");
      $fivesmhashcol = set_color_low($fivesmhashper, $config->yellowgessper, $config->maxgessper);

      // Desired Mhash vs. avg mhash
      $avgmhper = number_format(100 / $desmhash * $avgmhash, $sigdigs, ".", "");
      $avgmhpercol = set_color_low($avgmhper, $config->yellowavgmhper, $config->maxavgmhper);
    }

    // Temperature
    // Set red on zero value
    if($max_temp == 0) $max_temp = $config->maxtemp;
    if($config->yellowtemp == 0 and $config->maxtemp == 0) 
	$tempcol = "class=green";
    else 
	$tempcol = set_color_high($max_temp, $config->yellowtemp, $config->maxtemp);
             // host status
    $thisstatuscol = ($thisstatus == "S") ? "class=green" : "class=yellow";     
             // active devs
    $thisdevcol = ($activedevs == $devs) ? "class=green" : "class=red";         
    // <td data-label=\"Temp1\">$temp1&deg;C<BR>$temp2&deg;C</td>
	$row = "
      <td $thisdevcol data-label=\"Devices\">$activechips<BR>$activedevs/$devs</td>
      <td noWrap=\"noWrap\">1:$temp1&deg;C—8:$temp8&deg;C<BR>2:$temp2&deg;C—7:$temp7&deg;C<BR>3:$temp3&deg;C—6:$temp6&deg;C<BR>4:$temp4&deg;C—5:$temp5&deg;C<BR>$tempx</td>
      <td data-label=\"rounded-q1\">$pwm<BR>rpm</td>
      <td data-label=\"Utility\">$utility<br>($Wutility)</td>
      <td data-label=\"5 Sec MH/s\" $fivesmhashcol>$fivesmhash<BR>$fivesmhashper %</td>
      <td $avgmhpercol data-label=\"Avg. MH/s\">$avgmhash<BR>$avgmhper %</td>
      <td >$receive</td>
      <td data-label=\"Accepted\">$accepted<BR>$efficency</td>
      <td data-label=\"Rejects\" $rejectscol>$rejected<BR>$rejects</td>
      <td >$hwerror<BR>$errorper</td>
      <td data-label=\"Getworks\">$getworks</td>
      <td data-label=\"Discards\" $discardscol>$discarded<BR>$discards</td>
      <td data-label=\"Stales\" $stalescol>$stale<BR>$stales</td>
      <td data-label=\"GetFail\" $getfailscol>$getfail<BR>$getfails</td>
      <td data-label=\"RemFail\" $remfailscol>$remfail<BR>$remfails</td>";

    // Sum Stuff
   if(strstr($host_data['name'],"BTC"))
{
    $data_totals_btc['hosts']++;
   $data_totals_btc['devs'] += $devs;
    $data_totals_btc['activedevs'] += $activedevs;
    $data_totals_btc['maxtemp'] = ($data_totals_btc['maxtemp'] > $max_temp) ? $data_totals_btc['maxtemp'] : $max_temp;
    $data_totals_btc['desmhash'] += $desmhash;
    $data_totals_btc['utility'] += $utility;
    $data_totals_btc['Wutility'] += $Wutility;
    $data_totals_btc['fivesmhash'] += $fivesmhash;
    $data_totals_btc['avemhash'] += $avgmhash;
    $data_totals_btc['accepts'] += $accepted;
    $data_totals_btc['getworks'] += $getworks;
    $data_totals_btc['rejects'] += $rejects;
    $data_totals_btc['discards'] += $discards;
    $data_totals_btc['stales'] += $stales;
    $data_totals_btc['getfails'] += $getfails;
    $data_totals_btc['remfails'] += $remfails;
 }
if(strstr($host_data['name'],"LTC"))
{
 $data_totals_ltc['hosts']++;
   $data_totals_ltc['devs'] += $devs;
    $data_totals_ltc['activedevs'] += $activedevs;
    $data_totals_ltc['maxtemp'] = ($data_totals_ltc['maxtemp'] > $max_temp) ? $data_totals_ltc['maxtemp'] : $max_temp;
    $data_totals_ltc['desmhash'] += $desmhash;
    $data_totals_ltc['utility'] += $utility;
    $data_totals_ltc['Wutility'] += $Wutility;
    $data_totals_ltc['fivesmhash'] += $fivesmhash;
    $data_totals_ltc['avemhash'] += $avgmhash;
    $data_totals_ltc['accepts'] += $accepted;
    $data_totals_ltc['getworks'] += $getworks;
    $data_totals_ltc['rejects'] += $rejects;
    $data_totals_ltc['discards'] += $discards;
    $data_totals_ltc['stales'] += $stales;
    $data_totals_ltc['getfails'] += $getfails;
    $data_totals_ltc['remfails'] += $remfails;
}
  }

  return $row;
}

/*****************************************************************************
/*  Function:    get_host_summary()
/*  Description: gets the summary of a host
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - Host summary in html
*****************************************************************************/
function get_host_summary($host_data)
{
  $hostid = $host_data['id'];
  $name = $host_data['name'];
  $host = $host_data['address'];
  $hostport = $host_data['port'];
  $desmhash = $host_data['mhash_desired'];
  $host_row = "";
  global $chgAlg;
  global $chgAlg2;

  $arr = array ('command'=>'summary','parameter'=>'');
  $summary_arr = send_request_to_host($arr, $host_data);

  if ($summary_arr != null)
  {
	if(isset($summary_arr['SUMMARY'][0]['Hash Method'])){
		if($summary_arr['SUMMARY'][0]['Hash Method'] == "blake256"){
			$name="CGMiner-Dcr";
			$chgAlg="Mine Sia";
		}else{
			$name="CGMiner-Sia";
			$chgAlg="Mine Dcr";
		}
		if(isset($summary_arr['SUMMARY'][0]['HCASH'])){
			if($summary_arr['SUMMARY'][0]['HCASH'] == "true"){
				$name="CGMiner-Hsr";
				$chgAlg2="Mine Dcr";
			}else{
				$chgAlg2="Mine Hsr";
			}
		}
	}
    $arr = array ('command'=>'devs','parameter'=>'');
    $dev_arr = send_request_to_host($arr, $host_data);

    $host_row = process_host_disp($host_data, $desmhash, $summary_arr, $dev_arr);
  }
  else
  {
    // No data from host
    $error = socket_strerror(socket_last_error());
    $msg = "Connection to $host:$hostport failed: ";
    $host_row = "<td colspan='16'>$msg '$error'</td>";
  }

  $host_row = "<tbody><tr>
    <td><table border=0><tr>
      <td data-label=\"Host\"><a href=\"edithost.php?id=$hostid\"><span class=\"button\">Config</span></a></td>
      <td class=\"hostname\"><a href=\"edithost.php?id=$hostid\">$name</a></td></td>
    </tr></table></td>"
    . $host_row .
    "</tr></tbody>";

  return $host_row;
}

/*****************************************************************************
/*  Function:    create_devs_header()
/*  Description: Creates the header bar for devices information
/*  Inputs:      none
/*  Outputs:     return - device header in html
*****************************************************************************/
function create_devs_header()
{
$header =
    "<thead>
    	<tr>
        	<th scope='col' class='rounded-company'>Dev</th>
            <th scope='col' class='rounded-q1'>En</th>
            <th scope='col' class='rounded-q1'>Run Time</th>
            <th scope='col' class='rounded-q1'>PLL</th>
            <th scope='col' class='rounded-q1'>Baud</th>
            <th scope='col' class='rounded-q1'>Chips Map</th>
            <th scope='col' class='rounded-q1'>Active Map</th>
            <th scope='col' class='rounded-q1'>GH/s 5s</th>
            <th scope='col' class='rounded-q1'>GH/s avg</th>
            <th scope='col' class='rounded-q1'>Send</th>
            <th scope='col' class='rounded-q1'>Rec</th>
            <th scope='col' class='rounded-q1'>Acc</th>
            <th scope='col' class='rounded-q1'>Rej</th>
            <th scope='col' class='rounded-q1'>H/W</th>
            <th scope='col' class='rounded-q1'>Share Diff</th>
            <th scope='col' class='rounded-q1'>Util</th>
            <th scope='col' class='rounded-q1'>Pool</th>
        </tr>
    </thead>";
    
    return $header;
}


/*****************************************************************************
/*  Function:    process_dev_disp()
/*  Description: Processes a single device data for html display
/*  Inputs:      gpu_data_array - the device array data
/*               edit - flag to show start/stop buttons
/*  Outputs:     return - html formatted table row
*****************************************************************************/
function process_dev_disp($gpu_data_array, $edit=false)
{
  global $config;
  global $id;
  global $privileged, $sigdigs;

  $accepted =   $gpu_data_array['Accepted'];
  $rejected =   $gpu_data_array['Rejected'];
  $received =  $gpu_data_array['Recieved'];
  $hwerrors = $gpu_data_array['Hardware Errors'];
  
  if (isset($accepted) && $accepted !== 0)
  {
    $efficency = number_format(100 / ($accepted + $rejected) * $accepted, $sigdigs, ".", "") . " %";
    $rejects = number_format(100 / ($accepted + $rejected) * $rejected, $sigdigs, ".", "") . " %";
  }
  if (isset($accepted) && $accepted !== 0)
  {
     $hwpercent = number_format(100 / ($received) * $hwerrors, $sigdigs, ".", "") . " %";
  }
  /* set colors */
  $encol = ($gpu_data_array['Enabled'] == "Y") ? "class=green" : "class=red";                      // Enabled
  $alcol = ($gpu_data_array['Status'] == "Alive") ? "class=green" : "class=red";                   // Alive
  // Temperature
  // Set red on zero value
  if($max_temp == 0) $max_temp = $config->maxtemp;
  if($config->yellowtemp == 0 and $config->maxtemp == 0) 
	$tempcol = "class=green";
  else 
  	$tmpcol = set_color_high($gpu_data_array['Temperature'], $config->yellowtemp, $config->maxtemp);
  // Fans
  if($config->yellowfan == 0 and $config->maxfan == 0) $fancol = "class=green";
  else $fancol = set_color_high($gpu_data_array['Fan Percent'], $config->yellowfan, $config->maxfan);
  
  /* format fan speeds */
  $fanspeed = ($gpu_data_array['Fan Speed'] == '-1') ? '---' : $gpu_data_array['Fan Speed'];
  $fanpercent = ($gpu_data_array['Fan Percent'] == '-1') ? '---' : $gpu_data_array['Fan Percent']. " %";

  $DEV_cell = '???';

  $GPU_specific1 =
    "<td>---</td>
    <td>---</td>
    <td>---</td>
    <td>---</td>
    <td>---</td>";
  $GPU_specific2 =
    "<td>---</td>";

  $button = $gpu_data_array['Enabled'];
  
  if(($gpu_data_array['Status'] != "Alive"))
    $button_disable = " disabled='disabled'";

  /* format DEV number */
  if (isset($gpu_data_array['GPU']))
  {
    if ($privileged)
    {
      /* show buttons if selected */
      if($edit)
      {
        if(($gpu_data_array['Enabled'] == "Y"))
        {
          $button =
            "<input type='submit' value='Stop' name='stop'".$button_disable."><br>
             <input type='submit' value='Restart' name='restart' ".$button_disable.">";
        }
        else
        {
          $button =
            "<input type='submit' value='Start' name='start'".$button_disable."><br>
             <input type='submit' value='Restart' name='restart' disabled='disabled'>";
        }
      }

      $DEV_cell =
      "<table border=0><tr>
        <td><a href='editdev.php?id=".$id."&dev=".$gpu_data_array['GPU']."&type=GPU'><span class=\"button\">Modify</span></a></td>
        <td><a href='editdev.php?id=".$id."&dev=".$gpu_data_array['GPU']."&type=GPU'>GPU" .$gpu_data_array['GPU']."</a></td></td>
      </tr></table>";
    }
    else
    {
      $DEV_cell = "GPU" . $gpu_data_array['GPU'];
    }

    $GPU_specific1 =
      "<td $fancol>".$fanspeed."<BR>".$fanpercent."</td>
      <td>".$gpu_data_array['GPU Clock']."</td>
      <td>".$gpu_data_array['Memory Clock']."</td>
      <td>".$gpu_data_array['GPU Voltage']."</td>
      <td>".$gpu_data_array['GPU Activity']." %</td>";

    $GPU_specific2 = 
      "<td>".$gpu_data_array['Intensity']."</td>";
  }
  else if (isset($gpu_data_array['PGA']))
  {
    if ($privileged)
    {
      if(($gpu_data_array['Enabled'] == "Y"))
        //$button = "<button class='button' type='submit' name='stoppga' value='".$gpu_data_array['PGA'].$button_disable."'>Stop</button>";
        $button = "<button class='button' type='submit' name='stoppga' value='".$gpu_data_array['PGA']."' disabled='disabled'>Stop</button>";
      else
        //$button = "<button class='button' type='submit' name='startpga' value='".$gpu_data_array['PGA'].$button_disable."'>Start</button>";
        $button = "<button class='button' type='submit' name='startpga' value='".$gpu_data_array['PGA']."' disabled='disabled'>Start</button>";
    }

    $DEV_cell = $gpu_data_array['Name'] . $gpu_data_array['PGA'];   // by ZWW
      /*$DEV_cell =
      "<table border=0>
        <td><a href='editdev.php?id=".$id."&dev=".$gpu_data_array['PGA']."&type=PGA'><span class=\"button\">Modify</span></a></td>
        <td><a href='editdev.php?id=".$id."&dev=".$gpu_data_array['PGA']."&type=PGA'>DM" .$gpu_data_array['PGA']."</a></td></td>
      </table>";*/
    $GPU_specific1 =
      "<td>".$gpu_data_array['PLL']."</td>
      <td>".$gpu_data_array['Baudrate']."</td>
      <td>".$gpu_data_array['Asics']."<BR>".$gpu_data_array['TestPllStr']."</td>
       <td>".$gpu_data_array['ActiveNum']."<BR>".$gpu_data_array['ActiveStr']."</td>";
    $GPU_specific2 =
      "<td>".$gpu_data_array['CurPool']."</td>";
}  
  else if (isset($gpu_data_array['CPU']))
  {
    $DEV_cell = $gpu_data_array['Name'] . $gpu_data_array['CPU'];
  }
  
  $diff_1_utill = round($gpu_data_array['Utility']*$gpu_data_array['Difficulty Accepted']/$accepted,2);

  # Handle -l parameters in cgminer that change this key from MHS 5s to 2s
  # and such.
  $def5shash = preg_grep('/MHS \d/', array_keys($gpu_data_array));
  # We have to find the value for the key we just found
  if(is_array($def5shash) and is_array($gpu_data_array)) {
    $index = array_values($def5shash);
    $index = $index[0];
    $def5shash = $gpu_data_array[$index];
  } else {
    $def5shash = 0;
  }
  /* form row */
  $row = " <tr>
  <td>".$DEV_cell."</td>
  <td $encol>".$button."</td>
  <td $tmpcol>".floor(($gpu_data_array['RunTime']/3600)/24)."days<BR>".floor(($gpu_data_array['RunTime']/3600)%24).":".(($gpu_data_array['RunTime']/60)%60).":".($gpu_data_array['RunTime']%60)."</td>"
  . $GPU_specific1 .
  "<td>".$def5shash."</td>
  <td>".$gpu_data_array['MHS av']."</td>
  <td>".$gpu_data_array['Send']."<BR>".$gpu_data_array['SendErr']."</td>
  <td>".$gpu_data_array['Recieved']."</td>
  <td>".$accepted."<BR>".$efficency."</td>
  <td>".$rejected."<BR>".$rejects."</td>
  <td>".$gpu_data_array['Hardware Errors']."<BR>".$hwpercent."</td>
  <td>".$gpu_data_array['Last Share Difficulty']."</td>
  <td>".$gpu_data_array['Utility']."<BR>(".$diff_1_utill.")</td>"
  . $GPU_specific2 .
  "</tr>";

  return $row;
}
//  <td $alcol>".$gpu_data_array['Status']."</td>

function get_chip_status($host_data)
{
    $hostid = $host_data['id'];
    $name = $host_data['name'];
    $host = $host_data['address'];
    $hostport = $host_data['port'];
    $host_row = "";

    $host_row =
        "<thead>
            <tr>
                <th scope='col' class='rounded-company'>Dev</th>
                <th scope='col' class='rounded-q1'>Info</th>
            </tr>
        </thead>";
            
    $arr = array ('command'=>'chips','parameter'=>'');
    $chips_arr = send_request_to_host($arr, $host_data);

    if ($chips_arr != null)
    {
        $i = 0;
        while (isset($chips_arr['CHIPS'][$i]))
        {
            $tablebody="<tr><td>".$chips_arr['CHIPS'][$i]['PGA']."</td><td>".$chips_arr['CHIPS'][$i]['info']."</td></tr>";
            $i++;
            $host_row = $host_row.$tablebody;
        }
    }
    else
    {
        // No data from host
        $error = socket_strerror(socket_last_error());
        $msg = "Connection to $host:$hostport failed: ";
        $host_row = "<td colspan='16'>$msg '$error'</td>";
    }
    
    return $host_row;
}

/*****************************************************************************
/*  Function:    process_devs_disp()
/*  Description: processes the devs of a host for html display
/*  Inputs:      host_data - the host data array.
/*               edit - flag to show start/stop buttons
/*  Outputs:     return - Devs table in html
*****************************************************************************/
function process_devs_disp($host_data, $edit=false)
{
  global $id;

  $i = 0;
  $table = "";

  $arr = array ('command'=>'devs','parameter'=>'');
  $devs_arr = send_request_to_host($arr, $host_data);

  if ($devs_arr != null)
  {
    $id = $host_data['id'];
    while (isset($devs_arr['DEVS'][$i]))
    {
      $table .= process_dev_disp($devs_arr['DEVS'][$i], $edit);
      $i++;
    }
  }

  return $table;
}

/*****************************************************************************
/*  Function:    get_miner_version()
/*  Description: processes the devs of a host for html display
/*  Inputs:      host_data - the host data array.
/*               edit - flag to show start/stop buttons
/*  Outputs:     return - Devs table in html
*****************************************************************************/
function get_miner_version($host_data)
{
  $table = "";

  $arr = array ('command'=>'devs','parameter'=>'');
  $devs_arr = send_request_to_host($arr, $host_data);

  if ($devs_arr != null)
  {
    return (($devs_arr['DEVS'][0]['Version']));
  }

  return $table;
}

function get_fpga_version($host_data)
{
	$arr = array ('command'=>'summary','parameter'=>'');
	$summary_arr = send_request_to_host($arr, $host_data);
	if ($summary_arr != null)
	{
		return (($summary_arr['SUMMARY'][0]['FPGAVer']));
	}
	
	$fpganame=system("basename `find /home/orangepi/share/outputdash/bin/ -maxdepth 1 -type f -name '*.bin' | head -n 1`");
	if ($fpganame != null)
	{
		return "";
	}
	return "fpga unknown";
}

/*****************************************************************************
/*  Function:    get_dev_data()
/*  Description: retrives a single dev from a host
/*  Inputs:      host_data - the host data array.
/*               devid - the the device ID.
/*               type - the the device type (CPU/GPU/PGA).
/*  Outputs:     return - the device data array
*****************************************************************************/
function get_dev_data($host_data, $devid, $type)
{
  if ($type == 'CPU')
  {
    $cmnd = 'cpu';
  }
  else if ($type == 'GPU')
  {
    $cmnd = 'gpu';
  }
  else if ($type == 'PGA')
  {
    $cmnd = 'pga';
  }

  $arr = array ('command'=>$cmnd,'parameter'=>$devid);
  $dev_arr = send_request_to_host($arr, $host_data);

  return $dev_arr[$type]['0'];
}

/*****************************************************************************
/*  Function:    create_pool_header()
/*  Description: Creates the header bar for pool information
/*  Inputs:      none
/*  Outputs:     return - pool header in html
*****************************************************************************/
function create_pool_header()
{
  $header =
    "<thead>
    <tr>
      <th scope='col' class='rounded-company'>Pool</th>
      <th scope='col' class='rounded-q1'>Priority</th>
      <th scope='col' class='rounded-q1' colspan='2'>URL</th>
      <th scope='col' class='rounded-q1'>Gets</th>
      <th scope='col' class='rounded-q1'>Diff</th>
      <th scope='col' class='rounded-q1'>Accepts</th>
      <th scope='col' class='rounded-q1'>Rejects</th>
      <th scope='col' class='rounded-q1'>Discards</th>
      <th scope='col' class='rounded-q1'>Stales</th>
      <th scope='col' class='rounded-q1'>Get Fails</th>
      <th scope='col' class='rounded-q1'>Rem fails</th>
      </tr>
    </thead>";

  return $header;
}

/*****************************************************************************
/*  Function:    process_pool_disp()
/*  Description: processes a single item of the pool array of a host for html display
/*  Inputs:      pool_data_array - the pool array data.
/*               edit - flag to show priority buttons
/*  Outputs:     return - the row the pool in html
*****************************************************************************/
function process_pool_disp($pool_data_array, $edit=false)
{
  global $config, $sigdigs;
  global $API_version;
  global $pools_in_use;
  global $sel_pool;
  global $sel_url,$sel_user,$sel_passwd;
  
  $fivesmhashcol = $avgmhpercol = $rejectscol = $discardscol = $stalescol = $getfailscol = $remfailscol = "";
  $rejects = $discards = $stales = $getfails = $remfails = '---';

  $getworks =   $pool_data_array['Getworks'];
  $accepted =   $pool_data_array['Accepted'];
  $rejected =   $pool_data_array['Rejected'];
  $discarded =  $pool_data_array['Discarded'];
  $stale =      $pool_data_array['Stale'];
  $getfail =    $pool_data_array['Get Failures'];
  $remfail =    $pool_data_array['Remote Failures'];
  $difficulty = round($pool_data_array['Difficulty Accepted']/$pool_data_array['Accepted'],3);   

  /* set shares colours */
  if (isset($accepted) && $accepted !== 0)
  {
    $efficency = number_format(100 / $getworks * $accepted, $sigdigs, ".", "") . " %";
    $accepts = number_format(100 / ($accepted + $rejected) * $accepted, $sigdigs, ".", "") . " %";
    $rejects = number_format(100 / ($accepted + $rejected) * $rejected, $sigdigs, ".", "") . " %";
    $discards = number_format(100 / $getworks * $discarded, $sigdigs, ".", "") . " %";
    $stales = number_format(100 / $accepted * $stale, $sigdigs, ".", "") . " %";
    $getfails = number_format(100 / $accepted * $getfail, $sigdigs, ".", "") . " %";
    $remfails = number_format(100 / $accepted * $remfail, $sigdigs, ".", "") . " %";

    $rejectscol = set_color_high($rejects, $config->yellowrejects, $config->maxrejects);      // Rejects
    $discardscol = set_color_high($discards, $config->yellowdiscards, $config->maxdiscards);  // Discards
    $stalescol = set_color_high($stales, $config->yellowstales, $config->maxstales);          // Stales
    $getfailscol = set_color_high($getfails, $config->yellowgetfails, $config->maxgetfails);  // Get fails
    $remfailscol = set_color_high($remfails, $config->yellowremfails, $config->maxremfails);  // Rem fails
  }

  /* set pool colour */
  if ($pool_data_array['Status'] == "Alive")
    $alcol = "class=green";
  else if ($pool_data_array['Status'] == "Disabled")
    $alcol = "class=yellow";
  else
    $alcol = "class=red";

  /* format buttons */
  $top_button = "";
  $start_stop_button = "";
  if($edit)
  {
    $disable_button = ($pool_data_array['Priority'] == '0') ? " disabled='disabled'" : "";
    $top_button = " <button type='submit' class='button' name='toppool' value='".$pool_data_array['POOL']. "' " . $disable_button.">Top</button>";
    if($sel_pool==$pool_data_array['POOL']) {
        $view_button = "<button type='submit' name='viewpool' style='background:red;color:white' value='" . $pool_data_array['POOL'] . "'>√</button>";
        $view_button.="<input type='radio' style='display:none' name='selpool' checked='checked' value='".$pool_data_array['POOL']."'/>";
    }else{
      $view_button = "<button type='submit' name='viewpool' value='".$pool_data_array['POOL']."'>√</button>";
        $view_button.="<input type='radio' style='display:none' name='selpool' value='".$pool_data_array['POOL']."'/>";
    }

    if($pool_data_array['Status'] == "Alive")
      $start_stop_button = " <button type='submit' class='button' name='stoppool' value='".$pool_data_array['POOL']."'>Stop</button>";
    else if ($pool_data_array['Status'] == "Disabled")
      $start_stop_button = " <button type='submit' class='button' name='startpool' value='".$pool_data_array['POOL']."'>Start</button>";
    else
      $start_stop_button = " <button disabled='disabled'>Start</button>";

    if (version_compare($API_version, 1.7, '>='))
    {
        $start_stop_button .= "<button type='submit' class='button' name='rempool' value='".$pool_data_array['POOL']."'>Delete</button>";
    }
  }
  if($sel_pool==$pool_data_array['POOL'])
  {
	  $sel_url = $pool_data_array['URL'];
	  $sel_user = $pool_data_array['User'];
	  $sel_passwd = $pool_data_array['Passwd'];
  }
  
  /*Set in-use colour */
  $poolcol = "";
  if ($pools_in_use[$pool_data_array['POOL']] == true)
    $poolcol = "class=green";
    
  $row = "<tr>
  <td $poolcol>".$pool_data_array['POOL'].$view_button."</td>
  <td>".$pool_data_array['Priority'].$top_button."</td>
  <td $alcol>".$pool_data_array['URL']."</td>
  <td $alcol>".$start_stop_button ."</td>
  <td>".$getworks."</td>
  <td>$difficulty</td>
  <td>".$accepted."<BR>".$accepts."</td>
  <td $rejectscol>".$rejected."<BR>".$rejects."</td>
  <td $discardscol>".$discarded."<BR>".$discards."</td>
  <td $stalescol>".$stale."<BR>".$stales."</td>
  <td $getfailscol>".$getfail."<BR>".$getfails."</td>
  <td $remfailscol>".$remfail."<BR>".$remfails."</td>
  </tr>";

  return $row;
}

/*****************************************************************************
/*  Function:    process_pools_disp()
/*  Description: processes the pools of a host for html display
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - Pool table in html
*****************************************************************************/
function process_pools_disp($host_data, $edit=false)
{
  $i = 0;
  $table = "";


  $arr = array ('command'=>'pools','parameter'=>'');
  $pool_arr = send_request_to_host($arr, $host_data);

  if ($pool_arr != null)
  {
    while (isset($pool_arr['POOLS'][$i]))
    {
      $table .= process_pool_disp($pool_arr['POOLS'][$i], $edit);
      $i++;
    }
  }
  return $table;
}

/*****************************************************************************
/*  Function:    get_warning_info()
/*  Description: get warning info for html display
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - warning info  table in html
 *****************************************************************************/
function get_warning_info($host_data)
{
    global $emailserver;
    global $emailuser;
    global $emailpwd;
    global $warnemail;
    global $warnhash,$warnreject,$warntime,$warnstarttime,$timezone;
    global $warnhashcnt,$warnrejectcnt,$warntempcnt;
    global $temptime,$warntemp,$safetemp,$sensorfault;
    global $strsensorfault;
    global $staticip,$strstaticip;
    global $netmask,$gateway,$ip,$port;
    global $fanspwm;
    $table = "";

    $arr = array ('command'=>'pgawarnstatus','parameter'=>'');
    $warn_arr = send_request_to_host($arr, $host_data);

    if ($warn_arr != null)
    {
        $emailserver = $warn_arr['WARN'][0]['emailserver'];
        $emailuser = $warn_arr['WARN'][0]['emailuser'];
        //echo "<script type='text/javascript'> alert('".$emailuser."'); </script>";
        $emailpwd = $warn_arr['WARN'][0]['emailpass'];
        $warnemail = $warn_arr['WARN'][0]['warnemail'];
        $warnhash = $warn_arr['WARN'][0]['warnhash'];
        $warnreject = $warn_arr['WARN'][0]['warnreject'];
        $warntime = $warn_arr['WARN'][0]['warntime'];
        $warnhashcnt = $warn_arr['WARN'][0]['warnhashcnt'];
        $warnrejectcnt = $warn_arr['WARN'][0]['warnrejectcnt'];
        $warntempcnt = $warn_arr['WARN'][0]['warntempcnt'];
        $warnstarttime = $warn_arr['WARN'][0]['warnstarttime'];
        $timezone = $warn_arr['WARN'][0]['timezone'];
        $temptime = $warn_arr['WARN'][0]['temptime'];
        $warntemp = $warn_arr['WARN'][0]['warntemp'];
        $safetemp = $warn_arr['WARN'][0]['safetemp'];
        $sensorfault = $warn_arr['WARN'][0]['sensorfault'];
        if($sensorfault)
        	$strsensorfault='checked="checked"';
        else
        	$strsensorfault="";
        $ip = $warn_arr['WARN'][0]['ip'];
        $port = $warn_arr['WARN'][0]['apiport'];
        $netmask = $warn_arr['WARN'][0]['netmask'];
        $gateway = $warn_arr['WARN'][0]['gateway'];
        $staticip = $warn_arr['WARN'][0]['staticip'];
        if($staticip)
        	$strstaticip='checked="checked"';
        else
        	$strstaticip="";
         $pi_apiport = $port;
         $fanspwm = $warn_arr['WARN'][0]['fanspwm'];
    }

    return $table;
}

/*****************************************************************************
/*  Function:    get_api_port()
/*  Description: get warning info for html display
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - warning info  table in html
 *****************************************************************************/
function get_api_port($host_data)
{
    $port = 4029;
 
    $arr = array ('command'=>'pgawarnstatus','parameter'=>'');
    $warn_arr = send_request_to_host($arr, $host_data);

    if ($warn_arr != null)
    {
        $port = $warn_arr['WARN'][0]['apiport'];
    }
    return $port;
}

/*****************************************************************************
/*  Function:    create_btc_totals()
/*  Description: forms the totals row
/*  Inputs:      none
/*  Outputs:     return - the html formatted totals
*****************************************************************************/
function create_btc_totals()
{
    global $data_totals_btc, $sigdigs;
    
    $sumrejects = number_format($data_totals_btc['rejects'] / $data_totals_btc['hosts'],$sigdigs, ".", "");
    $sumdiscards = number_format($data_totals_btc['discards'] / $data_totals_btc['hosts'],$sigdigs, ".", "");
    $sumstales = number_format($data_totals_btc['stales'] / $data_totals_btc['hosts'],$sigdigs, ".", "");
    $sumgetfails = number_format($data_totals_btc['getfails'] / $data_totals_btc['hosts'],$sigdigs, ".", "");
    $sumremfails = number_format($data_totals_btc['remfails'] / $data_totals_btc['hosts'],$sigdigs, ".", "");

    $totals =
    "<thead>
    	<tr>
            <th scope='col' class='bottomleft rounded-company'> iBelink Host ".$data_totals_btc['hosts']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['devs']."/".$data_totals_btc['activedevs']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['maxtemp']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['desmhash']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['utility']."</th>
            <th id=\"Speed_Btc\" scope='col' class='rounded-q1'>".$data_totals_btc['fivesmhash']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['avemhash']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['getworks']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_btc['accepts']."</th>
            <th scope='col' class='rounded-q1'>".$sumrejects." %</th>
            <th scope='col' class='rounded-q1'>".$sumdiscards." %</th>
            <th scope='col' class='rounded-q1'>".$sumstales." %</th>
            <th scope='col' class='rounded-q1'>".$sumgetfails." %</th>
            <th scope='col' class='bottomright rounded-q1'>".$sumremfails." %</th>
        </tr>
    </thead>";
    return $totals;
}
/*****************************************************************************
/*  Function:    create_ltc_totals()
/*  Description: forms the totals row
/*  Inputs:      none
/*  Outputs:     return - the html formatted totals
*****************************************************************************/
function create_ltc_totals()
{
    global $data_totals_ltc, $sigdigs;
    
    $sumrejects = number_format($data_totals_ltc['rejects'] / $data_totals_ltc['hosts'],$sigdigs, ".", "");
    $sumdiscards = number_format($data_totals_ltc['discards'] / $data_totals_ltc['hosts'],$sigdigs, ".", "");
    $sumstales = number_format($data_totals_ltc['stales'] / $data_totals_ltc['hosts'],$sigdigs, ".", "");
    $sumgetfails = number_format($data_totals_ltc['getfails'] / $data_totals_ltc['hosts'],$sigdigs, ".", "");
    $sumremfails = number_format($data_totals_ltc['remfails'] / $data_totals_ltc['hosts'],$sigdigs, ".", "");

    $totals =
    "<thead>
    	<tr>
        	<th scope='col' class='bottomleft rounded-company'>LTC Host ".$data_totals_ltc['hosts']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['devs']."/".$data_totals_ltc['activedevs']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['maxtemp']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['desmhash']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['utility']."</th>
            <th id=\"Speed_Ltc\" scope='col' class='rounded-q1'>".$data_totals_ltc['fivesmhash']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['avemhash']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['getworks']."</th>
            <th scope='col' class='rounded-q1'>".$data_totals_ltc['accepts']."</th>
            <th scope='col' class='rounded-q1'>".$sumrejects." %</th>
            <th scope='col' class='rounded-q1'>".$sumdiscards." %</th>
            <th scope='col' class='rounded-q1'>".$sumstales." %</th>
            <th scope='col' class='rounded-q1'>".$sumgetfails." %</th>
            <th scope='col' class='bottomright rounded-q1'>".$sumremfails." %</th>
        </tr>
    </thead>";
    return $totals;
}

/*****************************************************************************
/*  Function:    create_notify_header()
/*  Description: Creates the header bar for notification information
/*  Inputs:      none
/*  Outputs:     return - notify header in html
*****************************************************************************/
function create_notify_header()
{
  $header =
    "<thead>
    <tr>
      <th scope='col' rowspan='2' class='rounded-company'>Device</th>
      <th scope='col' colspan='2' class='rounded-q1'>Time</th>
      <th scope='col' rowspan='2' class='rounded-q1'>Reason</th>
      <th scope='col' colspan='3' class='rounded-q1'>Thread Counters</th>
      <th scope='col' colspan='7' class='rounded-q1'>Device Counters</th>
    </tr>
    <tr>
      <th scope='col' class='rounded-q1'>Well</th>
      <th scope='col' class='rounded-q1'>Ill</th>
      <th scope='col' class='rounded-q1'>Fail<br>Init</th>
      <th scope='col' class='rounded-q1'>Zero<br>Hash</th>
      <th scope='col' class='rounded-q1'>Fail<br>Queue</th>
      <th scope='col' class='rounded-q1'>Sick<br>60s</th>
      <th scope='col' class='rounded-q1'>Dead<br>10m</th>
      <th scope='col' class='rounded-q1'>Nostart</th>
      <th scope='col' class='rounded-q1'>Over<br>Heat</th>
      <th scope='col' class='rounded-q1'>Thermal<br>Cutoff</th>
      <th scope='col' class='rounded-q1'>Comms<br>Error</th>
      <th scope='col' class='rounded-q1'>Throt</th>
      </tr>
</thead>";

  return $header;
}

/*****************************************************************************
/*  Function:    process_notify_disp()
/*  Description: processes a single item of the notification array of a host
/*               for html display
/*  Inputs:      notify_data_array - the device detail array data.
/*  Outputs:     return - the row in html
*****************************************************************************/
function process_notify_disp($notify_data_array)
{
  $well_time = $notify_data_array['Last Well'];
  $notwell_time = $notify_data_array['Last Not Well'];

  if ($well_time > 0)
    $well_text = date ('d/m/y, H:i:s', $well_time);
  else
    $well_text = "Never well :(";

  if ($notwell_time > 0)
    $notwell_text = date ('d/m/y, H:i:s', $notwell_time);
  else
    $notwell_text = "Never ill :)";

  $row = "<tr>
  <td>".$notify_data_array['Name'] . $notify_data_array['ID']."</td>
  <td>".$well_text."</td>
  <td>".$notwell_text."</td>
  <td>".$notify_data_array['Reason Not Well'] ."</td>
  <td>".$notify_data_array['*Thread Fail Init']."</td>
  <td>".$notify_data_array['*Thread Zero Hash']."</td>
  <td>".$notify_data_array['*Thread Fail Queue']."</td>
  <td>".$notify_data_array['*Dev Sick Idle 60s']."</td>
  <td>".$notify_data_array['*Dev Dead Idle 600s']."</td>
  <td>".$notify_data_array['*Dev Nostart']."</td>
  <td>".$notify_data_array['*Dev Over Heat']."</td>
  <td>".$notify_data_array['*Dev Thermal Cutoff']."</td>
  <td>".$notify_data_array['*Dev Comms Error']."</td>
  <td>".$notify_data_array['*Dev Throttle']."</td>
  </tr>";

  return $row;
}

/*****************************************************************************
/*  Function:    process_notify_table()
/*  Description: processes the notifications of a host for html display
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - Device details table in html
*****************************************************************************/
function process_notify_table($host_data)
{
  $i = 0;
  $table = "";

  $arr = array ('command'=>'notify','parameter'=>'');
  $response_arr = send_request_to_host($arr, $host_data);

  if ($response_arr != null)
  {
    while (isset($response_arr['NOTIFY'][$i]))
    {
      $table .= process_notify_disp($response_arr['NOTIFY'][$i]);
      $i++;
    }
  }
  return $table;
}

/*****************************************************************************
/*  Function:    create_devdetails_header()
/*  Description: Creates the header bar for device information
/*  Inputs:      none
/*  Outputs:     return - pool header in html
*****************************************************************************/
function create_devdetails_header()
{
  $header =
    "<thead>
    <tr>
      <th scope='col' class='rounded-company'>Device</th>
      <th scope='col' class='rounded-q1'>Driver</th>
      <th scope='col' class='rounded-q1'>Kernel</th>
      <th scope='col' class='rounded-q1'>Model</th>
      <th scope='col' class='rounded-q1'>Dev Path</th>
    </tr>
    </thead>";

  return $header;
}

/*****************************************************************************
/*  Function:    process_devdetails_disp()
/*  Description: processes a single item of the device details array of a host 
/*               for html display
/*  Inputs:      dev_data_array - the device detail array data.
/*  Outputs:     return - the row in html
*****************************************************************************/
function process_devdetails_disp($dev_data_array)
{
  $button = '';
  
  if ($dev_data_array['Name'] == 'BFL')      
  	$button = " &nbsp;<button type='submit' class='button' name='flashpga' value='".$dev_data_array['ID']."'>Blink</button>";
  
  $row = "<tr>
  <td>".$dev_data_array['Name'] . $dev_data_array['ID'] . $button ."</td>
  <td>".$dev_data_array['Driver']."</td>
  <td>".$dev_data_array['Kernel']."</td>
  <td>".$dev_data_array['Model'] ."</td>
  <td>".$dev_data_array['Device Path']."</td>
  </tr>";

  return $row;
}

/*****************************************************************************
/*  Function:    process_devdetails_table()
/*  Description: processes the device details of a host for html display
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - Device details table in html
*****************************************************************************/
function process_devdetails_table($host_data)
{
  $i = 0;
  $table = "";

  $arr = array ('command'=>'devdetails','parameter'=>'');
  $response_arr = send_request_to_host($arr, $host_data);

  if ($response_arr != null)
  {
    while (isset($response_arr['DEVDETAILS'][$i]))
    {
      $table .= process_devdetails_disp($response_arr['DEVDETAILS'][$i]);
      $i++;
    }
  }
  return $table;
}
/*****************************************************************************
/*  Function:    create_stats_header()
/*  Description: Creates the header bar for stats information
/*  Inputs:      none
/*  Outputs:     return - pool header in html
*****************************************************************************/
function create_stats_header()
{
  $header =
    "<tr>
      <th scope='col'>Raw Stats Table</th>
    </tr>";

  return $header;
}
/*****************************************************************************
/*  Function:    process_stats_disp()
/*  Description: processes a single item of the stats array of a host
/*               for html display
/*  Inputs:      stats_data_array - the device detail array data.
/*  Outputs:     return - the row in html
*****************************************************************************/
function process_stats_disp($stats_data_array)
{
  $row = "<tr>";
  
  while (list($key, $val) = each($stats_data_array))
  {
    if ($key != 'STATS')
    {
      if ($key == 'Elapsed')
      {
        $days = floor($val / 86400);
        $val -= $days * 86400;
        $hours = floor($val / 3600);
        $val -= $hours * 3600;
        $mins = floor($val / 60);
        $seconds = $val - ($mins * 60);
        
        $val = $days."d ".$hours."h ".$mins."m ".$seconds."s";
      }

      $row .= "<td>" . $key. ": " . $val . "</td>";
    }
  }
  $row .= "</tr>";

  return $row;
}

/*****************************************************************************
/*  Function:    process_stats_table()
/*  Description: processes the stats of a host for html display
/*  Inputs:      host_data - the host data array.
/*  Outputs:     return - Device details table in html
*****************************************************************************/
function process_stats_table($host_data)
{
  $i = 0;
  $table = "";

  $arr = array ('command'=>'stats','parameter'=>'');
  $response_arr = send_request_to_host($arr, $host_data);

  if ($response_arr != null)
  {
    while (isset($response_arr['STATS'][$i]))
    {
      $table .= process_stats_disp($response_arr['STATS'][$i]);
      $i++;
    }
  }
  return $table;
}

/*****************************************************************************
/*  Function:    process_debug_info()
/*  Description: processes the debug level
/*  Outputs:     return - the table of info
*****************************************************************************/
function process_debug_info($host_data)
{
    global $debug_param_arr;
	
  	$arr = array ('command'=>'debug','parameter'=>'');
  	$debug_arr = send_request_to_host($arr, $host_data);

  	$output = '<tr>';
  	foreach ($debug_param_arr as $param)
  		$output .= "<th> $param </th>"; 			
  	$output .= '</tr></tr>';
  	 
  	foreach ($debug_param_arr as $param)
  	{
  		if ($debug_arr['DEBUG']['0'][$param])
  			$checked = 'checked';
  		else
  			$checked = '';
  				
  		$output .= "<td><input type='checkbox' value=$param name=$param $checked ></td>";			
  	}
  	
  	$output .= "<tr><th colspan=7>Select debug level and click submit, or click default to reset to defaults &nbsp; 
  	            <button type='submit' name='debug_submit' value='debug'>Submit</button>
  	            <button type='submit' name='default_submit' value='debug'>Default</button>
  	            </th><tr>";
  	 
  return $output;
}

?>


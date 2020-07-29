<?php


/////////////////////////////////////////// Includes //////////////////////////////////////////////

ini_set("soap.wsdl_cache_enabled", "0");  // clean WSDL for develop
//ini_set("allow_url_fopen", true);
date_default_timezone_set('America/Los_Angeles');
require_once realpath(__DIR__ . '/../commonDirLocation.php');
require_once realpath(__DIR__ . '/../EnigmaCallAPI.php');
require_once realpath(COMMON_PHP_DIR . '/SlackMessagePost.php');
require_once realpath(COMMON_PHP_DIR . '/checkOrgID.php');
require_once realpath(COMMON_PHP_DIR . '/respond.php');
require_once realpath(COMMON_PHP_DIR . '/parseNotification.php');
require_once realpath(COMMON_PHP_DIR . '/deleteOldLogs.php');
require_once realpath(COMMON_PHP_DIR . '/checkWait.php');
require_once realpath(COMMON_PHP_DIR . '/writelog.php');
require_once realpath(COMMON_PHP_DIR . '/logTime.php');
require_once realpath(COMMON_PHP_DIR . '/baton.php');

ini_set('soap.wsdl_cache_enabled',0); //this causes php to look at the wsdl every time and not cache it. If it is cached, then any edits to the wsdl will not be reflected unless this command is enabled.
date_default_timezone_set('America/Los_Angeles');
$f_name = pathinfo(__FILE__)['basename'];
$f_dir = pathinfo(__FILE__)['dirname'];
$log_dir = '/log/';
$rel_path = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT']));
$keep_logs_days_old = 90;
$sf_url = 'https://na131.salesforce.com';
$heavy_logging = 0;
///////////////////////////////////////////////  FUNCTIONS  ///////////////////////////////////////////



function find_node_by_name($node_name = '') {
/*
input name and ip address
query goes out to enigma's API
returns true if result; false if no result
*/
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_namea+LIKE+\'%' . $node_name . '%\'';
                                                                                                          writelog("\n$url\n");
  if (!empty($node_name)) {
    $callResult = CallAPI($url);
//    writelog($callResult);
    if ((preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $callResult))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
      writelog("\nFound node\n");
      return $callResult;
    } else {
      writelog("\nDidn't find node\n");
      return 0;
    }
  } else {
    writelog("\n\nEMPTY\n\n");
    return 0;
  }
}



function add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $acct_id, $down_event_alarm_delay ) {
/*
inputs: 
Source node IP ( device already in Enigma)
Source node name (the same device already in Enigma)
New node name
New node IP
New node description 
Site code (such as mtbr or atwr)
Account ID

This function assumes the device was not found using find_node()
*/


//broke the URL up into strings for code readability
$urlS1 = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=add_node';
$urlS2 = '&source_node_ip=' . $src_node_ip;
$urlS3 = '&source_node_name=' . $src_node_name;
$urlS4 = '&new_node_ip=' . $new_node_ip;
$urlS5 = '&new_node_name=' . $new_node_name;
$urlS6 = '&new_node_dsc=' . $new_node_desc;
$urlS7 = '&new_node_site_code=' . $site_code;
$urlS8 = '&hst_name_inherited_from_sysname_flag=N';
$urlS9 = '&hst_connection_comment=' . $acct_id;
$urlS10 = '&hst_page_delay_tst=' . $down_event_alarm_delay;
$url = $urlS1.$urlS2.$urlS3.$urlS4.$urlS5.$urlS6.$urlS7.$urlS8.$urlS9.$urlS10;
										writelog("\n\n$url\n");
  if (!((empty($src_node_ip) AND empty($src_node_name)) OR empty($new_node_ip) OR empty($new_node_name) OR empty($new_node_desc) OR empty($site_code))) {
    $callResult = CallAPI($url);
    return($callResult);
  } else {
    writelog("\n\nERROR - One or more required parameters was not found. Here are the parameters and values: ");
    writelog("\n$urlS2\n$urlS3\n$urlS4\n$urlS5\n$urlS6\n$urlS7\n$urlS9\n$urlS10");
    return 0;
  }
}   //end function add_node



function modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $acct_id)
{
/*
inputs:
Source node IP ( device already in Enigma)
Source node name (the same device already in Enigma)
New node name
New node IP
New node description
Site code (such as mtbr or atwr)
Site Name (such as Ave 12 Silo)
This function assumes the device was found using list_node()
*/



//broke the URL up into strings for code readability
  $urlS1 = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=modify_node';
  $urlS2 = '&source_node_ip=' . $src_node_ip;
  $urlS3 = '&source_node_name=' . $src_node_name;
  $urlS4 = '&new_node_ip=' . $new_node_ip;
  $urlS5 = '&new_node_name=' . $new_node_name;
  $urlS6 = '&new_node_dsc=' . $new_node_desc;
  $urlS7 = '&new_node_site_code=' . $site_code;
  
  $urlS8 = '&hst_name_inherited_from_sysname_flag=N';
  $urlS9 = '&hst_connection_comment=' . $acct_id;
//  $urlS8 = '&site_name=' . $site_name;

  $url = $urlS1.$urlS2.$urlS3.$urlS4.$urlS5.$urlS6.$urlS7.$urlS8.$urlS9;
                                                                                writelog("\n\n$url\n");
  if (!((empty($src_node_name)) OR (empty($new_node_ip)) OR (empty($new_node_desc)) OR (empty($site_code)))) {
    $callResult = CallAPI($url);
//										writelog("\n\nMODIFY NODE CALLRESULT: $callResult\n");
    if (!(preg_match('/modified":"OK"/', $callResult))) {
    return($callResult);
    } else {
      return 1;
    }
  } else {
    writelog("\n\nERROR - One or more required parameters was not found. Here are the parameters and values: ");
    writelog("\n$urlS2\n$urlS3\n$urlS4\n$urlS5\n$urlS6\n$urlS7\n$urlS8\n$urlS9");
    return 0;
  }
}  //end function modify_node



function del_node($del_node_name, $opp_name='unknown')
{
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=delete_node&source_node_name=' . $del_node_name . '&delete_forever=Y&deleted_hst_ref_num=Y';
  writelog("\nDeleting $del_node_name using this url:\n$url\n");
  if (!(empty($del_node_name))) {
    $callResult = CallAPI($url);
    if ((preg_match('/"Result":"OK"/', $callResult))) {
      writelog("\nDeletion of $del_node_name successful. Opportunity: $opp_name\n");
      return 1;
    } else {
      return 0;
    }
  }
}





/////////////////////////////// START EXECUTION CODE ///////////////////////////////////////////
deleteOldLogs($f_dir . $log_dir, $keep_logs_days_old);

writelog("\n\n=================================================================================\n");
log_time();

ob_start();


$busy = check_busy();
$times = check_times();
if ($busy) {
  if($times <= 2) {
    $times++;
    set_times($times);
    respond('false');
    exit;
  } else {
    set_busy(0);
    set_times(0);
  }
} else {
  ob_clean();
  respond('true');
  header('Connection: close');
  header('Content-Length: '.ob_get_length());
  ob_end_flush();
  ob_flush();
  flush();
  set_busy(1);
  
}



$req = file_get_contents('php://input');
if (empty($req)) {
  writelog("\n\nRequest is empty. Responding true and exiting...");
  respond('true');
  exit;
}
//											writelog("\n\nREQ:\n\n");
//											writelog($req);
$xml = new DOMDocument();
$xml->loadXML($req);
$requestArray = parseNotification($xml);



/*
//Test Array:
$requestArray = array(
  'OrganizationId' => '000lkjhlkajshldkfalgiu',
  'MapsRecords' => array(
    0 => array(
        'AccountId' => '0015B00000UcmcY',
        'Id' => '000kjh00000000023423',
        'Name' => 'A-S01040799',
        'SU_IP_Address__c' => '123.123.123.123',
        'AP_Standard_Name__c' => 'flbk-ap-test',
        'Key_Account__c' => 'True'
    ),
/*
    1 => array(
        'MIR_Down_Mbps__c' => 10,
        'Client_IP_s__c' => '192.168.44.44',
        'MO_IP_delete_flag__c' => 'false'
    ),

    2 => array(
        'MIR_Down_Mbps__c' => 10,
        'Client_IP_s__c' => '192.168.33.36',
        'MO_IP_delete_flag__c' => 'false'
    )

  ),
  'sObject' => '0'
);
*/
unset($requestArray['sObject']);
										heavylog( "\nXML_requestArray:\n\n");
										heavylog($requestArray);
$arr_size=count($requestArray['MapsRecords']);
										heavylog("\nNUMBER OF NOTIFICATIONS IN MESSAGE: $arr_size");
$org_id = $requestArray['OrganizationId'];
if (!checkOrgID($org_id)) {
  $msg = "$rel_path: ID check failed: $org_id";
                                                                                writelog("\n$msg\n");
                                                                                slack($msg, 'mattd');
                                                                                respond('true');
  exit;  
}

//  $arrOfRecordArrays = array();
try {
  $success_ct = 0;
  for($i=0;$i<$arr_size;$i++) {
    $SF_acct_id = $requestArray['MapsRecords'][$i]['AccountId'];
    $SF_opp_id = $requestArray['MapsRecords'][$i]['Id'];
    $SF_opp_name = $requestArray['MapsRecords'][$i]['Name'];
    if (!empty($requestArray['MapsRecords'][$i]['SU_IP_Address__c'])) {
      $SF_su_ip = $requestArray['MapsRecords'][$i]['SU_IP_Address__c'];
    } else {
      $SF_su_ip = '';
    }
    $SF_ap_name = $requestArray['MapsRecords'][$i]['AP_Standard_Name__c'];
    $SF_site_code = substr($SF_ap_name, 0, 4); //get first 4 characters of the AP name ($ap)
    if (isset($requestArray['MapsRecords'][$i]['Key_Account__c'])) {
      $SF_key_account = $requestArray['MapsRecords'][$i]['Key_Account__c'];
    } else {
      $SF_key_account = 'False';
    } 
    $msg = "SF Opportunity Name: $SF_opp_name\nSF SU IP: $SF_su_ip\nSF SU Site Code: $SF_site_code\nSF AP Name: $SF_ap_name\nSF Key Account: $SF_key_account";
                                                                                writelog("\n$msg\n");
    if ($SF_key_account === 'True') {
      $src_node_ip = '10.99.99.11';
      $src_node_name = '0_key_account_template';
      $down_event_alarm_delay = 240;
    } else {
      $src_node_ip = '10.99.99.10';
      $src_node_name = '0_su_template';
      $down_event_alarm_delay = 0;
    }
                                                                                writelog("\nSource Node Name: $src_node_name");
                                                                                writelog("\nSource Node IP: $src_node_ip");
    $new_node_desc = $SF_ap_name;
    $new_node_ip = $SF_su_ip;
    $new_node_name = $SF_opp_name;
    if (preg_match('/-0$/', $new_node_desc)) {
      $new_node_desc = $new_node_desc . '00';
    }
    if (preg_match('/-[0-9]{2}$/', $new_node_desc, $m)) {
      $new_node_desc = rtrim($new_node_desc, "0..9") . '0' . ltrim($m[0], "-");
    }
    $site_code = $SF_site_code;

    $find_node_result = find_node_by_name($SF_opp_name); //check if node is in Enigma and return the result if it is.
//                                                                                        writelog($find_node_result); 
    if (preg_match('/namea":"([^"]+)"/', $find_node_result, $m)) { //Get the Enigma SU name
      $ENIG_su_name_str = $m[1];
    
      if (preg_match('/site_code":"([^"]+)"/', $find_node_result, $m)) { //Get the Enigma SU site code
        $ENIG_su_site_code_str = $m[1];
      } else {
        $ENIG_su_site_code_str = '';
      }
      if (preg_match('/hst_dsc":"([^"]+)"/', $find_node_result, $m)) { // Get the Enigma SU description (AP)
        $ENIG_su_dsc_str = $m[1];
      }
      if (preg_match('/cst_code":"([^"]+)"/', $find_node_result, $m)) { // Get the Enigma SU SLA
        $ENIG_su_cst_code = $m[1];
      }
      
  
      preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $find_node_result, $match);  //Get the Enigma SU IP
      $ENIG_ip = $match[0];
      $msg = "Enigma SU Name: $ENIG_su_name_str\nEnigma SU IP: $ENIG_ip\nEnigma SU Site Code: $ENIG_su_site_code_str\nEnigma SU Description: $ENIG_su_dsc_str";
                                                                                writelog("\n$msg\n");
  
      if (empty($SF_su_ip)) { // If the SU IP from SF is empty, check to see if the Enigma node was found earlier. 
                              //If not, success. If so, delete Enigma node. Do a followup check to make sure it has been removed. If so, success.
//                                                                                         writelog("\nSF_su_ip is empty\n");
        if (!($find_node_result)) {
          $success_ct++;
        } else {
          $del_node_result = del_node($ENIG_su_name_str, $SF_opp_name);
          if ($del_node_result == 1) {
            $find_node_result2 = find_node_by_name($SF_opp_name);
            if ($find_node_result2 == 0) {
              $success_ct++;
            } else {
              $msg = "$rel_path: $sf_url/$SF_opp_id - ERROR - $SF_opp_name - del_node failed. Node still found.";
                                                                                writelog("\n$msg\n");

            }
          } else {
              $msg = "$rel_path: $sf_url/$SF_opp_id - ERROR - $SF_opp_name - del_node function failed.";
                                                                                writelog("\n$msg\n");
          }
        }
      } elseif (!(empty($SF_su_ip))) { // If the SU IP from SF is not empty, make the checks below and determine whether to add or modify
  
        if ((isset($SF_key_account)) and ((($SF_key_account === 'False') and ($ENIG_su_cst_code === 'KEY')) or (($SF_key_account === 'True') and ($ENIG_su_cst_code !== 'KEY'))))  { 
          $del_node_result = del_node($ENIG_su_name_str, $SF_opp_name);
          $new_node_ip = $SF_su_ip;
          $new_node_name = $ENIG_su_name_str;
          $new_node_desc = $SF_ap_name;
          $site_code = $SF_site_code;
          $add_node_result = add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_acct_id, $down_event_alarm_delay );
          if (preg_match('/OK/', $add_node_result)) {
            $success_ct++;
          }
            //down_event_alarm_delay is defined above, toward the beginning of this big for loop
        }

          //Check to see if modification is needed.
        $test_arrs = array (
          array(
            "Element" => 'IP',
            "SF" => $SF_su_ip,
            "ENIG" => $ENIG_ip
          ),
          array(
            "Element" => 'SiteCode',
            "SF" => $SF_site_code,
            "ENIG" => $ENIG_su_site_code_str
          ),
          array(
            "Element" => "Description",
            "SF" => $SF_ap_name,
            "ENIG" => $ENIG_su_dsc_str
          )
        );
        $modify_flag = false;
        foreach ($test_arrs as $arr) {
          $a = $arr['Element'];
          $b = $arr['SF'];
          $c = $arr['ENIG'];
        
          if ($b != $c) {
            $modify_flag = true;
                                                                                writelog("\nModifying $a: $c to $b" );
          }
        }
                                                                                writelog("\n");
            
        if ($modify_flag) {
          $src_node_ip = $ENIG_ip; 
          $src_node_name = $ENIG_su_name_str;
          $new_node_name = $ENIG_su_name_str;
          $modify_node_result = modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_acct_id);
//                                                                              writelog("\n\nMODIFY NODE RESULT: $modify_node_result\n\n");
          if ($modify_node_result) {
            $success_ct++;
          }
        } else {
            $success_ct++; //also success because no mod needed
//                                                                              writelog("\n\nNo modification needed.");
        }
      }
    } elseif (($find_node_result == 0) and (empty($new_node_ip))) {                     // check to make sure that if the node is not found the required IP is available for adding. If not, fail.
      $msg = "$rel_path: $sf_url/$SF_opp_id - No node to modify, and no IP tp add.";
      $success_ct++;
                                                                                writelog("\n$msg");
      
    } else { // If node not found in Enigma...

      $add_node_result = add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_acct_id, $down_event_alarm_delay ); //still need Account Name instead of ID
      if (preg_match('/OK/', $add_node_result)) {
        $success_ct++;
        $msg = "Added device to Enigma: $new_node_name \nIP: $new_node_ip \nDescription: $new_node_desc \nSite: $site_code";
                                                                                writelog("\n$msg\n");
      } elseif (preg_match('/already present/', $add_node_result)) {
        $msg = "$rel_path: $sf_url/$SF_opp_id - $new_node_name was not added. $new_node_ip is already in Enigma. Most likely an SU with the same IP was not removed in SF.";
                                                                                writelog("\n$msg\n");
                                                                                slack($msg, 'mattd');
        continue;                                                               
      } else { 
        $msg = "$rel_path: $sf_url/$SF_opp_id - add_node - REST API call returned: $add_node_result";
                                                                                writelog("\n$msg\n");
                                                                                slack($msg, 'mattd');
        continue;
      }
    } // end of if/else for checking if device is in Enigma
  } //end of for loop
} catch (exception $e) {
                                                                                writelog("\nEXCEPTION: ");
                                                                                writelog($e);
                                                                                slack($e, 'mattd');
}
  

if ($success_ct !== $arr_size) {
  $msg = "$rel_path: At least one failure occurred.";
                                                                                slack($msg, 'mattd');
}
ob_get_clean();
respond('true');
set_busy(0);
                                                                                log_time();
?>

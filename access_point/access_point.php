<?php


/////////////////////////////////////////// Includes //////////////////////////////////////////////

ini_set("allow_url_fopen", true);
date_default_timezone_set('America/Los_Angeles');
require_once realpath(__DIR__ . '/../../commonDirLocation.php');
require_once realpath(__DIR__ . '/../../EnigmaCallAPI.php');
require_once realpath(COMMON_PHP_DIR . '/SlackMessagePost.php');
require_once realpath(COMMON_PHP_DIR . '/checkOrgID.php');
require_once realpath(COMMON_PHP_DIR . '/respond.php');
require_once realpath(COMMON_PHP_DIR . '/parseNotification.php');
require_once realpath(COMMON_PHP_DIR . '/deleteOldLogs.php');
require_once realpath(COMMON_PHP_DIR . '/checkWait.php');
require_once realpath(COMMON_PHP_DIR . '/writelog.php');
require_once realpath(COMMON_PHP_DIR . '/logTime.php');
require_once realpath(COMMON_PHP_DIR . '/creds.php');

ini_set('soap.wsdl_cache_enabled',0); //this causes php to look at the wsdl every time and not cache it. If it is cached, then any edits to the wsdl will not be reflected unless this command is enabled.
date_default_timezone_set('America/Los_Angeles');
$logging_on = 1;
$f_name = pathinfo(__FILE__)['basename'];
$f_dir = pathinfo(__FILE__)['dirname'];
$log_dir = '/log/';
$heavy_logging = 0;
$rel_path = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT']));
$keep_logs_days_old = 90;
$sf_url = 'https://na131.salesforce.com';

                                                    ////////////////////////////////////////////  FUNCTIONS  ///////////////////////////////////////////
function find_node_by_name($node_name = '') {
/*
input name
query goes out to enigma's API
returns true if result; false if no result
*/
  writelog("\nFinding by name...\n");
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_namea+LIKE+%27%25' . $node_name . '%25%27';
											heavylog("\n$url\n");
  if (!empty($node_name)) {
    $call_result = CallAPI($url);
//    writelog($callResult);
    if ((preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
											heavylog("\nFound node by name\n");
      return $call_result;
    } else {
											heavylog("\nFailed to find node by name\n");
      return 0;
    }
  } else {
											writelog("\n\nEMPTY\n\n");
    return 0;
  }
}

function find_node_by_ip($node_ip = '') {
/*
input ip address
query goes out to enigma's API
returns true if result; false if no result
*/
											heavylog("\nFinding by IP address...\n");
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_ip+=+%27' . $node_ip . '%27';
											heavylog("\n$url\n");
  if (!empty($node_ip)) {
    $call_result = CallAPI($url);
//    writelog($callResult);
    if ((!empty($call_result)) and (preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
											heavylog("\nFound node by IP address\n");
      return $call_result;
    } else {
											writelog("\nFailed to find node by IP address\n");
      return 0;
    }
  } else {
											writelog("\n\nEMPTY\n\n");
    return 0;
  }
}

function find_node_by_sf_id($sf_id = '') { 
											heavylog("\nFinding by sf_id...\n");
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_dsc+LIKE+%27%25' . $sf_id . '%25%27';
											heavylog($url);
  if (!empty($sf_id)) {
    $call_result = CallAPI($url);
  } else {
    return 0;
  }
  if ((!empty($call_result)) and (preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) {
											writelog("\nFound node by sf_id\n");
											heavylog($call_result);
    return $call_result;
  } else {
											writelog("\nFailed to find node by sf_id\n");
    return 0;
  }
   
}





function add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $sf_id, $down_event_alarm_delay ) {
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
  $urlS9 = '&hst_connection_comment=' . $sf_id;
  $urlS10 = '&hst_page_delay_tst=' . $down_event_alarm_delay;
  $url = $urlS1.$urlS2.$urlS3.$urlS4.$urlS5.$urlS6.$urlS7.$urlS8.$urlS9.$urlS10;
										writelog("\n\n$url\n");
  if (!((empty($src_node_ip) AND empty($src_node_name)) OR empty($new_node_ip) OR empty($new_node_name) OR empty($new_node_desc) OR empty($site_code))) {
    $callResult = CallAPI($url);
    return($callResult);
  } else {
    $msg = "ERROR - One or more required parameters was not found. Here are the parameters and values: ";
    $msg2 = "\n$urlS2\n$urlS3\n$urlS4\n$urlS5\n$urlS6\n$urlS7\n$urlS9\n$urlS10";
										writelog("\n\n$msg\n$msg2");
    return 0;
  }
}   //end function add_node



function modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $sf_id) {
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
  $urlS9 = '&hst_connection_comment=' . $sf_id;
//  $urlS8 = '&site_name=' . $site_name;

  $url = $urlS1.$urlS2.$urlS3.$urlS4.$urlS5.$urlS6.$urlS7.$urlS8.$urlS9;
                                                                                	heavylog("\n\n$url\n");
  if (!((empty($src_node_name)) OR (empty($new_node_ip)) OR (empty($new_node_desc)) OR (empty($site_code)))) {
    $callResult = CallAPI($url);
      $msg = "MODIFY NODE CALLRESULT: $callResult";
                                                                                	heavylog("\n\n$msg\n");
    if (!(preg_match('/modified":"OK"/', $callResult))) {
    return($callResult);
    } else {
      return 1;
    }
  } else {
    $msg = "ERROR - One or more required parameters was not found. Here are the parameters and values: ";
    $msg2 = "$urlS2\n$urlS3\n$urlS4\n$urlS5\n$urlS6\n$urlS7\n$urlS8\n$urlS9";
											writelog("\n\n$msg $msg2\n");
    return 0;
  }
}  //end function modify_node



function del_node($del_node_name)
{
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=delete_node&source_node_name=' . 
         $del_node_name . 
         '&delete_forever=Y&deleted_hst_ref_num=Y';
  $msg = "Deleting $del_node_name using this url:\n$url";
                                                                                        writelog("\n\n$msg\n");
  if (!(empty($del_node_name))) {
    $callResult = CallAPI($url);
    if ((preg_match('/"Result":"OK"/', $callResult))) {
      $msg = "Deletion of $del_node_name successful.";
                                                                                        writelog("\n\n$msg\n");
      return 1;
    } else {
      return 0;
    }
  }
}





/////////////////////////////// START EXECUTION CODE ///////////////////////////////////////////
deleteOldLogs($f_dir . $log_dir, 90);
                                                                                        log_time();

ob_start();


$req = file_get_contents('php://input');
if (empty($req)) {
$msg = "Request is empty. Responding true and exiting...";
											writelog("\n$msg\n\n");
  respond('true');
  exit;
}
                                                                                        heavylog("\n\nREQ:\n\n");
                                                                                        heavylog($req);
$xml = new DOMDocument();
$xml->loadXML($req);
$requestArray = parseNotification($xml);
                                                                                        heavylog("\n\nREQ ARRAY:\n\n");
                                                                                        heavylog($requestArray);
$success = 0;
$fail_end = 0;
$arr_size = count($requestArray['MapsRecords']);
for($i=0;$i<$arr_size;$i++) {
  $sf_id = $requestArray['MapsRecords'][$i]['Id'];
  $sf_ap_name = $requestArray['MapsRecords'][$i]['Name'];
  $sf_ap_ip = (!empty($requestArray['MapsRecords'][$i]['IP__c'])) ? $requestArray['MapsRecords'][$i]['IP__c'] : '';
/*
  if (!empty($requestArray['MapsRecords'][$i]['IP__c'])) {
    $sf_ap_ip = $requestArray['MapsRecords'][$i]['IP__c'];
  } else {
    $sf_ap_ip = '';
  }
*/
  $ap_standard_name = $requestArray['MapsRecords'][$i]['AP_Standard_Name__c'];
  //get first 4 characters of the AP name ($ap)
  $site_code = substr($ap_standard_name, 0, 4);
  $sf_ap_status = $requestArray['MapsRecords'][$i]['Status__c'];
  $msg = "sf_id: $sf_id\nSF AP IP: $sf_ap_ip\nSF Site Code: $site_code\nSF AP Name: $sf_ap_name\n";
                                                                                        writelog("\n\n$msg\n");
  $src_node_ip = '10.99.99.50';
  $src_node_name = '0_ap_template_1';
  $down_event_alarm_delay = '0';
  $modify_flag = 0;
                                                                                        writelog("\nSource Node Name: $ap_standard_name");
                                                                                        writelog("\nSource Node IP: $sf_ap_ip");
  $new_node_name = $ap_standard_name;
  if (!preg_match('/[a-z0-9]{4}-ap-\d{1,3}-\d{1,3}-(000|045|090|135|180|225|270|315|360)/',$new_node_name)) {
    $new_node_name = $new_node_name . '_FIX_NAME';
  }
  $protocol_str = ['https://','http://'];
  $new_node_ip = str_replace($protocol_str, '', $sf_ap_ip);
  $new_node_desc = "$sf_url/$sf_id";
  

  if ($find_node_result = find_node_by_sf_id($sf_id)) {
    $found_in_Enigma = 1;
    $msg = "$sf_id : $sf_ap_ip : $sf_ap_name : $ap_standard_name  was found in Enigma by ID.";
                                                                                        heavylog("\n$msg");
  } else {  
    //we look up in Enigma by name first, and if found, we don't look by ip
    if (($find_node_result = find_node_by_name($ap_standard_name)) or ($find_node_result = find_node_by_ip($sf_ap_ip))) { 
      $found_in_Enigma = 1;
        $msg = "$sf_id : $sf_ap_ip : $sf_ap_name : $ap_standard_name  was found in Enigma by AP standard name or IP address.";
                                                                                        heavylog("\n$msg");    
    } else {
      $found_in_Enigma = 0;
      $msg = "$sf_id : $sf_ap_ip : $sf_ap_name : $ap_standard_name  was not found in Enigma.";
                                                                                        heavylog("\n$msg");    
    }
  }


  if ($found_in_Enigma) {
                                                                                        heavylog("\nfind_node_result: ");    
                                                                                        heavylog($find_node_result);    
    $find_node_result_json = json_decode($find_node_result);
                                                                                        heavylog("\nfind_node_result_json: ");    
                                                                                        heavylog($find_node_result_json);    
    $enig_ap_name_str      = $find_node_result_json[0]->hst_namea;
    $enig_ap_site_code_str = $find_node_result_json[0]->site_code;
    $enig_ap_dsc_str       = $find_node_result_json[0]->hst_dsc;
    $enig_ap_cst_code      = $find_node_result_json[0]->cst_code;
    $enig_ip               = $find_node_result_json[0]->hst_ip;

    $msg = "\n\nEnigma AP Name: $enig_ap_name_str\nEnigma AP IP: $enig_ip\nEnigma AP Site Code: $enig_ap_site_code_str\nEnigma AP Description: $enig_ap_dsc_str";
                                                                                       writelog($msg);
    if (($sf_ap_status == 'Removed') or ($sf_ap_status == 'Non Opmode')) { // If the status of AP is Removed or Non Opmode, check if the Enigma node was found earlier.
                                                                           // If not, success. If so, delete Enigma node. Do a followup check to make sure it has been removed. If it has, success = 1
      if (!$found_in_Enigma) { //if not found, good! We were trying to delete it anyway.
        $success = 1;
      } else {
        $del_node_result = del_node($enig_ap_name_str);
        if ($del_node_result == 1) {
          $find_node_result2 = find_node_by_ip($sf_ap_ip);
          if ($find_node_result2 == 0) {
            $success = 1;
          } else {
            $success = 0;
            $msg = "ERROR - $enig_ap_name_str - del_node failed. Node still found.";
                                                                                        writelog("\n$msg");
          }
        } else {
          $msg = "ERROR - $enig_ap_name_str - del_node function failed.";
											writelog("\n$msg");
        }
      }
      continue;
    }
    //Check to see if modification is needed.
    $test_arrs = array (
      array(
        "Element" => 'Name',
        "SF" => $ap_standard_name,
        "ENIG" => $enig_ap_name_str
      ),
      array(
        "Element" => 'IP',
        "SF" => $sf_ap_ip,
        "ENIG" => $enig_ip
      ),
      array(
        "Element" => 'SiteCode',
        "SF" => $site_code,
        "ENIG" => $enig_ap_site_code_str
      ),
      array(
        "Element" => "Description",
        "SF" => $new_node_desc,
        "ENIG" => $enig_ap_dsc_str
      )
    );

    if (($sf_ap_status != 'Removed') and ($sf_ap_status != 'Non Opmode')) {
      foreach ($test_arrs as $arr) {
        $a = $arr['Element'];
        $b = $arr['SF'];
        $c = $arr['ENIG'];
      
        if ($b != $c) {
          $modify_flag = 1;
                                                                                        writelog("\nModifying $a: $c to $b" );
        }  
      }
      if (!$modify_flag) {
                                                                                        writelog("\nNot modifying anything." );
      }
                                                                                        writelog("\n");
          
      if ($modify_flag) {
        $src_node_ip = $enig_ip;
        //new_node_ip is already defined at start of execution code 
        $src_node_name = $enig_ap_name_str;
        $new_node_name = $ap_standard_name;
        $modify_node_result = modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $sf_id);
											heavylog("\n\nMODIFY NODE RESULT: $modify_node_result\n\n");
        if ($modify_node_result) {
          $success = 1;
        }
      } else {
        $success = 1; //also success because no mod needed
                                                                                        writelog("\n\nNo modification needed.");
      }
      continue;
    }
  }// end of if/else for checking if device is in Enigma 
  if (($sf_ap_status != 'Removed') and ($sf_ap_status != 'Non Opmode') and (!$found_in_Enigma) and (!$modify_flag)) {
    if (empty($new_node_ip)) {                     // check to make sure that if the node is not found the required IP is available for adding. If not, fail.
      $msg ="Cannot do anything. No node to modify, and cannot add without IP.";
											writelog("\n$msg");
      $fail_end = 1;
    } else { // Add to enigma if above conditions true
      $add_node_result = add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $sf_id, $down_event_alarm_delay); //still need Account Name instead of ID
      if (preg_match('/OK/', $add_node_result)) {
        $msg = "Added device to Enigma: $new_node_name \nIP: $new_node_ip \nDescription: $new_node_desc \nSite: $site_code";
											writelog("\n$msg");
        if (find_node_by_sf_id($sf_id)) {
          $success = 1;
        } else {
          $success = 0;
        }
      } else {
        $success = 0;
      }
    }
    continue;
  } else {
    $msg = "Device status is either 'Removed' or in 'Non-op mode', and it was not found in Enigma. Nothing more to do.";
											writelog("\n$msg");
    $success = 1;
    continue;
  }
   
} //end of for loop
                                                                                        writelog("\nSuccess: $success\n");



ob_get_clean();
if (($success == 1) or ($fail_end == 1)) {
  respond('true');
} else {
  respond('false');
slack('Enigma access_point: failed and will be retried', 'mattd');
}
											log_time();

?>

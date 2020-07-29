<?php


/////////////////////////////////////////// Includes //////////////////////////////////////////////

ini_set("allow_url_fopen", true);
date_default_timezone_set('America/Los_Angeles');
include realpath(__DIR__ . '/../../commonDirLocation.php');
include realpath(__DIR__ . '/../../EnigmaCallAPI.php');
include realpath(COMMON_PHP_DIR . '/SlackMessagePost.php');
include realpath(COMMON_PHP_DIR . '/checkOrgID.php');
include realpath(COMMON_PHP_DIR . '/respond.php');
include realpath(COMMON_PHP_DIR . '/parseNotification.php');///////////////////////////////////////////////  FUNCTIONS  ///////////////////////////////////////////
include realpath(COMMON_PHP_DIR . '/deleteOldLogs.php');
include realpath(COMMON_PHP_DIR . '/checkWait.php');
include realpath(COMMON_PHP_DIR . '/writelog.php');
include realpath(COMMON_PHP_DIR . '/logTime.php');

ini_set('soap.wsdl_cache_enabled',0); //this causes php to look at the wsdl every time and not cache it. If it is cached, then any edits to the wsdl will not be reflected unless this command is enabled.
date_default_timezone_set('America/Los_Angeles');
$f_name = pathinfo(__FILE__)['basename'];
$f_dir = pathinfo(__FILE__)['dirname'];
$log_dir = '/log/';
$heavy_logging = 1;

function find_node_by_name($node_name = '') {
/*
input name
query goes out to enigma's API
returns true if result; false if no result
*/
  writelog("\nFinding by name...\n");
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_namea+LIKE+%27%25' . $node_name . '%25%27';
                                                                                                          writelog("\n$url\n");
  if (!empty($node_name)) {
    $call_result = CallAPI($url);
//    writelog($callResult);
    if ((preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
      writelog("\nFound node\n");
      return $call_result;
    } else {
      writelog("\nFailed to find node by name\n");
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
  writelog("\nFinding by IP address...\n");
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_ip+=+%27' . $node_ip . '%27';
                                                                                                          writelog("\n$url\n");
  if (!empty($node_ip)) {
    $call_result = CallAPI($url);
//    writelog($callResult);
    if ((!empty($call_result)) and (preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
      writelog("\nFound node\n");
      return $call_result;
    } else {
      writelog("\nFailed to find node by ip\n");
      return 0;
    }
  } else {
    writelog("\n\nEMPTY\n\n");
    return 0;
  }
}

function find_node_by_SF_id($SF_id = '') { 
  writelog("\nFinding by SF_id...\n");
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_dsc+LIKE+%27%25' . $SF_id . '%25%27';
  writelog($url);
  if (!empty($SF_id)) {
    $call_result = CallAPI($url);
  } else {
    return 0;
  }
  if ((!empty($call_result)) and (preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) {
    writelog("\nFound by SF_id\n");
                                                                                                          heavylog($call_result);
    return $call_result;
  } else {
    writelog("\nFailed to find node by SF_id\n");
    return 0;
  }
   
}





function add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_id, $down_event_alarm_delay ) {
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
  $urlS9 = '&hst_connection_comment=' . $SF_id;
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



function modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_id) {
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
  $urlS9 = '&hst_connection_comment=' . $SF_id;
//  $urlS8 = '&site_name=' . $site_name;

  $url = $urlS1.$urlS2.$urlS3.$urlS4.$urlS5.$urlS6.$urlS7.$urlS8.$urlS9;
                                                                                writelog("\n\n$url\n");
  if (!((empty($src_node_name)) OR (empty($new_node_ip)) OR (empty($new_node_desc)) OR (empty($site_code)))) {
    $callResult = CallAPI($url);
										heavylog("\n\nMODIFY NODE CALLRESULT: $callResult\n");
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



function del_node($del_node_name)
{
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=delete_node&source_node_name=' . $del_node_name . '&delete_forever=Y&deleted_hst_ref_num=Y';
  writelog("\nDeleting $del_node_name using this url:\n$url\n");
  if (!(empty($del_node_name))) {
    $callResult = CallAPI($url);
    if ((preg_match('/"Result":"OK"/', $callResult))) {
      writelog("\nDeletion of $del_node_name successful.\n");
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
  writelog("\n\nRequest is empty. Responding true and exiting...");
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
  $SF_id = $requestArray['MapsRecords'][$i]['Id'];
  $SF_ap_name = $requestArray['MapsRecords'][$i]['Name'];
  if (!empty($requestArray['MapsRecords'][$i]['IP__c'])) {
    $SF_ap_ip = $requestArray['MapsRecords'][$i]['IP__c'];
  } else {
    $SF_ap_ip = '';
  }
  $AP_standard_name = $requestArray['MapsRecords'][$i]['AP_Standard_Name__c'];
  $site_code = substr($AP_standard_name, 0, 4); //get first 4 characters of the AP name ($ap)
  $SF_ap_status = $requestArray['MapsRecords'][$i]['Status__c'];
                                                                                        writelog("\n\nSF_id: $SF_id\nSF AP IP: $SF_ap_ip\nSF Site Code: $site_code\nSF AP Name: $SF_ap_name\n");
  $src_node_ip = '10.99.99.50';
  $src_node_name = '0_ap_template_1';
  $down_event_alarm_delay = '0';
  $modify_flag = 0;
                                                                                        writelog("\nSource Node Name: $AP_standard_name");
                                                                                        writelog("\nSource Node IP: $SF_ap_ip");
  $new_node_name = $AP_standard_name;
  if (!preg_match('/[a-z0-9]{4}-ap-\d{1,3}-\d{1,3}-(000|045|090|135|180|225|270|315|360)/',$new_node_name)) {
    $new_node_name = $new_node_name . '_FIX_NAME';
  }
  $protocol_str = ['https://','http://'];
  $new_node_ip = str_replace($protocol_str, '', $SF_ap_ip);
  $new_node_desc = 'https://na131.salesforce.com/' . $SF_id;
  

  if ($find_node_result = find_node_by_SF_id($SF_id)) {
    $found_in_Enigma = 1;
    writelog("$SF_id : $SF_ap_ip : $SF_ap_name : $AP_standard_name  was found in Enigma by ID.");
  } else {
    if (($find_node_result = find_node_by_name($AP_standard_name)) or ($find_node_result = find_node_by_ip($SF_ap_ip))) { //we look up in Enigma by name first, and if found, we don't look by ip
      $found_in_Enigma = 1;
        $msg = "$SF_id : $SF_ap_ip : $SF_ap_name : $AP_standard_name  was found in Enigma by AP standard name or IP address.";
                                                                                        heavylog("\n$msg");    
    } else {
      $found_in_Enigma = 0;
      $msg = "$SF_id : $SF_ap_ip : $SF_ap_name : $AP_standard_name  was not found in Enigma.";
                                                                                        heavylog("\n$msg");    
    }
  }


  if ($found_in_Enigma) {
                                                                                        heavylog("\nfind_node_result: ");    
                                                                                        heavylog($find_node_result);    
    $find_node_result_json = json_decode($find_node_result);
                                                                                        heavylog("\nfind_node_result_json: ");    
                                                                                        heavylog($find_node_result_json);    
    $ENIG_ap_name_str      = $find_node_result_json[0]->hst_namea;
    $ENIG_ap_site_code_str = $find_node_result_json[0]->site_code;
    $ENIG_ap_dsc_str       = $find_node_result_json[0]->hst_dsc;
    $ENIG_ap_cst_code      = $find_node_result_json[0]->cst_code;
    $ENIG_ip               = $find_node_result_json[0]->hst_ip;

    $msg = "\n\nEnigma AP Name: $ENIG_ap_name_str\nEnigma AP IP: $ENIG_ip\nEnigma AP Site Code: $ENIG_ap_site_code_str\nEnigma AP Description: $ENIG_ap_dsc_str";
                                                                                       writelog($msg);
    if (($SF_ap_status == 'Removed') or ($SF_ap_status == 'Non Opmode')) { // If the status of the AP is Removed or Non Opmode, check to see if the Enigma node was found earlier.
                                                                           // If not, success. If so, delete Enigma node. Do a followup check to make sure it has been removed. If it has, success = 1
      if (!$found_in_Enigma) { //if not found, good! We were trying to delete it anyway.
        $success = 1;
      } else {
        $del_node_result = del_node($ENIG_ap_name_str);
        if ($del_node_result == 1) {
          $find_node_result2 = find_node_by_ip($SF_ap_ip);
          if ($find_node_result2 == 0) {
            $success = 1;
          } else {
            $success = 0;
                                                                                       writelog("ERROR - $ENIG_ap_name_str - del_node failed. Node still found.");
          }
        } else {
          writelog("ERROR - $ENIG_ap_name_str - del_node function failed.");
        }
      }
    }
    //Check to see if modification is needed.
    $test_arrs = array (
      array(
        "Element" => 'Name',
        "SF" => $AP_standard_name,
        "ENIG" => $ENIG_ap_name_str
      ),
      array(
        "Element" => 'IP',
        "SF" => $SF_ap_ip,
        "ENIG" => $ENIG_ip
      ),
      array(
        "Element" => 'SiteCode',
        "SF" => $site_code,
        "ENIG" => $ENIG_ap_site_code_str
      ),
      array(
        "Element" => "Description",
        "SF" => $new_node_desc,
        "ENIG" => $ENIG_ap_dsc_str
      )
    );

    if (($SF_ap_status != 'Removed') and ($SF_ap_status != 'Non Opmode')) {
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
        $src_node_ip = $ENIG_ip;
        //new_node_ip is already defined at start of execution code 
        $src_node_name = $ENIG_ap_name_str;
        $new_node_name = $AP_standard_name;
        $modify_node_result = modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_id);
//    										writelog("\n\nMODIFY NODE RESULT: $modify_node_result\n\n");
        if ($modify_node_result) {
          $success = 1;
        }
      } else {
        $success = 1; //also success because no mod needed
                                                                                      writelog("\n\nNo modification needed.");
      }
    }
  }// end of if/else for checking if device is in Enigma 
  if (($SF_ap_status != 'Removed') and ($SF_ap_status != 'Non Opmode') and (!$found_in_Enigma) and (!$modify_flag)) {
    if (empty($new_node_ip)) {                     // check to make sure that if the node is not found the required IP is available for adding. If not, fail.
                                                                                        writelog("\nCannot do anything. No node to modify, and cannot add without IP.");
      $fail_end = 1;
    } else { // Add to enigma if above conditions true
      $add_node_result = add_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_id, $down_event_alarm_delay); //still need Account Name instead of ID
      if (preg_match('/OK/', $add_node_result)) {
        writelog("\n Added device to Enigma: $new_node_name \nIP: $new_node_ip \nDescription: $new_node_desc \nSite: $site_code");
        if (find_node_by_SF_id($SF_id)) {
          $success = 1;
        } else {
          $success = 0;
        }
      } else {
//                                                                                    writelog("\n\nThe add_node REST API call returned: $add_node_result\n\n");
        $success = 0;
      }
    }
  }
   
} //end of for loop
//											writelog("\nSUCCESS\n");

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

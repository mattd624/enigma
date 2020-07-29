<?php


/////////////////////////////////////////// Includes //////////////////////////////////////////////

ini_set("allow_url_fopen", true);
date_default_timezone_set('America/Los_Angeles');
require_once realpath(__DIR__ . '/../../commonDirLocation.php');
require_once realpath(COMMON_PHP_DIR . '/SlackMessagePost.php');
require_once realpath(COMMON_PHP_DIR . '/checkOrgID.php');
require_once realpath(COMMON_PHP_DIR . '/respond.php');
require_once realpath(COMMON_PHP_DIR . '/parseNotification.php');
require_once realpath(COMMON_PHP_DIR . '/deleteOldLogs.php');
require_once realpath(COMMON_PHP_DIR . '/checkWait.php');
require_once realpath(COMMON_PHP_DIR . '/writelog.php');
require_once realpath(COMMON_PHP_DIR . '/logTime.php');
require_once realpath(COMMON_PHP_DIR . '/creds.php');
require_once realpath(COMMON_PHP_DIR . '/enigma.php');

ini_set('soap.wsdl_cache_enabled',0); //this causes php to look at the wsdl every time and not cache it. If it is cached, then any edits to the wsdl will not be reflected unless this command is enabled.
date_default_timezone_set('America/Los_Angeles');
$logging_on = 1;
$f_name = pathinfo(__FILE__)['basename'];
$f_dir = pathinfo(__FILE__)['dirname'];
$log_dir = '/log/';
$heavy_logging = 1;
$rel_path = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT']));
$keep_logs_days_old = 90;
$sf_url  = 'https://na131.salesforce.com';
$api_url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi';

                                                 ////////////////////////////////////////////  FUNCTIONS  ///////////////////////////////////////////
/*
function get_param_names($function_name) {
    $f = new ReflectionFunction($function_name);
    $result = array();
    foreach ($f->getParameters() as $param) {
        $result[] = $param->name;   
    }
    return $result;
}
*/

function get_data($site_code = '') {
/*input site_code
query goes out to enigma API
returns true if result; false if no result
*/
											heavylog("FUNCTION " . __FUNCTION__ . " CALLED!");
  global $site_param_arr;
  global $api_url;
  global $function;
  $function = __FUNCTION__;
  $action   = '?action=get_data';
  $select   = '&select=*&from=site'; // "*" means getting all params
  $where    = '&where=site_code+LIKE+%27%25' . $site_code . '%25%27';
											heavylog("$api_url$action$select$where\n");
  if (!empty($site_code)) {
    $result = CallAPI($api_url.$action.$select.$where);
    $result_arr = json_decode(str_replace("},\n]", "}\n]", $result));
    if ((isset($result_arr[0]->site_code) and ($site_code == strtolower($result_arr[0]->site_code)))) { 
											heavylog("Found site by site code\n");
    } else {
											heavylog("Failed to find site by site code\n");
    }
  } else {
											writelog("\n\nEMPTY\n\n");
  }
  $function = '';
  return isset($result_arr) ? $result_arr : 0;
}

function get_geo_location_id($zip_code) {
/*
input: Zip code
output: Enigma Geographic Location Record ID
*/

											heavylog("FUNCTION " . __FUNCTION__ . " CALLED!");
  global $api_url;
  global $function;
  $function = __FUNCTION__;
  $action   = '?action=get_data';
  $select   = '&select=loc_id_pri&from=loc';
  $where    = '&where=loc_pcode+=+' . $zip_code;
                                                                                        heavylog("$api_url$action$select$where\n");
  if (!empty($zip_code)) {
    $call_result = CallAPI($api_url.$action.$select.$where);
    $call_result_arr = json_decode(str_replace("},\n]", "}\n]", $call_result));
    if (isset($call_result_arr[0]->loc_id_pri)) {
      $result = $call_result_arr[0]->loc_id_pri;
    } else {
                                                                                        heavylog("Failed to find Geo Location ID\n");
    }
  } else {
                                                                                        writelog("\n\nEMPTY\n\n");
  }
  $function = '';
  return isset($result) ? $result : 0;
}


function site_action($action, $sf_obj) {
/*
inputs: $action = string that corresponds to either modify_site or add_site actions in Enigma
        $sf_obj = standard class object with properties that conform to site property names in Enigma and are translated from Salesforce values
This function assumes the device was found using get_data()
*/
  
                                                                                        heavylog("FUNCTION " . __FUNCTION__ . " CALLED WITH $action ACTION!");
  global $function;
  $function = __FUNCTION__;
  global $api_url;
  $url_arr = [];
  $url_arr[] = $api_url . '?action=' . $action;
  foreach ($sf_obj as $param_name => $param_val) {
    $url_arr[] = '&' . $param_name . '=' . str_replace(" ", "+", $param_val);
  }
                                                                                        writelog("url_arr:");
                                                                                        writelog($url_arr);
  $params_missing_arr = [];
  foreach ($sf_obj as $param_name => $param_val) {
    if (empty($param_val)) $params_missing_arr[] = $param_name;
  }
                                                                                        writelog("params_missing_arr:");
                                                                                        writelog($params_missing_arr);
  if (empty($params_missing_arr)) {
    $url = implode('', $url_arr);
                                                                                        writelog("\nurl:");
                                                                                        writelog("\n$url");
    $result = CallAPI($url);
  } else {
    $msg = "ERROR - One or more required parameters was not found: ";
    $msg2 = implode(", ", $params_missing_arr);
                                                                                        writelog("\n\n$msg\n$msg2");
  }
  $function = '';
  return isset($result) ? $result : 0;
}   //end function site_action



/////////////////////////////// START EXECUTION CODE ///////////////////////////////////////////
try {
  deleteOldLogs($f_dir . $log_dir, 90);
                                                                                          log_time();
  
  ob_start(); 

  $req = file_get_contents('php://input');
  if (empty($req)) {
  $msg = "Request is empty. Responding true and exiting...";
											writelog("$msg\n\n");
    respond('true');
    exit;
  }
                                                                                        heavylog("REQ:\n\n");
                                                                                        heavylog($req);
  $xml = new DOMDocument();
  $xml->loadXML($req);
  $request_array = parseNotification($xml);
                                                                                        heavylog("request_array:\n\n");
                                                                                        heavylog($request_array);
  if ( preg_match('+(https://.*\.salesforce\.com)/+', $request_array['EnterpriseUrl'], $match)) $sf_url = $match[1];
  $success = 0;
  $fail_end = 0;
  $arr_size = count($request_array['MapsRecords']);
  $sf_obj = new stdClass();
  foreach ($request_array['MapsRecords'] as $sf_data) {
    $sf_id				= $sf_data['Id'];
    $sf_obj->site_comment		= "$sf_url/$sf_id";
    $sf_obj->site_name			= $sf_data['Name'];
    $sf_obj->site_code			= $sf_data['Site_Code__c'];
    $sf_obj->site_svc_code		= $sf_data['Site_Code__c'];
    $sf_obj->site_street_address	= $sf_data['Address__c'];
    $sf_obj->site_loc_id		= get_geo_location_id($sf_data['Zip__c']);
    $sf_obj->site_status		= $sf_data['Status__c'];
    $sf_gps_lat				= $sf_data['GPS_Coordinates__Latitude__s'];
    $sf_gps_lon				= $sf_data['GPS_Coordinates__Longitude__s'];
    $sf_obj->site_gps			= "$sf_gps_lat, $sf_gps_lon";
    $sf_obj->site_com_dt		= $sf_data['Start_Date__c'];
    $sf_obj->site_owner_cst_id		= 30;
    $sf_obj->site_cst_id		= 30;
    $sf_obj->site_gps_locked		= "Y";
    $sf_obj->site_per_id		= 36;
											heavylog("LOGGING PARAMS AND VALS");  
    $msg = '';
    foreach ($sf_obj as $param => $val ) {
      $msg = $msg . "$param:	$val\n";
    }
                                                                                        heavylog("$msg");
    $msg = "$sf_obj->site_comment : $sf_obj->site_code : $sf_obj->site_name";
    if ($find_result_arr = get_data(strtolower($sf_obj->site_code))) {
      $found_in_enigma = 1;
      $msg = "$msg was found in Enigma by site code.";
    } else {
      $found_in_enigma = 0;
      $msg = "$msg was not found in Enigma.";
    }
											heavylog("$msg");     
  											heavylog("find_result_arr: ");    
  											heavylog($find_result_arr);
    if ($found_in_enigma and ($sf_obj->site_status == 'Active')) {
    //if ( $found_in_enigma ) {

      //Check to see if modification is needed by mapping sf values to enig values
      $msg = "\n\nEnigma Values:"; 
      foreach ($find_result_arr[0] as $property => $value) {
        $msg = $msg . "\n" . "$property\t\t$value";
      }              
											heavylog("$msg");
//respond('true');
//exit;
      $modify_obj = new stdClass();
      foreach ($sf_obj as $sf_param => $sf_val) {
        $enig_val = $find_result_arr[0]->{$sf_param};
        if ($sf_val != $enig_val) {
          $modify_obj->{$sf_param} = $sf_val;
                                                                                        writelog("Modifying $sf_param: $enig_val to $sf_val");
        }
      }
                                                                                        heavylog("modify_obj:\n");
                                                                                        heavylog($modify_obj);
      if (count($modify_obj) > 0) {
        $modify_obj->site_code = $sf_obj->site_code;
        $modify_site_result = site_action('modify_site', $modify_obj);
                                                                                        heavylog("MODIFY SITE RESULT: $modify_site_result\n\n");
        if ($modify_site_result) {
          $success = 1;
        }
      } else {
        $success = 1;
                                                                                        writelog("All values seem to match. Not modifying anything." );
      }
/*
    } elseif ($sf_obj->site_status == 'Active') {
      $add_site_result = site_action('add_site', $sf_obj); 
											heavylog("add_site_result: $add_site_result\n\n");
      if (preg_match('/OK/', $add_site_result)) {
        $msg = "Added Site: $sf_obj->site_name ($sf_obj->site_code)";
											writelog("$msg");
        if (get_data(strtolower($sf_obj->site_code))) {
          $success = 1;
        } else {
          $success = 0;
          $fail_end = 1;
          $msg = "Failed to verify site $sf_obj->site_name ($sf_obj->site_code) was added";
  											writelog("$msg");
  											slack("$rel_path: $msg", 'mattd');
        }
      } else {
        $msg = "API error while adding site $sf_obj->site_name ($sf_obj->site_code)";
  											writelog("$msg");
  											slack("$rel_path: $msg", 'mattd');
        $fail_end = 1;
      }
*/    
    } else {
      $msg = "No criteria were met for adding or modifying site $sf_obj->site_name ($sf_obj->site_code)";
  											writelog("$msg");
  											slack("$rel_path: $msg", 'mattd');
      $fail_end = 1;
    } // end of if/else for $found_in_enigma 
    
  } //end of for loop
											heavylog("Success: $success\n");
} catch (Exception $e){	
  $msg = "enigma/site: Exception caught: $e->faultstring";
                                                                                        writelog("$msg");
                                                                                        slack("$rel_path: $msg", 'mattd');
}
ob_get_clean();
if (($success == 1) or ($fail_end == 1)) {

  respond('true');
} else {
  respond('false');
  $msg = "Script failed and will be retried";
											writelog("\n$msg\n");
											slack("$rel_path: $msg", 'mattd');
}
											log_time();

?>

<?php


/////////////////////////////////////////// Includes //////////////////////////////////////////////

ini_set("soap.wsdl_cache_enabled", "0");  // clean WSDL for develop
ini_set("allow_url_fopen", true);
date_default_timezone_set('America/Los_Angeles');
include realpath(__DIR__ . '/../commonDirLocation.php');
include realpath(COMMON_PHP_DIR . '/SlackMessagePost.php');
include realpath(COMMON_PHP_DIR . '/toolkit/soapclient/SforceEnterpriseClient.php');
include realpath(COMMON_PHP_DIR . '/production.userAuth.php');
include realpath(COMMON_PHP_DIR . '/deleteOldLogs.php');
include realpath(COMMON_PHP_DIR . '/writelog.php');
include realpath(COMMON_PHP_DIR . '/logTime.php');
include realpath(__DIR__ . '/../EnigmaCallAPI.php');

ini_set('soap.wsdl_cache_enabled',0); //this causes php to look at the wsdl every time and not cache it. If it is cached, then any edits to the wsdl will not be reflected unless this command is enabled.
date_default_timezone_set('America/Los_Angeles');
$f_name = pathinfo(__FILE__)['basename'];
$f_dir = pathinfo(__FILE__)['dirname'];

///////////////////////////////////////////////  FUNCTIONS  ///////////////////////////////////////////


function find_node_by_name($node_name = '') {
/*
input name
query goes out to enigma's API
returns true if result; false if no result
*/
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_namea+LIKE+%27%25' . $node_name . '%25%27';
                                                                                                          writelog("\n$url\n");
  if (!empty($node_name)) {
    $call_result = CallAPI($url);
//    writelog($callResult);
    if ((preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
      writelog("\nFound node by name\n");
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
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_ip,hst_namea,site_code,hst_dsc,cst_code&from=hst&where=hst_ip+=+%27' . $node_ip . '%27';
                                                                                                          writelog("\n$url\n");
  if (!empty($node_ip)) {
    $call_result = CallAPI($url);
//    writelog($callResult);
    if ((!empty($call_result)) and (preg_match('/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/', $call_result))) { //if there are any results, they will include an IP, but one not necessarily matching the find_node_ip, so just check if there is an IP pattern in the result
      writelog("\nFound node by IP address\n");
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
    writelog("\nFound node by SF_id\n");
//    writelog($call_result);
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
}  //end function modify node



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
log_time();

///Salesforce API stuff///
/// The code below queries Salesforce and puts the results into an array:  $record_arr

$obj = 'Access_Point__c'; // salesforce object we want to query
$get_fields = 'Id,Tower__r.Site_Code__c,Name,IP__c,General_Direction__c,Status__c'; // comma-delimited string: fields we want to query for (found in Salesforce>Setup>Create>Objects[Standard Fields|Custom Fields & Relationships])
$where_clause = '';
$query_return_limit = '2000';
$wsdl = COMMON_PHP_DIR . '/wsdl/production.enterprise.wsdl.old';
$down_event_alarm_delay = '0';


try {
  $mySforceConnection = new SforceEnterpriseClient();
  $mySoapClient = $mySforceConnection->createConnection($wsdl);
  $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);
  if (empty($where_clause)){
    $query = "SELECT $get_fields FROM $obj LIMIT $query_return_limit"; //soql query
  } else {
    $query = "SELECT $get_fields FROM $obj WHERE $where_clause LIMIT $query_return_limit"; //soql query
  }

  $options = new QueryOptions(300);  //Set query to return results in chunks
  $mySforceConnection->setQueryOptions($options);
  $done = false;
  $response = $mySforceConnection->query(($query));
  echo "Size of records:  " . $response->size."\n";
  $record_arr=array();
  if ($response->size > 0) {
    while (!$done) {
      foreach ($response->records as $record) {

//print_r("\n\n");
//print_r($record);
        if (!(empty($record->Id))) {
          $Id = $record->Id;
          $record_arr[$Id] = array();
          $record_arr[$Id]['Site_Code__c'] = $record->Tower__r->Site_Code__c;
          $record_arr[$Id]['Name'] = $record->Name;
          $record_arr[$Id]['Status__c'] = $record->Status__c;
          if (!empty($record->IP__c)) {
            $record_arr[$Id]['IP__c'] = $record->IP__c;
          } else {
            $record_arr[$Id]['IP__c'] = '';
          }
          if (!empty($record->General_Direction__c)) {
            $record_arr[$Id]['General_Direction__c'] = "$record->General_Direction__c";
          } else {
            $record_arr[$Id]['General_Direction__c'] = '';
          }
        }
      }
      if ($response->done == true) {
        $done = true;
      } else {
//      echo "***** Get Next Chunk *****\n";
        $response = $mySforceConnection->queryMore($response->queryLocator);
//                                                                                            print_r($response);
//  print_r($mySforceConnection->getLastRequest());
      }
    }
  }
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}



//conversion table

$AZ_convert = array(
'North' => '000',
'Northeast' => '045',
'East' => '090',
'Southeast' => '135',
'South' => '180',
'Southwest' => '225',
'West' => '270',
'Northwest' => '315'
);




$success = 0;


foreach ($record_arr as $SF_id => $SF_id_arr) {
  writelog("\n\n\n_______________________________________________________________________________________________________ ");
  $modify_flag = 0; //default setting
  $src_node_ip = '10.12.13.50';
  $src_node_name = '0_AP_Template_1';

  //print_r($SF_id);
  //print_r($SF_id_arr);
  $SF_ap_name = $SF_id_arr['Name'];
  $site_code = $SF_id_arr['Site_Code__c'];
  $SF_ap_ip = $SF_id_arr['IP__c'];
  $ip_split = explode(".", $SF_ap_ip);
  $ip_octet3 = $ip_split[2];
  $ip_octet4 = $ip_split[3];
  //print_r($SF_id_arr['General_Direction__c']);
  $SF_general_direction = $SF_id_arr['General_Direction__c'];
  $SF_azimuth = $AZ_convert[$SF_general_direction];
  //print_r($SF_azimuth);
  $AP_standard_name = $site_code . '-ap-' . $ip_octet3 . '-' . $ip_octet4 . '-' . $SF_azimuth ;
  $SF_ap_status = $SF_id_arr['Status__c'];
  //print_r($site_code);
                                                                                       writelog("\nSource Node Name: $AP_standard_name");
                                                                                       writelog("\nSource Node IP: $SF_ap_ip");
  $new_node_name = $AP_standard_name;
  $new_node_ip = str_replace('https://', '', $SF_ap_ip);
  $new_node_desc = 'https://na131.salesforce.com/' . $SF_id;

  if ($find_node_result = find_node_by_SF_id($SF_id)) {
    $found_in_Enigma = 1;
    writelog("$SF_id : $SF_ap_ip : $SF_ap_name : $AP_standard_name  was found in Enigma by ID.");
  } else {
    if (($find_node_result = find_node_by_name($AP_standard_name)) or ($find_node_result = find_node_by_ip($SF_ap_ip))) { //we look up in Enigma by name first, and if found, we don't look by ip
      $found_in_Enigma = 1;
      writelog("$SF_id : $SF_ap_ip : $SF_ap_name : $AP_standard_name  was found in Enigma by AP standard name or IP address.");
    } else {
      $found_in_Enigma = 0;
      writelog("$SF_id : $SF_ap_ip : $SF_ap_name : $AP_standard_name  was not found in Enigma.");
    }
  }


  if ($found_in_Enigma) {
//                                                                                        writelog($find_node_result); 
    if (preg_match('/namea":"([^"]+)"/', $find_node_result, $m)) { //Get the Enigma AP name
      $ENIG_ap_name_str = $m[1];
    
      if (preg_match('/site_code":"([^"]+)"/', $find_node_result, $m)) { //Get the Enigma AP site code
        $ENIG_ap_site_code_str = $m[1];
      } else {
        $ENIG_ap_site_code_str = '';
      }
      if (preg_match('/hst_dsc":"([^"]+)"/', $find_node_result, $m)) { // Get the Enigma AP description (AP)
        $ENIG_ap_dsc_str = $m[1];
      }
      if (preg_match('/cst_code":"([^"]+)"/', $find_node_result, $m)) { // Get the Enigma AP SLA
        $ENIG_ap_cst_code = $m[1];
      }
        
      preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $find_node_result, $m);  //Get the Enigma AP IP
      $ENIG_ip = $m[0];
      
      writelog("\n\nEnigma AP Name: $ENIG_ap_name_str\nEnigma AP IP: $ENIG_ip\nEnigma AP Site Code: $ENIG_ap_site_code_str\nEnigma AP Description: $ENIG_ap_dsc_str\n");
  
      if (($SF_ap_status == 'Removed') or ($SF_ap_status == 'Non Opmode')) { // If the status of the AP is Removed or Non Opmode, check to see if the Enigma node was found earlier. If not, success. If so, delete Enigma node. Do a followup check to make sure it has been removed. If it has, success = 1
        if (!$found_in_Enigma) { //if not found, good! We were trying to delete it anyway.
          $success = 1;
        } else {
          $del_node_result = del_node($ENIG_ap_name_str);
          if ($del_node_result == 1) {
            $find_node_result2 = find_node_by_ip($ENIG_ip);
            if ($find_node_result2 == 0) {
              $success = 1;
            } else {
              $success = 0;
                                                                                         writelog("ERROR - $SF_ap_name - del_node failed. Node still found.");
            }
          } else {
                                                                                         writelog("ERROR - $SF_ap_name - del_node function failed.");
          }
        }
        continue;
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
            
        if ($modify_flag) {
                                                                                        writelog("\n");
          $src_node_ip = $ENIG_ip;
          //new_node_ip is already defined at start of execution code 
          $src_node_name = $ENIG_ap_name_str;
          $new_node_name = $AP_standard_name;
          $modify_node_result = modify_node($src_node_ip, $src_node_name, $new_node_ip, $new_node_name, $new_node_desc, $site_code, $SF_id);
//											writelog("\n\nMODIFY NODE RESULT: $modify_node_result\n\n");
          if ($modify_node_result) {
            $success = 1;
          }
        } else {
          $success = 1; //also success because no mod needed
                                                                                        writelog("\nNo modification needed.");
          continue;
        }
      }
    }
  } // end of if/else for checking if device is in Enigma 

  if (($SF_ap_status != 'Removed') and ($SF_ap_status != 'Non Opmode') and (!$found_in_Enigma) and (!$modify_flag)) {
    if (empty($new_node_ip)) {                     // check to make sure that if the node is not found the required IP is available for adding. If not, fail.
                                                                                        writelog("\nCannot do anything. No node to modify, and cannot add without IP.");
      continue;
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
//                                                                                      writelog("\n\nThe add_node REST API call returned: $add_node_result\n\n");
        $success = 0;
      }
    }
  }
} //end of for loop
//											writelog("\nSUCCESS\n");

writelog("\nSuccess: $success\n");
log_time();



<?php

/////////////////////////////////////////// Includes //////////////////////////////////////////////

ini_set("allow_url_fopen", true);
date_default_timezone_set('America/Los_Angeles');
include realpath(__DIR__ . '/../commonDirLocation.php');
include realpath(COMMON_PHP_DIR . '/SlackMessagePost.php');
include realpath(__DIR__ . '/../EnigmaCallAPI.php');
include realpath(COMMON_PHP_DIR . '/toolkit/soapclient/SforceEnterpriseClient.php');
include realpath(COMMON_PHP_DIR . '/production.userAuth.php');

///////////////////////////////////////////////  FUNCTIONS  ///////////////////////////////////////////


function log_writeln($log)
{
    file_put_contents(__DIR__ . '/delete_log/' . @date('Y-m-d') . '.log', print_r($log, true), FILE_APPEND);
}

function log_time()
/*
..........depends on log_writeln()
*/
{
$tmstmp = date('D, \d\a\y d \o\f F, G:i:s');
log_writeln($tmstmp . "\n\n\n\n");
}



function getSFInfo($obj = '',$fields = '', $where_clause = '', $query_return_limit = 10,$USERNAME, $PASSWORD) {
/*
  $obj = 'Access_Point__c'; // salesforce object we want to query
  $fields = 'Tower__r.Site_Code__c,Name,IP__c,General_Direction__c,Status__c'; // comma-delimited string: fields we want to query for (found in Salesforce>Setup>Create>Objects[Standard Fields|Custom Fields & Relationships])
  $where_clause = "Status__c = 'Active'";
  $query_return_limit = 10;
*/
  $wsdl = COMMON_PHP_DIR . '/wsdl/production.enterprise.wsdl.xml';
  $down_event_alarm_delay = '0';
  //$fields_arr = explode(",", $fields);

  try {
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection($wsdl);
    $mylogin = $mySforceConnection->login($USERNAME, $PASSWORD);
    if (empty($where_clause)){
      $query = "SELECT Id,$fields FROM $obj LIMIT $query_return_limit"; //soql query
    } else {
      $query = "SELECT Id,$fields FROM $obj WHERE $where_clause LIMIT $query_return_limit"; //soql query
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
          if (!(empty($record->Name))) {
            $nm = $record->Name;
            $record_arr[$nm] = array();
            foreach ($record as $key_1 => $value_1) {
              if (is_object($value_1)) {
                foreach ($value_1 as $key_2 => $value_2) {
                  $record_arr[$nm][$key_2] = $value_2;
                }
              } else {
                $record_arr[$nm][$key_1] = $value_1;
              }
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
      unset ($response);
    }
  } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
  }
  if (!empty($record_arr)) {
    return $record_arr;
  }

} //end of function




function find_nodes_down_for_days($days_down='') {  //this function not used currently, but may be in the future
$days_down_in_seconds = $days_down * 86400;
  $seconds_down_thres = time() - $days_down_in_seconds;
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=get_data&select=hst_id,hst_namea,hst_ip,lo_down_tst,lo_up_tst&from=hst&where=lo_down_tst+<+%27' . $seconds_down_thres . '%27+AND+lo_up_tst+=+0+AND+hst_namea+NOT+LIKE+%27template%27&limit=2000';

  if (!empty($days_down)) {
    $call_result = CallAPI($url);
    if (preg_match('/hst_id/',$call_result)) {
      preg_match('/.*(\[.*\]).*/is',$call_result,$result);
      $split_data = json_decode($result[1]);
      return $split_data;
    } else {
      return 0;
    }
  } else {
    return 0;
  }
}


function del_node($del_node_name)
{
  $url = 'https://enig-01.unwiredbb.net/cgi-bin/protected/manage_api.cgi?action=delete_node&source_node_name=' . $del_node_name . '&delete_forever=Y&deleted_hst_ref_num=Y';
  log_writeln("\nDeleting $del_node_name using this url:\n$url");
  if (!(empty($del_node_name))) {
    $callResult = CallAPI($url);
    if ((preg_match('/"Result":"OK"/', $callResult))) {
      log_writeln("\nDeletion of $del_node_name successful.\n");
      return 1;
    } else {
      return 0;
    }
  }
}

//////////////////////////////////////////////////EXECUTION CODE/////////////////////////////////////////////////

log_writeln("\n______________________________________________________________________________________________________\n\n");
log_writeln('BEGIN: ');

$obj = 'Opportunity'; // salesforce object we want to query
$fields = 'Name'; // Comma-delimited string: fields we want to query for (found in Salesforce>Setup>Create>Objects[Standard Fields|Custom Fields & Relationships])
                  // Id is hard-coded as part of the query in the getSFInfo function/
$where_clause = "Status__c = 'Active'";
$SF_info = getSFInfo($obj,$fields, $where_clause, $query_return_limit = 20000, $USERNAME, $PASSWORD);
//print_r($SF_info);

log_time();
$days = 30;

$nodes = find_nodes_down_for_days($days);



$nodes_to_delete = [];
foreach ($nodes as $node) {
//  print_r($node);
  preg_match('/.*([Aa]-[Ss]\d{8}).*/',$node->hst_namea,$opp_name);
  if (isset($opp_name[1])) { //This works out to mean that if the node does not have A-S######## in its name, it will not be deleted.
    if (!array_key_exists($opp_name[1],$SF_info)) {
       $nodes_to_delete[] = $node->hst_namea;
    }
  }
} 

$node_count = count($nodes_to_delete);
slack('sflr-01: Beginning deletion of nodes in Enigma that have been down for more than ' . $days . ' days: Number of nodes: ' . $node_count,'mattd');


log_writeln("\nNODES TO BE DELETED:\n\n");
log_writeln($nodes_to_delete);
 
foreach ($nodes_to_delete as $n) {
  del_node($n);
}

log_writeln('END: ');
log_time();

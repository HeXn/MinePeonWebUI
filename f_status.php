<?php
/*
f_status gets values that people want to see in realtime
returns success, status data and errors
*/
header('Content-type: application/json');

function cgminer($command, $parameter='') {
  // Setup socket
  $client = stream_socket_client("tcp://127.0.0.1:4028", $errno, $errorMessage);

  // Socket failed
  if ($client === false) {
    return false;
  }
  // Socket success
  fwrite($client, json_encode(array('command'=>$command)));
  // Get response
  $response = json_decode(preg_replace('/[^[:alnum:][:punct:]]/','',stream_get_contents($client)), true);
  fclose($client);

  return $response;
}

// Miner data
//$r['summary'] = cgminer('summary', '')['SUMMARY'];
$devs=cgminer('devs');
$pools=cgminer('pools');

if($devs!==false){
  $r['status']['devs'] = $devs['DEVS'];
  if(is_null($devs['DEVS'])){
    $r['info'][] = array("type" => "danger", "text" => "cgminer detects no devices");;
  }
}
if($pools!==false){
  $r['status']['pools'] = $pools['POOLS'];
}

// Status of cgminer
$r['status']['minerUp'] = $pools!==false;
$r['status']['minerDown'] = $pools===false;

// Debug miner data
if(isset($_REQUEST['dev'])){
  $r['status']['devs'][]=array('Name'=>'Hoeba','ID'=>0,'Temperature'=>rand(20,35),'MHS5s'=>rand(00,1000000000),'MHSav'=>rand(600000,800000),'LongPoll'=>'N','Getworks'=>200,'Accepted'=>rand(70,200),'Rejected'=>rand(1,10),'HardwareErrors'=>rand(0,50),'Utility'=>1.2,'LastShareTime'=>time()-rand(0,10));
  $r['status']['devs'][]=array('Name'=>'Debug','ID'=>1,'Temperature'=>rand(20,35),'MHS5s'=>rand(0,10000000),'MHSav'=>rand(100000,120000),'LongPoll'=>'N','Getworks'=>1076,'Accepted'=>1324,'Rejected'=>1,'HardwareErrors'=>46,'Utility'=>1.2,'LastShareTime'=>time()-rand(0,40));
  $r['status']['devs'][]=array('Name'=>'Wut','ID'=>2,'Temperature'=>rand(20,35),'MHS5s'=>rand(0,100000),'MHSav'=>rand(6000,8000),'LongPoll'=>'N','Getworks'=>1076,'Accepted'=>1324,'Rejected'=>1,'HardwareErrors'=>46,'Utility'=>1.2,'LastShareTime'=>time()-rand(0,300));
  $r['status']['devs'][]=array('Name'=>'More','ID'=>3,'Temperature'=>rand(20,35),'MHS5s'=>rand(0,1000),'MHSav'=>rand(6000,8000),'LongPoll'=>'N','Getworks'=>1076,'Accepted'=>1324,'Rejected'=>1,'HardwareErrors'=>46,'Utility'=>1.2,'LastShareTime'=>time()-rand(0,300));
  $r['status']['pools'][]=array('POOL'=>5,'URL'=>'http://stratum.mining.eligius.st:3334','Status'=>'Alive','Priority'=>9,'LongPoll'=>'N','Getworks'=>10760,'Accepted'=>50430,'Rejected'=>60,'Discarded'=>21510,'Stale'=>0,'GetFailures'=>0,'RemoteFailures'=>0,'User'=>'1BveW6ZoZmx31uaXTEKJo5H9CK318feKKY','LastShareTime'=>1375501281,'Diff1Shares'=>20306,'ProxyType'=>'','Proxy'=>'','DifficultyAccepted'=>20142,'DifficultyRejected'=>24,'DifficultyStale'=>0,'LastShareDifficulty'=>4,'HasStratum'=>true,'StratumActive'=>true,'StratumURL'=>'stratum.mining.eligius.st','HasGBT'=>false,'BestShare'=>40657);
}

$devices = 0;
$MHSav = 0;
$MHS5s = 0;
$Accepted = 0;
$Rejected = 0;
$HardwareErrors = 0;
$Utility = 0;

foreach ($r['status']['devs'] as $id => $dev) {
  if ($dev['MHS5s'] > 0) {
    $devices++;
    $MHS5s = $MHS5s + $dev['MHS5s'];
    $MHSav = $MHSav + $dev['MHSav'];
    $Accepted = $Accepted + $dev['Accepted'];
    $Rejected = $Rejected + $dev['Rejected'];
    $HardwareErrors = $HardwareErrors + $dev['HardwareErrors'];
    $Utility = $Utility + $dev['Utility'];
  }
  $r['status']['devs'][$id]['TotalShares']=$dev['Accepted']+$dev['Rejected']+$dev['HardwareErrors'];
}

$r['status']['dtot']=array(
  'devices'=>$devices,
  'MHS5s'=>$MHS5s,
  'MHSav'=>$MHSav,
  'Accepted'=>$Accepted,
  'Rejected'=>$Rejected,
  'HardwareErrors'=>$HardwareErrors,
  'Utility'=>$Utility,
  'TotalShares'=>$Accepted+$Rejected+$HardwareErrors);

// Set q to 0 if not given
if(isset($_REQUEST['all'])){
  $r['status']['uptime'] = explode(' ', exec('cat /proc/uptime'));
  $r['status']['temp'] = substr(exec('/opt/vc/bin/vcgencmd measure_temp'), 5, -2);
}

$r['status']['load'] = sys_getloadavg()[0];
$r['status']['time'] = time();

echo json_encode($r);
?>
<?
function get_ip_address() {
	foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
		if (array_key_exists($key, $_SERVER) === true) {
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
					return $ip;
				}
			}
		}
	}
}

function get_ip_info() {
	$ip = get_ip_address();


	$headers=Array('Cache-Control: max-age=0','Host:api.hostip.info','User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11');

	$ch = curl_init();
	$url="http://api.hostip.info/get_json.php?ip=".$ip;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$content=curl_exec($ch);
	curl_close($ch);

	$result=json_deocode($content);
	return $result;
}
$result=json_decode($_POST['json']);
$ip_info=get_ip_info();

if ($ip_info['country']=="") $ip_info['country']=$ip_info['country_name'];

$ua=$_SERVER['HTTP_USER_AGENT'];

$log=date("Y-m-d H:i:s")."\t".$ip_info['ip']."\t".$ip_info['country']."\t".$ip_info['state/region'];
$log.="\t".$ip_info['city']."\t".$ip_info['isp']."\t".$ip_info['type'];
foreach ($result as $val) {
	$log.="\t".$val->location."\t".$val->ping."\t".$val->speed."\t".$val->speedDetails;
}
$log.="\n";
$logfile=date("Ymd").".log";
$fp=fopen("store/".$logfile,"a");
fwrite($fp,$log);
fclose($fp);

$message="Test made on ".date(DATE_RFC822)."\n\n";
$message.="From:\n";
$message.="IP: ".$ip_info['ip']."\n";
$message.="Country: ".$ip_info['country']."\n";
if ($ip_info['state/region']) $message.="State: ".$ip_info['state/region']."\n";
if ($ip_info['city']) $message.="City: ".$ip_info['city']."\n";
if ($ip_info['isp']) $message.="ISP: ".$ip_info['isp']."\n";
if ($ip_info['type']) $message.="Type: ".$ip_info['type']."\n";
$message.="\n";
$message.="Test results:\n";
foreach ($result as $val) {
	$message.=$val->location."\t".$val->ping."\t".$val->speed." (".$val->speedDetails.")\n";
}
$cfg=parse_ini_file("speed.ini",true);
$notify=$cfg['notify'];
if ($notify['email']!="") {
	$mail($notify['email'],"Spuul Speed Test",$message,"From: ".$notify['from_name']." <".$notify['from'].">");
}
?>

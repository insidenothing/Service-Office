<?
mysql_connect();
mysql_select_db('core');
function talk($to,$message){
	include_once '/thirdParty/xmpphp/XMPPHP/XMPP.php';
	$conn = new XMPPHP_XMPP('talk.google.com', 5222, 'talkabout.files@gmail.com', '', 'xmpphp', 'gmail.com', $printlog=false, $loglevel=XMPPHP_Log::LEVEL_INFO);
	try {
		$conn->useEncryption(true);
		$conn->connect();
		$conn->processUntil('session_start');
		//$conn->presence("Ya, I'm online","available","talk.google.com");
		$conn->message($to, $message);
		$conn->disconnect();
	} catch(XMPPHP_Exception $e) {
		die($e->getMessage());
	}
}

function ev_timeline($id,$note){
	mysql_select_db ('core');
	hardLog("$note for eviction packet $id",'user');
	//talk('insidenothing@gmail.com',"$note for eviction packet $id");

	$q1 = "SELECT timeline FROM evictionPackets WHERE eviction_id = '$id'";		
	$r1 = @mysql_query ($q1) or die("Query: $q1<br>".mysql_error());
	$d1 = mysql_fetch_array($r1, MYSQL_ASSOC);
	$access=date('m/d/y g:i A');
	if ($d1[timeline] != ''){
		$notes = $d1[timeline]."<br>$access: ".$note;
	}else{
		$notes = $access.': '.$note;
	}
	$notes = addslashes($notes);
	$q1 = "UPDATE evictionPackets set timeline='$notes' WHERE eviction_id = '$id'";		
	$r1 = @mysql_query ($q1) or die(mysql_error());
}
function hardLog($str,$type){
	if ($type == "user"){
		$log = "/logs/user.log";
	}
	if ($type == "contractor"){
		$log = "/logs/contractor.log";
	}
	if ($type == "debug"){
		$log = "/logs/debug.log";
	}
	if ($log){
		error_log(date('h:iA n/j/y')." ".$_COOKIE[psdata][name]." ".$_SERVER["REMOTE_ADDR"]." ".trim($str)."\n", 3, $log);
	}
}
//looking for $_GET[packet], $_GET[entry], $_GET[newDate], and $_GET[oldDate]
$q="SELECT eviction_id, client_file FROM evictionPackets WHERE eviction_id='".$_GET[packet]."'";
$r=@mysql_query($q) or die ("Query: $q<br>".mysql_error());
$d=mysql_fetch_array($r,MYSQL_ASSOC);
	//update packet
	@mysql_query("UPDATE evictionPackets SET estFileDate='".$_GET[newDate]."' WHERE eviction_id='".$_GET[packet]."'");
	//generate email
	$entry=strtoupper($_GET[entry]);
	$to = "Service Updates <mdwestserve@gmail.com>";
	$subject = "EstFileDate Updated for EV$d[eviction_id] ($d[client_file]), From $_GET[oldDate] To $_GET[newDate]: $entry";
	$headers  = "MIME-Version: 1.0 \n";
	$headers .= "Content-type: text/html; charset=iso-8859-1 \n";
	$headers .= "From: ".$_COOKIE[psdata][name]." <".$_COOKIE[psdata][email]."> \n";
	$body="Service for Eviction $d[eviction_id] (<strong>$d[client_file]</strong>) has been modified by ".$_COOKIE[psdata][name].", Estimated File Date was changed From $_GET[oldDate] To $_GET[newDate].";
	$body .= "<br>REASON: $entry";
	$body .= "<br><br>(410) 828-4568<br>service@mdwestserve.com<br>MDWestServe, Inc.";
	$headers .= "Cc: Service Updates <service@mdwestserve.com> \n";
	mail($to,$subject,$body,$headers);
	//make timeline entry
	ev_timeline($_GET[packet],$_COOKIE[psdata][name]." Updated Est. Close from $_GET[oldDate] to $_GET[newDate]: $entry");
echo "<script>window.location='order.php?packet=$_GET[packet]';</script>";
?>
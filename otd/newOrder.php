<?
include 'common.php';
?>
<script>
function automation() {
  window.opener.location.href = window.opener.location.href;
  //window.open('write_update.php','update','width=1,height=1,toolbar=no,location=no')
  if (window.opener.progressWindow)
		
 {
    window.opener.progressWindow.close()
  }
  window.close();
}
</script>
<?

function byteConvert(&$bytes){
        $b = (int)$bytes;
        $s = array('B', 'kB', 'MB', 'GB', 'TB');
        if($b < 0){
            return "0 ".$s[0];
        }
        $con = 1024;
        $e = (int)(log($b,$con));
        return '<b>'.number_format($b/pow($con,$e),0,',','.').' '.$s[$e].'</b>'; 
}

function testLink($file){ //http://mdwestserve.com/portal/PS_PACKETS/October 06 2008 09:42:08.-08-128315F.pdf/08-128315F.pdf
	$file = str_replace('http://mdwestserve.com/portal/PS_PACKETS/','/data/service/orders/',$file);
	$file = str_replace('http://mdwestserve.com/PS_PACKETS/','/data/service/orders/',$file);
	if(file_exists($file)){
		$size = filesize($file);
		return byteConvert($size);
	}else{
		return "otd not found";
	}
}




$totalr=@mysql_query("SELECT packet_id FROM ps_packets order by packet_id DESC");
$totald=mysql_fetch_array($totalr, MYSQL_ASSOC);
$lastID = $totald[packet_id];



// rescan commands
function rescanStatus($a,$b,$p){
if ($a && $b){ return 'REQUEST BY '.id2name($a).' SCANNED BY '.id2name($b); }
if ($a && !$b){ return 'RESCAN REQUESTED BY '.id2name($a); }
if (!$a){ return 'ORIGINAL SCANS'; }
}




$rTest=@mysql_query("select * from rescanRequests where packetID = '$_GET[packet]'");
$dTest=mysql_fetch_array($rTest,MYSQL_ASSOC);

if ($_GET[rescan]){
	if(!$dTest[byID]){
		hardLog('requested rescan '.$_GET[packet],'user');
		//mail('patrick@mdwestserve.com',$_COOKIE[psdata][name].' requested rescan '.$_GET[packet],$_COOKIE[psdata][name].' requested rescan of packet '.$_GET[packet]);
		@mysql_query("INSERT INTO rescanRequests (packetID,byID) values ('$_GET[packet]','".$_COOKIE[psdata][user_id]."') ");
		echo "<script>automation();</script>";
	}else{
		hardLog('approved rescan '.$_GET[packet],'user');
		//mail('patrick@mdwestserve.com',$_COOKIE[psdata][name].' approved rescan '.$_GET[packet],$_COOKIE[psdata][name].' approved rescan of packet '.$_GET[packet]);
		@mysql_query("UPDATE rescanRequests set rescanID = '".$_COOKIE[psdata][user_id]."', rescanDate = NOW() where packetID = '$_GET[packet]'");
		echo "<script>automation();</script>";
	}
}
$rTest=@mysql_query("select * from rescanRequests where packetID = '$_GET[packet]'");
$dTest=mysql_fetch_array($rTest,MYSQL_ASSOC);
$rescanStatus = rescanStatus($dTest[byID],$dTest[rescanID],$_GET[packet]);



// end rescan commands

// export commands



function exportStatus($a,$b,$p){
if ($a && $b){ return 'REQUEST BY '.id2name($a).' APPROVED BY '.id2name($b); }
if ($a && !$b){ 
	$r=@mysql_query("SELECT * FROM ps_history WHERE packet_id='$p'");
	$d=mysql_num_rows($r);
	if ($d){ echo "<script>alert('!! Export Warning !! This packet has $d history items.');</script>"; }
	return 'EXPORT APPROVAL REQUESTED BY '.id2name($a); }
if (!$a){ return 'ACTIVE DATABASE'; }
}
$rTest=@mysql_query("select * from exportRequests where packetID = '$_GET[packet]'");
$dTest=mysql_fetch_array($rTest,MYSQL_ASSOC);
if ($_GET[export]){
	if(!$dTest[byID]){
		hardLog('requested export '.$_GET[packet],'user');
		//mail('patrick@mdwestserve.com',$_COOKIE[psdata][name].' requested export '.$_GET[packet],$_COOKIE[psdata][name].' requested export of packet '.$_GET[packet]);
		@mysql_query("INSERT INTO exportRequests (packetID,byID) values ('$_GET[packet]','".$_COOKIE[psdata][user_id]."') ");
		echo "<script>automation();</script>";
	}elseif($dTest[byID] != $_COOKIE[psdata][user_id]){
		hardLog('approved export '.$_GET[packet],'user');
		//mail('patrick@mdwestserve.com',$_COOKIE[psdata][name].' approved '.$_GET[packet],$_COOKIE[psdata][name].' approved export of packet '.$_GET[packet]);
		@mysql_query("UPDATE exportRequests set confirmID = '".$_COOKIE[psdata][user_id]."' where packetID = '$_GET[packet]'");
		echo "<script>automation();</script>";
	}else{	
		echo "<script>alert('You cannot approve exports you requested silly goose!');</script>";
	}
}
$rTest=@mysql_query("select * from exportRequests where packetID = '$_GET[packet]'");
$dTest=mysql_fetch_array($rTest,MYSQL_ASSOC);
$exportStatus = exportStatus($dTest[byID],$dTest[confirmID],$_GET[packet]);


// end export commands


function dupCheck($string){
	$r=@mysql_query("select * from ps_packets where client_file LIKE '%$string%'");
	$c=mysql_num_rows($r);
	if ($c == 1){
		$return="class='single'";
		//$return[1]=$c;
	}else{
		$return="class='duplicate'";
		//$return[1]=$c;
	}
	return $return;
}
function dupList($string,$packet){
	if ($string){
		$r=@mysql_query("select * from ps_packets where client_file LIKE '%$string%' and packet_id <> '$packet'");
		$data="<div style='font-size:12px; background-color:#FF0000; border:solid 1px #ffff00;'>Possible Duplicates:";
		while ($d=mysql_fetch_array($r,MYSQL_ASSOC)){
			$data .= " <a href='order.php?packet=$d[packet_id]' target='_blank'>[$d[packet_id]]</a>";
		}
		$data .= "</div>";
	}else{
		$data="<div style='font-size:12px; background-color:#FF0000; border:solid 1px #ffff00;'>Unable to Determine Possible Duplicates</div>";
	}
	return $data;
}
function stripHours($date){
$hours = explode(':',$date);
return $hours[0];
}

function colorCode($hours,$status){
if ($status == "CANCELLED" || $status == "FILED WITH COURT" || $status == "FILED WITH COURT - FBS"){
return "00FF00";
}else{
if ($hours <= 250){ return "00FF00"; }
if ($hours > 250 && $hours <= 300){ return "ffFF00"; }
if ($hours > 300){ return "ff0000"; }
}
return "FFFFFF";
}

function dbCleaner($str){
$str = trim($str);
$str = addslashes($str);
$str = strtoupper($str);
//$str = ucwords($str);
return $str;
}



$id=$_COOKIE[psdata][user_id];

function mkCC($str){
$q="SELECT * FROM county";
$r=@mysql_query($q);
$option = '<option>'.$str.'</option>';
while($d=mysql_fetch_array($r, MYSQL_ASSOC)){;
$option .= '<option>'.$d[name].'</option>';
}
return $option;
}

function photoCount($packet){
	$count=trim(getPage("http://data.mdwestserve.com/countPhotos.php?packet=$packet", 'MDWS Count Photos', '5', ''));
	if ($count==''){
		$count=0;
	}
	return $count;
}

function findFileCopy($client_file){
	$search_dir='/data/service/fileCopy/';
	$dp=opendir($search_dir);
	while ($item = readdir($dp)){
		if ((is_dir ($item)) AND (substr($item,0,1) != '.')){
			if (strpos($client_file,$item)){
				echo "$item<br>";
			}
		}
	}
	rewinddir ($dp);
}

function dbIN($str){
	$str = trim($str);
	$str = addslashes($str);
	$str = strtolower($str);
	$str = ucwords($str);
	return $str;
}

function dbOUT($str){
	$str = stripslashes($str);
	return $str;
}

function addressRevise($packet,$address,$oldType,$newType){
	$qh="SELECT action_str, serverID, history_id FROM ps_history WHERE packet_id='$packet' AND action_str LIKE '%$address%'";
	$rh=@mysql_query($qh) or die (mysql_error());
	while ($dh=mysql_fetch_array($rh,MYSQL_ASSOC)){
		if ($dh != ''){
			$newStr=str_replace($oldType,$newType,$dh[action_str]);
			$list .= "<tr><td>".$dh[history_id]."</td><td>$oldType</td><td>$newType</td></tr>'";
			@mysql_query("UPDATE ps_history SET action_str='".$newStr."' WHERE history_id='".$dh[history_id]."'");
		}
	}
	return $list;
}

if ($_POST[reopen]){
	$r13=@mysql_query("select processor_notes, fileDate from ps_packets where packet_id = '$_GET[packet]'");
	$d13=mysql_fetch_array($r13,MYSQL_ASSOC);
	$oldNote = $d13[processor_notes];
	$note="file originally closed out on ".$d13[fileDate];
	$newNote = "<li>From ".$_COOKIE[psdata][name]." on ".date('m/d/y g:ia').": \"".$note."\"</li>".$oldNote;
	$today=date('Y-m-d');
	$deadline=time()+604800;
	$deadline=date('Y-m-d',$deadline);
	$q="UPDATE ps_packets SET processor_notes='".dbIN($newNote)."', filing_status='REOPENED', affidavit_status='IN PROGRESS', affidavit_status2='REOPENED', process_status='ASSIGNED', reopenDate='$today', fileDate='0000-00-00', estFileDate='$deadline', request_close='', request_closea='', request_closeb='', request_closec='', request_closed='', request_closee='' WHERE packet_id='".$_GET[packet]."'";
	@mysql_query($q) or die ("Query: $q<br>".mysql_error());
	timeline($_GET[packet],$_COOKIE[psdata][name]." Reopened File for Additional Service");
}

if ($_POST[sendToClient]){
	$today=date('Y-m-d');
	@mysql_query("UPDATE ps_packets SET fileDate='$today', estFileDate='$today', filing_status='SEND TO CLIENT' WHERE packet_id='$_GET[packet]'");
	timeline($_GET[packet],$_COOKIE[psdata][name]." Marked File 'Send to Client'");
}

if ($_POST[submit]){
if ($_GET[packet]){
timeline($_GET[packet],$_COOKIE[psdata][name]." Updated Order");
$q=@mysql_query("SELECT * from ps_packets WHERE packet_id='$_POST[packet_id]'") or die (mysql_error());
$d=mysql_fetch_array($q, MYSQL_ASSOC);
$case_no=trim($_POST[case_no]);
// un dbCleaner on all items

if ($_POST[addressType] != $d[addressType]){
	$searchAdd=$d[address1].", ".$d[city1].", ".$d[state1]." ".$d[zip1];
	$searchAdd=strtoupper($searchAdd);
	$reviseList=addressRevise($_POST[packet_id],$searchAd,$d[addressType],$_POST[addressType]);
	$TYPE .= "<table><tr><td>History ID</td><td>Old Type</td><td>New Type</td></tr>".$reviseList;
	//$TYPE .= "<h1>POST addressType: ".$_POST[addressType]."</h1><br><h1>DB addressType: ".$d[addressType]."</h1>";
}
foreach(range('a','e') as $letter){
	if ($_POST["addressType$letter"] != $d["addressType$letter"]){
		$searchAdd=$d["address1$letter"].", ".$d["city1$letter"].", ".$d["state1$letter"]." ".$d["zip1$letter"];
		$searchAdd=strtoupper($searchAdd);
		$reviseList=addressRevise($_POST[packet_id],$searchAdd,$d["addressType$letter"],$_POST["addressType$letter"]);
		$TYPE .= "<table><tr><td>History ID</td><td>Old Type</td><td>New Type</td></tr>".$reviseList;
		//$TYPE .= "<h1>POST addressType".$letter.": ".$_POST["addressType$letter"]."</h1><br><h1>DB addressType".$letter.": ".$d["addressType$letter"]."</h1>";
	}
}
@mysql_query("UPDATE ps_packets SET process_status='$_POST[process_status]',
	filing_status='$_POST[filing_status]',
	service_status='$_POST[service_status]',
	fileDate='$_POST[fileDate]',
	courierID='$_POST[courierID]',
	addlDocs='$_POST[addlDocs]',
	estFileDate='$_POST[estFileDate]',
	reopenDate='$_POST[reopenDate]',
	startDate='$_POST[startDate]',
	status='$_POST[status]',
	affidavit_status='$_POST[affidavit_status]',
	affidavit_status2='$_POST[affidavit_status2]',
	photoStatus='$_POST[photoStatus]',
	pobox='$_POST[pobox]',
	pocity='$_POST[pocity]',
	postate='$_POST[postate]',
	pozip='$_POST[pozip]',
	pobox2='$_POST[pobox2]',
	pocity2='$_POST[pocity2]',
	postate2='$_POST[postate2]',
	pozip2='$_POST[pozip2]',
	mail_status='$_POST[mail_status]',
	affidavitType='$_POST[affidavitType]',
	onAffidavit1='$_POST[onAffidavit1]',
	onAffidavit2='$_POST[onAffidavit2]',
	onAffidavit3='$_POST[onAffidavit3]',
	onAffidavit4='$_POST[onAffidavit4]',
	onAffidavit5='$_POST[onAffidavit5]',
	onAffidavit6='$_POST[onAffidavit6]',
	refile='$_POST[refile]',
	amendedAff='$_POST[amendedAff]',
	mailWeight='$_POST[mailWeight]',
	altPlaintiff='$_POST[altPlaintiff]',
	pages='$_POST[pages]',
	rush='$_POST[rush]',
	priority='$_POST[priority]',
	request_close='$_POST[request_close]',
	request_closea='$_POST[request_closea]',
	request_closeb='$_POST[request_closeb]',
	request_closec='$_POST[request_closec]',
	request_closed='$_POST[request_closed]',
	request_closee='$_POST[request_closee]',
	serveComplete='$_POST[serveComplete]',
	serveCompletea='$_POST[serveCompletea]',
	serveCompleteb='$_POST[serveCompleteb]',
	serveCompletec='$_POST[serveCompletec]',
	serveCompleted='$_POST[serveCompleted]',
	serveCompletee='$_POST[serveCompletee]',
	addressType='$_POST[addressType]',
	addressTypea='$_POST[addressTypea]',
	addressTypeb='$_POST[addressTypeb]',
	addressTypec='$_POST[addressTypec]',
	addressTyped='$_POST[addressTyped]',
	addressTypee='$_POST[addressTypee]',
	client_file='".strtoupper($_POST[client_file])."',
	case_no='".str_replace('�',0,$case_no)."',
	reopenNotes='".addslashes(strtoupper($_POST[reopenNotes]))."',
	auctionNote='".strtoupper($_POST[auctionNote])."',
	circuit_court='".strtoupper($_POST[circuit_court])."'
	WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}else{
$case_no=trim($_POST[case_no]);
@mysql_query("UPDATE ps_packets SET process_status='$_POST[process_status]',
	filing_status='$_POST[filing_status]',
	service_status='$_POST[service_status]',
	pobox='$_POST[pobox]',
	pocity='$_POST[pocity]',
	postate='$_POST[postate]',
	pozip='$_POST[pozip]',
	pobox2='$_POST[pobox2]',
	pocity2='$_POST[pocity2]',
	postate2='$_POST[postate2]',
	pozip2='$_POST[pozip2]',
	entry_id='$id',
	courierID='$_POST[courierID]',
	addlDocs='$_POST[addlDocs]',
	fileDate='$_POST[fileDate]',
	estFileDate='$_POST[estFileDate]',
	reopenDate='$_POST[reopenDate]',
	startDate='$_POST[startDate]',
	affidavit_status='$_POST[affidavit_status]',
	affidavit_status2='$_POST[affidavit_status2]',
	photoStatus='$_POST[photoStatus]',
	onAffidavit1='$_POST[onAffidavit1]',
	onAffidavit2='$_POST[onAffidavit2]',
	onAffidavit3='$_POST[onAffidavit3]',
	onAffidavit4='$_POST[onAffidavit4]',
	onAffidavit5='$_POST[onAffidavit5]',
	onAffidavit6='$_POST[onAffidavit6]',
	refile='$_POST[refile]',
	amendedAff='$_POST[amendedAff]',
	mailWeight='$_POST[mailWeight]',
	altPlaintiff='$_POST[altPlaintiff]',
	rush='$_POST[rush]',
	priority='$_POST[priority]',
	pages='$_POST[pages]',
	request_close='$_POST[request_close]',
	request_closea='$_POST[request_closea]',
	request_closeb='$_POST[request_closeb]',
	request_closec='$_POST[request_closec]',
	request_closed='$_POST[request_closed]',
	request_closee='$_POST[request_closee]',
	serveComplete='$_POST[serveComplete]',
	serveCompletea='$_POST[serveCompletea]',
	serveCompleteb='$_POST[serveCompleteb]',
	serveCompletec='$_POST[serveCompletec]',
	serveCompleted='$_POST[serveCompleted]',
	serveCompletee='$_POST[serveCompletee]',
	addressType='$_POST[addressType]',
	addressTypea='$_POST[addressTypea]',
	addressTypeb='$_POST[addressTypeb]',
	addressTypec='$_POST[addressTypec]',
	addressTyped='$_POST[addressTyped]',
	addressTypee='$_POST[addressTypee]',
	mail_status='$_POST[mail_status]',
	affidavitType='$_POST[affidavitType]',
	client_file='".strtoupper($_POST[client_file])."',
	case_no='".str_replace('�',0,$case_no)."',
	process_status='READY',
	status='RECIEVED',
	circuit_court='".strtoupper($_POST[circuit_court])."'
	WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
	timeline($_POST[packet_id],$_COOKIE[psdata][name]." Performed Data Entry");
// here is where we will automate the address check
?><script>window.open('supernova.php?packet=<?=$_POST[packet_id];?>&close=1',   'supernova',   'width=600, height=800'); </script><?
}
if (isset($_POST[server1])){
@mysql_query("UPDATE ps_packets SET server_id='$_POST[server1]' WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if (isset($_POST[server1a])){
@mysql_query("UPDATE ps_packets SET server_ida='$_POST[server1a]' WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if (isset($_POST[server1b])){
@mysql_query("UPDATE ps_packets SET server_idb='$_POST[server1b]' WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if (isset($_POST[server1c])){
@mysql_query("UPDATE ps_packets SET server_idc='$_POST[server1c]' WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if (isset($_POST[server1d])){
@mysql_query("UPDATE ps_packets SET server_idd='$_POST[server1d]' WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if (isset($_POST[server1e])){
@mysql_query("UPDATE ps_packets SET server_ide='$_POST[server1e]' WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
$r=mysql_query("SELECT name1, name2, name3, name4, name5, name6 from ps_packets WHERE packet_id='$_POST[packet_id]'");
$d=mysql_fetch_array($r, MYSQL_ASSOC) or die(mysql_error());
if ($_POST[name1] || ($_POST[name1] != $d[name1])){
@mysql_query("UPDATE ps_packets SET name1='".addslashes($_POST[name1])."',
address1='$_POST[address]',
city1='$_POST[city]',
state1='$_POST[state]',
zip1='$_POST[zip]',
address1a='$_POST[addressa]',
city1a='$_POST[citya]',
state1a='$_POST[statea]',
zip1a='$_POST[zipa]',
address1b='$_POST[addressb]',
city1b='$_POST[cityb]',
state1b='$_POST[stateb]',
zip1b='$_POST[zipb]',
address1c='$_POST[addressc]',
city1c='$_POST[cityc]',
state1c='$_POST[statec]',
zip1c='$_POST[zipc]',
address1d='$_POST[addressd]',
city1d='$_POST[cityd]',
state1d='$_POST[stated]',
zip1d='$_POST[zipd]',
address1e='$_POST[addresse]',
city1e='$_POST[citye]',
     
	 
	         state1e='$_POST[statee]',
zip1e='$_POST[zipe]'
WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if ($_POST[name2] || ($_POST[name2] != $d[name2])){
@mysql_query("UPDATE ps_packets SET name2='".addslashes($_POST[name2])."',
address2='$_POST[address]',
city2='$_POST[city]',
state2='$_POST[state]',
zip2='$_POST[zip]',
address2a='$_POST[addressa]',
city2a='$_POST[citya]',
state2a='$_POST[statea]',
zip2a='$_POST[zipa]',
address2b='$_POST[addressb]',
city2b='$_POST[cityb]',
state2b='$_POST[stateb]',
zip2b='$_POST[zipb]',
address2c='$_POST[addressc]',
city2c='$_POST[cityc]',
state2c='$_POST[statec]',
zip2c='$_POST[zipc]',
address2d='$_POST[addressd]',
city2d='$_POST[cityd]',
state2d='$_POST[stated]',
zip2d='$_POST[zipd]',
address2e='$_POST[addresse]',
city2e='$_POST[citye]',
state2e='$_POST[statee]',
zip2e='$_POST[zipe]'
WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if ($_POST[name3] || ($_POST[name3] != $d[name3])){
@mysql_query("UPDATE ps_packets SET name3='".addslashes($_POST[name3])."',
address3='$_POST[address]',
city3='$_POST[city]',
state3='$_POST[state]',
zip3='$_POST[zip]',
address3a='$_POST[addressa]',
city3a='$_POST[citya]',
state3a='$_POST[statea]',
zip3a='$_POST[zipa]',
address3b='$_POST[addressb]',
city3b='$_POST[cityb]',
state3b='$_POST[stateb]',
zip3b='$_POST[zipb]',
address3c='$_POST[addressc]',
city3c='$_POST[cityc]',
state3c='$_POST[statec]',
zip3c='$_POST[zipc]',
address3d='$_POST[addressd]',
city3d='$_POST[cityd]',
state3d='$_POST[stated]',
zip3d='$_POST[zipd]',
address3e='$_POST[addresse]',
city3e='$_POST[citye]',
state3e='$_POST[statee]',
zip3e='$_POST[zipe]'
WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if ($_POST[name4] || ($_POST[name4] != $d[name4])){
@mysql_query("UPDATE ps_packets SET name4='".addslashes($_POST[name4])."',
address4='$_POST[address]',
city4='$_POST[city]',
state4='$_POST[state]',
zip4='$_POST[zip]',
address4a='$_POST[addressa]',
city4a='$_POST[citya]',
state4a='$_POST[statea]',
zip4a='$_POST[zipa]',
address4b='$_POST[addressb]',
city4b='$_POST[cityb]',
state4b='$_POST[stateb]',
zip4b='$_POST[zipb]',
address4c='$_POST[addressc]',
city4c='$_POST[cityc]',
state4c='$_POST[statec]',
zip4c='$_POST[zipc]',
address4d='$_POST[addressd]',
city4d='$_POST[cityd]',
state4d='$_POST[stated]',
zip4d='$_POST[zipd]',
address4e='$_POST[addresse]',
city4e='$_POST[citye]',
state4e='$_POST[statee]',
zip4e='$_POST[zipe]'
WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if ($_POST[name5] || ($_POST[name5] != $d[name5])){
@mysql_query("UPDATE ps_packets SET name5='".addslashes($_POST[name5])."',
address5='$_POST[address]',
city5='$_POST[city]',
state5='$_POST[state]',
zip5='$_POST[zip]',
address5a='$_POST[addressa]',
city5a='$_POST[citya]',
state5a='$_POST[statea]',
zip5a='$_POST[zipa]',
address5b='$_POST[addressb]',
city5b='$_POST[cityb]',
state5b='$_POST[stateb]',
zip5b='$_POST[zipb]',
address5c='$_POST[addressc]',
city5c='$_POST[cityc]',
state5c='$_POST[statec]',
zip5c='$_POST[zipc]',
address5d='$_POST[addressd]',
city5d='$_POST[cityd]',
state5d='$_POST[stated]',
zip5d='$_POST[zipd]',
address5e='$_POST[addresse]',
city5e='$_POST[citye]',
state5e='$_POST[statee]',
zip5e='$_POST[zipe]'
WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}
if ($_POST[name6] || ($_POST[name6] != $d[name6])){
@mysql_query("UPDATE ps_packets SET name6='".addslashes($_POST[name6])."',
address6='$_POST[address]',
city6='$_POST[city]',
state6='$_POST[state]',
zip6='$_POST[zip]',
address6a='$_POST[addressa]',
city6a='$_POST[citya]',
state6a='$_POST[statea]',
zip6a='$_POST[zipa]',
address6b='$_POST[addressb]',
city6b='$_POST[cityb]',
state6b='$_POST[stateb]',
zip6b='$_POST[zipb]',
address6c='$_POST[addressc]',
city6c='$_POST[cityc]',
state6c='$_POST[statec]',
zip6c='$_POST[zipc]',
address6d='$_POST[addressd]',
city6d='$_POST[cityd]',
state6d='$_POST[stated]',
zip6d='$_POST[zipd]',
address6e='$_POST[addresse]',
city6e='$_POST[citye]',
state6e='$_POST[statee]',
zip6e='$_POST[zipe]'
WHERE packet_id='$_POST[packet_id]'") or die(mysql_error());
}

if ($_GET[packet]){
header ('Location: newOrder.php?packet='.$_GET[packet].'&type='.addslashes($TYPE));
}else{
if ($_GET[start]){
header ('Location: newOrder.php?start='.$_GET[start].'&type='.addslashes($TYPE));
}else{
?><script>window.location.href='newOrder.php';</script><? }
}
}



if ($_GET[packet]){
$r=@mysql_query("SELECT *, CONCAT(TIMEDIFF( NOW(), date_received)) as hours FROM ps_packets where packet_id='$_GET[packet]'");
hardLog('loaded order for '.$_GET[packet],'user');
}else{
if($_GET[start]){
$r=@mysql_query("SELECT *, CONCAT(TIMEDIFF( NOW(), date_received)) as hours FROM ps_packets where status='NEW' and process_status <> 'CANCELLED' AND process_status <> 'DUPLICATE' AND process_status <> 'DAMAGED PDF' and process_status <> 'DUPLICATE/DIFF-PDF' and packet_id >= '$_GET[start]' order by packet_id ");
}else{
$r=@mysql_query("SELECT *, CONCAT(TIMEDIFF( NOW(), date_received)) as hours FROM ps_packets where status='NEW' and process_status <> 'CANCELLED' and process_status <> 'DUPLICATE' AND process_status <> 'DAMAGED PDF' and process_status <> 'DUPLICATE/DIFF-PDF' order by RAND() ");
$test55 = 1;
}
}
$d=mysql_fetch_array($r, MYSQL_ASSOC);

if ($test55){
hardLog('loaded NEW order for '.$d[packet_id],'user');
}


if ($_GET[notify]){

// email client invoice
$to = "Service Updates <mdwestserve@gmail.com>";
$subject = "Service Cancelled for Packet $_GET[packet] ($d[client_file])";
$headers  = "MIME-Version: 1.0 \n";
$headers .= "Content-type: text/html; charset=iso-8859-1 \n";
$headers .= "From: $to \n";
$attR = @mysql_query("select ps_to from attorneys where attorneys_id = '$d[attorneys_id]'");
$attD = mysql_fetch_array($attR, MYSQL_BOTH);
$c=-1;
$cc = explode(',',$attD[ps_to]);
$ccC = count($cc)-1;
while ($c++ < $ccC){
$headers .= "Cc: Service Updates <".$cc[$c]."> \n";
}
$headers .= "Cc: Service Updates <zach@mdwestserve.com> \n";
$q=@mysql_query("UPDATE ps_packets SET status= '$_GET[notify]', process_status='$_GET[notify]', service_status='CANCELLED', filing_status='CANCELLED' where packet_id='$_GET[packet]'");
$body ="<strong>Thank you for selecting MDWestServe as Your Process Service Provider.</strong><br>
Service for <strong>$d[client_file]</strong> is cancelled per $_GET[notify].";
$q20="SELECT * from ps_packets where client_file='$d[client_file]'";
$r20=@mysql_query($q20) or die ("Query: $q20<br>".mysql_error());
$table="<table><tr><td>Our File</td><td>Date Received</td><td>Status</td></tr>";
while ($d20=mysql_fetch_array($r20, MYSQL_ASSOC)){
	$table .="<tr><td>$d20[packet_id]</td><td>$d20[date_received]</td><td>$d20[status], $d20[process_status]</td></tr>";
}
$table .="</table>";
$body .= "$table<br><br>".$_COOKIE[psdata][name]."<br>MDWestServe<br>Harvey West Auctioneers";
mail($to,$subject,$body,$headers);
timeline($_GET[packet],$_COOKIE[psdata][name]." Set status to ".$_GET[notify]);
header('Location: order.php?packet='.$_GET[packet]);
}


if ($_GET[caseLookupFlag] == 1){
// email client invoice
psActivity("caseNumberFlag");
$to = "Michelle Warner <MWarner@logs.com>";
$subject = "Unable to determine case number for file $d[client_file]";
$headers  = "MIME-Version: 1.0 \n";
$headers .= "Content-type: text/html; charset=iso-8859-1 \n";
$headers .= "From: caseLookup Manager <service@hwestauctions.com> \n";
$headers .= "Cc: MDWestServe Group <service@hwestauctions.com> \n";
$headers .= "Cc: Cristy Larkin <CLarkin@logs.com> \n";
$headers .= "Cc: Michael Pollard <MPollard@logs.com> \n";
$headers .= "Cc: Jennie Yiu <JYiu@logs.com> \n";
$q=@mysql_query("UPDATE ps_packets SET caseLookupFlag= '1' where packet_id='$_GET[packet]'");
$body ="<strong>Thank you for selecting MDWestServe as Your Process Service Provider.</strong><br>
For file <strong>$d[client_file]</strong> we are unable to determine case number beacuse there was no case found with Shapiro and Burson.<br>Could you please reply with the case number?";
$table .="</table>";
$body .= "$table<br><br>".$_COOKIE[psdata][name]."<br>MDWestServe<br>Harvey West Auctioneers";
//mail($to,$subject,$body,$headers);
timeline($_GET[packet],$_COOKIE[psdata][name]." Set CaseLookupFlag-no results");
header('Location: order.php?packet='.$_GET[packet]);
}
if ($_GET[caseLookupFlag] == 2){
// email client invoice
psActivity("caseNumberFlag");
$to = "Michelle Warner <MWarner@logs.com>";
$subject = "Unable to determine case number for file $d[client_file]";
$headers  = "MIME-Version: 1.0 \n";
$headers .= "Content-type: text/html; charset=iso-8859-1 \n";
$headers .= "From: caseLookup Manager <service@hwestauctions.com> \n";
$headers .= "Cc: MDWestServe Group <service@hwestauctions.com> \n";
$headers .= "Cc: Cristy Larkin <CLarkin@logs.com> \n";
$headers .= "Cc: Michael Pollard <MPollard@logs.com> \n";
$q=@mysql_query("UPDATE ps_packets SET caseLookupFlag= '1' where packet_id='$_GET[packet]'");
$body ="<strong>Thank you for selecting MDWestServe as Your Process Service Provider.</strong><br>
For file <strong>$d[client_file]</strong> we are unable to determine case number beacuse there was more than one open forclosure case with Shapiro and Burson.<br>Could you please reply with the case number?";
$table .="</table>";
$body .= "$table<br><br>".$_COOKIE[psdata][name]."<br>MDWestServe<br>Harvey West Auctioneers";
//mail($to,$subject,$body,$headers);
timeline($_GET[packet],$_COOKIE[psdata][name]." Set CaseLookupFlag-multiple results");
header('Location: order.php?packet='.$_GET[packet]);
}
?>
<center style="padding:0px;"><? include 'http://staff.mdwestserve.com/otd/timeline.php?packet='.$d[packet_id];?></center>
<style>
a { text-decoration:none}
table { padding:0px; margin:0px; cellpadding:0px;}
body { margin:0px; padding:0px;}
input, select { background-color:#CCFFFF; font-variant:small-caps; }
td { font-variant:small-caps;}
legend {margin:0px; border:solid 1px #FF0000; background-color:#cccccc; padding:0px;}
legend.a {margin:0px; border:solid 1px #FF0000; background-color:#cccccc; padding:0px; font-size:12px}
fieldset {margin:0px; padding:0px; background-color:#FFFFFF; }
.single{background-color:#00FF00}
.duplicate{background-color:#FF0000}
</style>
<? if (!$d[packet_id]){ ?>
<center>
<img src="/404.gif" border="1"><br>
<form>Jump to packet <input name="packet"></form><br><br>
<a href="archive.php?packet=<?=$_GET[packet]?>">Have you checked <b>the archives</b> for packet <?=$_GET[packet]?>?</a>
</center>
<? }else{ ?>
<body  style="padding:0px;">
<script type="text/javascript">
function hideshow(which){
if (!document.getElementById)
return
if (which.style.display=="block")
which.style.display="none"
else
which.style.display="block"
}

function ClipBoard()
{
holdtext.innerText = copytext.innerText;
Copied = holdtext.createTextRange();
Copied.execCommand("Copy");
}</script>
<form method="post">
<table width="100%" style='background-color:<?=colorCode(stripHours($d[hours]),$d[filing_status]);?>; padding:0px;'>
<tr>
<td valign="top">
<FIELDSET style="padding:0px;">
<div style="background-color:#FFFFFF; padding:0px;" align="center">
<table width="100%"  style="padding:0px;"><tr><td align="center">
<? if (!$d[uspsVerify]){?><a href="supernova.php?packet=<?=$d[packet_id]?>" target="preview">!!!Verify Addresses!!!</a><? }else{ ?><img src="http://www.usps.com/common/images/v2header/usps_hm_ci_logo2-159x36x8.gif" ><br>Verified by <? echo $d[uspsVerify]; } ?>
<?
$received=strtotime($d[date_received]);
$deadline=$received+432000;
$deadline=date('F jS Y',$deadline);
$days=number_format((time()-$received)/86400,0);
$hours=number_format((time()-$received)/3600,0);
?>
 </td><td align="center">
<? if(!$d[caseVerify]){ ?> <a href="validateCase.php?case=<?=$d[case_no]?>&packet=<?=$d[packet_id]?>&county=<?=$d[circuit_court]?>" target="preview">!!!Verify Case Number!!!</a><? }else{ ?><img src="http://www.courts.state.md.us/newlogosm.gif"><br>Verified by <? echo $d[caseVerify]; }?>
</td><td align="center">
<? if(!$d[qualityControl]){ ?> <a href="entryVerify.php?packet=<?=$d[packet_id]?>&frame=no" target="preview">!!!Verify Data Entry!!!</a><? }else{ ?><img src="http://staff.mdwestserve.com/small.logo.gif" height="41" width="41"><br>Verified by <? echo $d[qualityControl]; }?>
</td><td align="center"><div style="font-size:15pt" ><?=$hours?> Hours || <?=$days?> Days<br>Deadline: <?=$deadline?><div></td></tr></table>
</div>
<? if ($d[possibleDuplicate]){?>
<div style="background-color:#ff0000" align="center">Duplicate Warning Level: <?=$d[possibleDuplicate]?></div>
<? } ?>
<table width="100%" style="padding:0px;"><tr>
<?
$dupCheck=dupCheck($d[client_file]);
?>
<td valign="top" <?=$dupCheck?>>
<FIELDSET style="padding:0px;">
<LEGEND ACCESSKEY=C><?=id2attorney($d[attorneys_id]);?> File Data <input type="submit" name="submit" style="background-color:#00FF00; font-weight:bold; width:100px;" value="SAVE"></LEGEND>
<table>
<tr>
<td>Client&nbsp;File </td>
<td><input name="client_file" value="<?=$d[client_file]?>" /></td>
</tr>
<tr>
<td><a href="http://casesearch.courts.state.md.us/inquiry/inquiryDetail.jis?caseId=<?=str_replace('-','',trim($d[case_no]))?>&detailLoc=<? if ($d[circuit_court] == "MONTGOMERY"){ echo "MCCI";}elseif($d[circuit_court] == "PRINCE GEORGES"){echo "PGV";}else{ echo "CC";} ?>" target="preview">Case&nbsp;Number</a></td>
<td><input name="case_no" value="<?=$d[case_no]?>" /></td>
</tr>
<tr>
<td>Circuit&nbsp;Court</a></td>
<td><select name="circuit_court"><?=mkCC($d[circuit_court]);?></select></td>
</tr>
<tr>
<td>Start Date</td>
<td><input name="startDate" value="<?=$d[startDate]?>"></td>
</tr>
<tr>
<td>Est. Close</td>
<td><input name="estFileDate" value="<?=$d[estFileDate]?>"></td>
</tr>
<?
$rXX=@mysql_query("select name from courier where courierID = '$d[courierID]'");
$dXX=mysql_fetch_array($rXX,MYSQL_ASSOC);
?>
<tr>
<td>Courier</td>
<td><select name="courierID"><option value="<?=$d[courierID]?>"><?=$dXX[name]?></option>
<?
$CCr=@mysql_query("select * from courier");
while($CCd=mysql_fetch_array($CCr,MYSQL_ASSOC)){
?>
<option value="<?=$CCd[courierID]?>"><?=$CCd[name]?> (<?=$CCd[courierID]?>)</option>
<? }?></select></td>
</tr>
<tr>
<td>File Closed</td>
<td><input name="fileDate" value="<?=$d[fileDate]?>"><input type="submit" name="sendToClient" style="background-color:#66CCFF; font-weight:bold; width:140px;" value="SEND TO CLIENT"></td>
</tr>
<tr>
<td>File Reopened</td>
<td><input name="reopenDate" value="<?=$d[reopenDate]?>"><input type="submit" name="reopen" style="background-color:#FFFF00; font-weight:bold; width:70px;" value="REOPEN"></td>
</tr>
<tr>
<td>Alt. Plaintiff</td>
<td><input name="altPlaintiff" value="<?=$d[altPlaintiff]?>"></td>
</tr>
<tr>
<td>Addl&nbsp;Docs</td>
<td><input name="addlDocs" value="<?=$d[addlDocs]?>"></td>
</tr>
<tr>
<td colspan='2'><div style=" font-size:12px; background-color:ffffff; border:solid 1px #ffff00; padding:0px;">
<?
mysql_select_db('core');
$q5="SELECT * FROM ps_affidavits WHERE packetID = '$d[packet_id]' order by defendantID";
$r5=@mysql_query($q5) or die ("Query: $q5<br>".mysql_error());
while ($d5=mysql_fetch_array($r5, MYSQL_ASSOC)){
		$defname = $d["name".$d5[defendantID]];
		echo "<li><a target='_blank' href='".str_replace('ps/','',$d5[affidavit])."'><strong>".$defname."</strong>: $d5[method]</a></li>";
}
?>
<a href="affidavitUpload.php?packet=<?=$d[packet_id]?>" target="preview">Upload More Documents</a>, <a href="#" onclick="window.open('/lightboard.php?packet=<?=$d[packet_id]?>','Lightboard','menubar=0,resizable=1,status=0,width=800,height=600') ">Lightboard</a>
</div></td></tr>
<? if ($d[attorney_notes]){ ?>
<tr>
<td colspan='2'>Instructions: <?=$d[date_received];?><br><?=$d[attorney_notes];?></td>
</tr>
<? }?>
</table>
</FIELDSET>
<?
if ($dupCheck == "class='duplicate'"){
	echo dupList($d[client_file],$d[packet_id]);
}
?>
</td>
<td valign="top">
<FIELDSET style="padding:0px;">
<LEGEND ACCESSKEY=C>Persons to Serve</LEGEND>
<table>
<tr>
<td nowrap>1<input size="20" name="name1" value="<?=stripslashes($d[name1])?>" /><input <? if ($d[onAffidavit1]=='checked'){echo "checked";} ?> type="checkbox" value="checked" name="onAffidavit1"></td><? $mult=1;?>
</tr><tr>
<td nowrap>2<input size="20" name="name2" value="<?=stripslashes($d[name2])?>" /><input <? if ($d[onAffidavit2]=='checked'){echo "checked";} ?> type="checkbox" value="checked" name="onAffidavit2"></td><? if ($d[name2]){$mult++;}?>
</tr><tr>
<td nowrap>3<input size="20" name="name3" value="<?=stripslashes($d[name3])?>" /><input <? if ($d[onAffidavit3]=='checked'){echo "checked";} ?> type="checkbox" value="checked" name="onAffidavit3"></td><? if ($d[name3]){$mult++;}?>
</tr><tr>
<td nowrap>4<input size="20" name="name4" value="<?=stripslashes($d[name4])?>" /><input <? if ($d[onAffidavit4]=='checked'){echo "checked";} ?> type="checkbox" value="checked" name="onAffidavit4"></td><? if ($d[name4]){$mult++;}?>
</tr><tr>
<td nowrap>5<input size="20" name="name5" value="<?=stripslashes($d[name5])?>" /><input <? if ($d[onAffidavit5]=='checked'){echo "checked";} ?> type="checkbox" value="checked" name="onAffidavit5"></td><? if ($d[name5]){$mult++;}?>
</tr><tr>
<td nowrap>6<input size="20" name="name6" value="<?=stripslashes($d[name6])?>" /><input <? if ($d[onAffidavit6]=='checked'){echo "checked";} ?> type="checkbox" value="checked" name="onAffidavit6"></td><? if ($d[name6]){$mult++;}?>
</tr>
</table>
</FIELDSET>
<?
$otdStr=str_replace('portal//var/www/dataFiles/service/orders/','PS_PACKETS/',$d[otd]);
$otdStr=str_replace('data/service/orders/','PS_PACKETS/',$otdStr);
$otdStr=str_replace('portal/','',$otdStr);
//$otdStr=str_replace('mdwestserve.com','alpha.mdwestserve.com',$otdStr);
/*if (!$otdStr){
	$otdStr=$d[otd];
}*/
if (!strpos($otdStr,'mdwestserve.com')){
	$otdStr="http://mdwestserve.com/".$otdStr;
}
if ($d[packet_id] > 3620){
	$checkLink="serviceSheet.php?packet=$d[packet_id]&autoPrint=1";
}else{
	$checkLink="oldServiceSheet.php?packet=$d[packet_id]&autoPrint=1";
}
$q5="SELECT DISTINCT serverID from ps_history WHERE packet_id='$d[packet_id]'";
$r5=@mysql_query($q5) or die(mysql_error());
$i=0;
$data5=mysql_num_rows($r5);
if ($data5 > 0){
while ($d5=mysql_fetch_array($r5, MYSQL_ASSOC)){$i++;
$q6="SELECT * FROM ps_history WHERE serverID='$d5[serverID]' and packet_id='$d[packet_id]'";
$r6=@mysql_query($q6) or die(mysql_error());
$d6=mysql_num_rows($r6);
if ($i == '1'){
if ($d6 > 1){
$server = $d6." ".initals(id2name($d5[serverID]));
}else{
$server = $d6." ".initals(id2name($d5[serverID]));
}
}else{
if ($d6 > 1){
$server .= ", ".$d6." ".initals(id2name($d5[serverID]));
}else{
$server .= ", ".$d6." ".initals(id2name($d5[serverID]));
}
}
}
}else{
$server="none";
}
?>
<FIELDSET style="background-color:#FFFF00; padding:0px;">
<LEGEND ACCESSKEY=C>Service Links</LEGEND>
<table style="padding:0px; font-weight:bold; border-collapse:collapse; height:150px !important; font-size:small;" cellpadding="0" cellspacing="0">
	<tr>
		<td><a href="http://staff.mdwestserve.com/otd/minips_pay.php?id=<?=$d[packet_id]?>" target="preview">Payments</a></td>
	</tr>
	<tr>
		<td><a href="historyModify.php?packet=<?=$d[packet_id]?>&form=1" target="preview">History (<?=$server?>)</a></td>
	</tr>
	<tr>
		<td><a href="http://service.mdwestserve.com/customInstructions.php?packet=<?=$d[packet_id]?>" target="preview">Instructions (<?=id2attorney($d[attorneys_id])?>)</a>-<a href="instructWiz.php?packet=<?=$d[packet_id]?>" target="preview"><small>[CUSTOMIZE]</small></a></td>
	</tr>
	<tr>
		<td><a href="<?=$otdStr?>" target="preview">OTD</a></td>
	</tr>
	<tr>
		<td><a href="ps_write_invoice.php?id=<?=$d[packet_id]?>" target="preview">Invoice</a></td>
	</tr>
	<tr>
		<td><a href="serviceReview.php?packet=<?=$d[packet_id]?>" target="preview">Timeline</a></td> 
	</tr>
	<tr>
		<td><a href="<?=$checkLink?>" target="_blank">Checklist</a></td>
	</tr>
	<tr>
		<td><a href="photoDisplay.php?packet=<?=$d[packet_id]?>" target="preview"><?$photoCount=photoCount($d[packet_id]); echo $photoCount;?> Photo<? if($photoCount != 1){echo "s";}?></a></td>
	</tr>
	<tr>
		<td><a href="mailings.php?OTD=<?=$d[packet_id]?>" target="preview">Mailings</a></td>
	</tr>
	<?
$FC = trim(getPage("http://data.mdwestserve.com/findFC.php?clientFile=".$d[client_file], 'MDWS File Copy for Packet'.$d[packet_id], '5', ''));
if ($FC != '' && $FC != '1'){
	echo "<tr><td>$FC</td></tr>";
}
	?>
	
</table>
</FIELDSET>
</td></tr></table>
<table style="display:<? if ($_GET[packet]){ echo "block";}else{ echo "none"; }?>; padding:0px;" id="notes" width="100%"><tr><td colspan="2"><fieldset><legend>Notes</legend>
<iframe height="200px" width="600px"  frameborder="0" src="http://staff.mdwestserve.com/notes.php?packet=<?=$d[packet_id]?>"></iframe></fieldset></td></tr></table>
<table style="display:none;" id="track" width="100%"><tr><td align='center'>
<FIELDSET>
<LEGEND ACCESSKEY=C>docuTrack: in-house document tracking solution</LEGEND>
<table width="100%" border="1" style="border-collapse:collapse;" cellspacing='0' cellpadding='2'>
<tr>
	<td>Document</td>
	<td>Defendant</td>
	<td>Signer</td>
	<td>Processor</td>
	<td>Timestamp</td>
</tr>
<? 
$r92=@mysql_query("select * from docuTrack where packet = '$d[packet_id]' order by trackID desc");
while($d92=mysql_fetch_array($r92,MYSQL_ASSOC)){
if ($d92[defendant] == 'OCC'){
	$defname = "OCCUPANT";
}else{
	$defname = $d["name".$d92[defendant]];
}
if ($d92[server]){
	$signer = id2name($d92[server]);
}else{
	$signer = "Version 1 Barcode";
}?>
<tr>
	<td><?=$d92[document]?></td>
	<td><?=$defname?></td>
	<td><?=$signer?></td>
	<td><?=$d92[location]?></td>
	<td><?=$d92[binder]?></td>
</tr>
<? } ?>
</table>    
</FIELDSET></td></tr></table>

<? if(!$d[address1]){ ?>
<table width="100%" style="display:block;" id="addresses">
<? }else{ ?>
<table width="100%" style="display:none;" id="addresses">
<? } ?>
<tr><td>
<FIELDSET>
<LEGEND class="a" ACCESSKEY=C><a href="http://staff.mdwestserve.com/dispatcher.php?aptsut=&address=<?=$d[address1]?>&city=<?=$d[city1]?>&state=<?=$d[state1]?>&miles=5" target="_Blank"><img src="http://staff.mdwestserve.com/small-usps-logo.jpg" border="0"></a>&nbsp;<?=id2name($d[server_id]);?><br><input name="addressType" size="55" style="font-size:10px; background-color:CCFFCC;" value="<?=$d[addressType]?>"></LEGEND>
<table>
<tr>
<td><input id="address" name="address" size="30" value="<?=$d[address1]?>" /></td>
</tr>
<tr>
<td><input size="20" name="city" value="<?=$d[city1]?>" /><input size="1" name="state" value="<?=$d[state1]?>" /><input size="4" name="zip" value="<?=$d[zip1]?>" /></td>
</tr>
</table>    
</FIELDSET>
</td><td>
<FIELDSET>
<LEGEND class="a" ACCESSKEY=C><a href="http://staff.mdwestserve.com/dispatcher.php?aptsut=&address=<?=str_replace('#','',$d[address1a])?>&city=<?=$d[city1a]?>&state=<?=$d[state1a]?>&miles=5" target="_Blank"><img src="http://staff.mdwestserve.com/small-usps-logo.jpg" border="0"></a>&nbsp;<?=id2name($d[server_ida]);?><br><input name="addressTypea" size="55" style="font-size:10px; background-color:CCFFCC;" value="<?=$d[addressTypea]?>"></LEGEND>
<table>
<tr>
<td><input name="addressa" size="30" value="<?=$d[address1a]?>" /></td>
</tr>
<tr>
<td><input name="citya" size="20" value="<?=$d[city1a]?>" /><input size="1" name="statea" value="<?=$d[state1a]?>" /><input size="4" name="zipa" value="<?=$d[zip1a]?>" /></td>
</tr>
</table>    
</FIELDSET>
</td><td>
<FIELDSET>
<LEGEND class="a" ACCESSKEY=C><a href="http://staff.mdwestserve.com/dispatcher.php?aptsut=&address=<?=$d[address1b]?>&city=<?=$d[city1b]?>&state=<?=$d[state1b]?>&miles=5" target="_Blank"><img src="http://staff.mdwestserve.com/small-usps-logo.jpg" border="0"></a>&nbsp;<?=id2name($d[server_idb]);?><br><input name="addressTypeb" size="55" style="font-size:10px; background-color:CCFFCC;" value="<?=$d[addressTypeb]?>"></LEGEND>
<table>
<tr>
<td><input name="addressb" size="30" value="<?=$d[address1b]?>" /></td>
</tr>
<tr>
<td><input name="cityb" size="20" value="<?=$d[city1b]?>" /><input size="1" name="stateb" value="<?=$d[state1b]?>" /><input size="4" name="zipb" value="<?=$d[zip1b]?>" /></td>
</tr>
</table>    
</FIELDSET>
</td></tr>

<tr><td>
<FIELDSET>
<LEGEND class="a" ACCESSKEY=C><a href="http://staff.mdwestserve.com/dispatcher.php?aptsut=&address=<?=$d[address1c]?>&city=<?=$d[city1c]?>&state=<?=$d[state1c]?>&miles=5" target="_Blank"><img src="http://staff.mdwestserve.com/small-usps-logo.jpg" border="0"></a>&nbsp;<?=id2name($d[server_idc]);?><br><input name="addressTypec" size="55" style="font-size:10px; background-color:CCFFCC;" value="<?=$d[addressTypec]?>"></LEGEND>
<table>
<tr>
<td><input name="addressc" value="<?=$d[address1c]?>" size="30" /></td>
</tr>
<tr>
<td><input name="cityc" size="20" value="<?=$d[city1c]?>" /><input size="1" name="statec" value="<?=$d[state1c]?>" /><input size="4" name="zipc" value="<?=$d[zip1c]?>" /></td>
</tr>
</table>    
</FIELDSET>
</td><td>
<FIELDSET>
<LEGEND class="a" ACCESSKEY=C><a href="http://staff.mdwestserve.com/dispatcher.php?aptsut=&address=<?=$d[address1d]?>&city=<?=$d[city1d]?>&state=<?=$d[state1d]?>&miles=5" target="_Blank"><img src="http://staff.mdwestserve.com/small-usps-logo.jpg" border="0"></a>&nbsp;<?=id2name($d[server_idd]);?><br><input name="addressTyped" size="55" style="font-size:10px; background-color:CCFFCC;" value="<?=$d[addressTyped]?>"></LEGEND>
<table>
<tr>
<td><input name="addressd" size="30" value="<?=$d[address1d]?>" /></td>
</tr>
<tr>
<td><input name="cityd" size="20" value="<?=$d[city1d]?>" /><input size="1" name="stated" value="<?=$d[state1d]?>" /><input size="4" name="zipd" value="<?=$d[zip1d]?>" /></td>
</tr>
</table>    
</FIELDSET>
</td><td>
<FIELDSET>
<LEGEND class="a" ACCESSKEY=C><a href="http://staff.mdwestserve.com/dispatcher.php?aptsut=&address=<?=$d[address1e]?>&city=<?=$d[city1e]?>&state=<?=$d[state1e]?>&miles=5" target="_Blank"><img src="http://staff.mdwestserve.com/small-usps-logo.jpg" border="0"></a>&nbsp;<?=id2name($d[server_ide]);?><br><input name="addressTypee" size="55" style="font-size:10px; background-color:CCFFCC;" value="<?=$d[addressTypee]?>"></LEGEND>
<table>
<tr>
<td><input name="addresse" size="30" value="<?=$d[address1e]?>" /></td>
</tr>
<tr>
<td><input name="citye" size="20" value="<?=$d[city1e]?>" /><input size="1" name="statee" value="<?=$d[state1e]?>" /><input size="4" name="zipe" value="<?=$d[zip1e]?>" /></td>
</tr>
</table>    
</FIELDSET>
</td></tr>
</table>



<strong>
	<div align="center" style="background-color:#FFFF00">
    	<a onClick="hideshow(document.getElementById('track'))">Tracking</a> &curren; 
    	<a onClick="hideshow(document.getElementById('addresses'))">Addresses</a> &curren; 
    	<a onClick="hideshow(document.getElementById('pobox'))">Mail Only</a> &curren; 
    	<a onClick="hideshow(document.getElementById('status'))">Status</a> &curren; 
        <a onClick="hideshow(document.getElementById('servers'))">Servers</a> &curren; 
        <a onClick="hideshow(document.getElementById('notes'))">Notes</a> &curren; 
    </div>
</strong>
<table width="100%" id="pobox" style="display:none;"><tr><td>
<table width="100%">
<tr>
<td>Mail Only</td>
<td><input name="pobox" value="<?=$d[pobox]?>" /></td>
</tr>
<tr>
<td>City</td>
<td><input name="pocity" value="<?=$d[pocity]?>" /></td>
</tr>
<tr>
<td>State</td>
<td><input name="postate" value="<?=$d[postate]?>" /></td>
</tr>
<tr>
<td>ZIP</td>
<td><input name="pozip" value="<?=$d[pozip]?>" /></td>
</tr>
</table>
</td><td>
<table width="100%">
<tr>
<td>Mail Only 2</td>
<td><input name="pobox2" value="<?=$d[pobox2]?>" /></td>
</tr>
<tr>
<td>City 2</td>
<td><input name="pocity2" value="<?=$d[pocity2]?>" /></td>
</tr>
<tr>
<td>State 2</td>
<td><input name="postate2" value="<?=$d[postate2]?>" /></td>
</tr>
<tr>
<td>ZIP 2</td>
<td><input name="pozip2" value="<?=$d[pozip2]?>" /></td>
</tr>
</table>    
</td></tr></table>

<table width="100%" id="status" style="display:none;">
<input type="hidden" name="packet_id" value="<?=$d[packet_id]?>" />
<tr>
<? if ($_GET[packet]){?>
<td align="center" width="25%">Client Status<br><select name="status"><option><?=$d[status]?></option>
<?
$q1="SELECT DISTINCT status from ps_packets WHERE status <> ''";
$r1=@mysql_query($q1) or die("Query: $q1<br>".mysql_error());
while ($d1=mysql_fetch_array($r1, MYSQL_ASSOC)){
?>
<option><?=$d1[status]?></option>
<? } ?>
<option value=""></option>
</select></td>
<? }?>
<td align="center" width="25%">Service Status<br><select name="service_status"><option><?=$d[service_status]?></option>
<?
$q1="SELECT DISTINCT service_status from ps_packets WHERE service_status <> ''";
$r1=@mysql_query($q1) or die("Query: $q1<br>".mysql_error());
while ($d1=mysql_fetch_array($r1, MYSQL_ASSOC)){
?>
<option><?=$d1[service_status]?></option>
<? } ?>
<option value=""></option>
</select></td>
<td align="center" width="25%">Filing Status<br><select name="filing_status"><option><?=$d[filing_status]?></option>
<?
$q1="SELECT DISTINCT filing_status from ps_packets WHERE filing_status <> '' AND filing_status <> 'REOPENED'";
$r1=@mysql_query($q1) or die("Query: $q1<br>".mysql_error());
while ($d1=mysql_fetch_array($r1, MYSQL_ASSOC)){
?>
<option><?=strtoupper($d1[filing_status])?></option>
<? } ?>
<option>REOPENED</option>
<option value=""></option>
</select></td></tr><tr>
<td align="center" width="25%">Process Status<br><select name="process_status"><option><?=$d[process_status]?></option>
<?
$q2="SELECT DISTINCT process_status from ps_packets WHERE process_status <> ''";
$r2=@mysql_query($q2) or die("Query: $q2<br>".mysql_error());
while ($d2=mysql_fetch_array($r2, MYSQL_ASSOC)){
?>
<option><?=$d2[process_status]?></option>
<? } ?>
<option value=""></option>
</select></td>
<td align="center" width="25%"><table><tr><td>Affidavit Status<br><select name="affidavit_status"><option><?=$d[affidavit_status]?></option>
<?
$q3="SELECT DISTINCT affidavit_status from ps_packets WHERE affidavit_status <> ''";
$r3=@mysql_query($q3) or die("Query: $q3<br>".mysql_error());
while ($d3=mysql_fetch_array($r3, MYSQL_ASSOC)){
?>
<option><?=$d3[affidavit_status]?></option>
<? } ?>
<option value=""></option>
</select>

</td><td>
<td align="center" colspan='3'>Affidavit Status 2<br><select name="affidavit_status2"><option><?=$d[affidavit_status2]?></option>
<?
$q3="SELECT DISTINCT affidavit_status2 from ps_packets WHERE affidavit_status2 <> '' AND affidavit_status2 <> 'REOPENED' AND affidavit_status2 <> 'AWAITING OUT OF STATE AFFIDAVITS' AND affidavit_status2 <> 'AWAITING OUT OF STATE SERVICE'";
$r3=@mysql_query($q3) or die("Query: $q3<br>".mysql_error());
while ($d3=mysql_fetch_array($r3, MYSQL_ASSOC)){
?>
<option><?=$d3[affidavit_status2]?></option>
<? } ?>
<option>AWAITING OUT OF STATE AFFIDAVITS</option>
<option>AWAITING OUT OF STATE SERVICE</option>
<option>REOPENED</option>
<option value=""></option>
</select></td>
</td></tr></table>
</td>
<td align="center">Photo Status<br><select name="photoStatus"><option><?=$d[photoStatus]?></option>
<?
$q4="SELECT DISTINCT photoStatus from ps_packets WHERE photoStatus <> ''";
$r4=@mysql_query($q4) or die("Query: $q4<br>".mysql_error());
while ($d4=mysql_fetch_array($r4, MYSQL_ASSOC)){
?>
<option><?=$d4[photoStatus]?></option>
<? } ?>
<option value=""></option>
</select></td></tr><tr>
<td align="center" width="25%">Affidavit Type<br><select name="affidavitType"><option><?=$d[affidavitType]?></option>
<?
$q4="SELECT DISTINCT affidavitType from ps_packets WHERE affidavitType <> ''";
$r4=@mysql_query($q4) or die("Query: $q4<br>".mysql_error());
while ($d4=mysql_fetch_array($r4, MYSQL_ASSOC)){
?>
<option><?=$d4[affidavitType]?></option>
<? } ?>
<option value=""></option>
</select></td>
<td align="center" width="25%">Mail Status<br><select name="mail_status"><option><?=$d[mail_status]?></option>
<?
$q4="SELECT DISTINCT mail_status from ps_packets WHERE mail_status <> ''";
$r4=@mysql_query($q4) or die("Query: $q4<br>".mysql_error());
while ($d4=mysql_fetch_array($r4, MYSQL_ASSOC)){
?>
<option><?=$d4[mail_status]?></option>
<? } ?>
<option value=""></option>
</select></td>
<td align="center">
Refile<br>
<input type="checkbox" name="refile" <? if ($d[refile] == 'checked'){ echo "checked";} ?> value="checked">
</td></tr>
<td align="center">
Rush Service<br>
<input type="checkbox" name="rush" <? if ($d[rush] == 'checked'){ echo "checked";} ?> value="checked">
</td><td align="center" style="padding-left:5px">
Priority Service<br>
<input type="checkbox" name="priority" <? if ($d[priority] == 'checked'){ echo "checked";} ?> value="checked">
</td><td align="center" style="padding-left:5px">
Amended Affidavit<br>
<input type="checkbox" name="amendedAff" <? if ($d[amendedAff] == 'checked'){ echo "checked";} ?> value="checked">
</td>
</tr>
<tr>
<td align="center">
Request Close<br>
<input type="checkbox" name="request_close" <? if ($d[request_close] == 'YES'){ echo "checked";} ?> value="YES">
</td>
<td align="center">
Request Close 'A'<br>
<input type="checkbox" name="request_closea" <? if ($d[request_closea] == 'YES'){ echo "checked";} ?> value="YES">
</td>
<td align="center">
Request Close 'B'<br>
<input type="checkbox" name="request_closeb" <? if ($d[request_closeb] == 'YES'){ echo "checked";} ?> value="YES">
</td>
</tr>
<tr>
<td align="center">
Request Close 'C'<br>
<input type="checkbox" name="request_closec" <? if ($d[request_closec] == 'YES'){ echo "checked";} ?> value="YES">
</td>
<td align="center">
Request Close 'D'<br>
<input type="checkbox" name="request_closed" <? if ($d[request_closed] == 'YES'){ echo "checked";} ?> value="YES">
</td>
<td align="center">
Request Close 'E'<br>
<input type="checkbox" name="request_closee" <? if ($d[request_closee] == 'YES'){ echo "checked";} ?> value="YES">
</td>
</tr>
<tr>
<td align="center">
<? if ($d[server_id]){ echo id2name($d[server_id]);}else{echo "Server 1";} ?> Complete<br>
<input name="serveComplete" size="1"  value="<?=$d[serveComplete]?>">
</td>
<td align="center">
<? if ($d[server_ida]){ echo id2name($d[server_ida]);}else{echo "Server 2";} ?> Complete<br>
<input name="serveCompletea" size="1"  value="<?=$d[serveCompletea]?>">
</td>
<td align="center">
<? if ($d[server_idb]){ echo id2name($d[server_idb]);}else{echo "Server 3";} ?> Complete<br>
<input name="serveCompleteb" size="1"  value="<?=$d[serveCompleteb]?>">
</td>
</tr>
<tr>
<td align="center">
<? if ($d[server_idc]){ echo id2name($d[server-idc]);}else{echo "Server 4";} ?> Complete<br>
<input name="serveCompletec" size="1"  value="<?=$d[serveCompletec]?>">
</td>
<td align="center">
<? if ($d[server_idd]){ echo id2name($d[server_idd]);}else{echo "Server 5";} ?> Complete<br>
<input name="serveCompleted" size="1"  value="<?=$d[serveCompleted]?>">
</td>
<td align="center">
<? if ($d[server_ide]){ echo id2name($d[server_ide]);}else{echo "Server 6";} ?> Complete<br>
<input name="serveCompletee" size="1"  value="<?=$d[serveCompletee]?>">
</td>
</tr>
</table>





<table width="100%"  id="servers" style="display:none;">
<tr>
<td valign="top">
<FIELDSET>
<LEGEND ACCESSKEY=C>Process Server #<?=$d[server_id]?><? if ($d[svrPrint] > 0){echo " - <small>PRINTED</small>";}?></LEGEND>
<?
mysql_select_db("core");
$r2=@mysql_query("select * from ps_users where id = '$d[server_id]'");
$d2=mysql_fetch_array($r2, MYSQL_ASSOC);
?>
<table <? if ($d[svrPrint] > 0){echo "bgcolor='#FFFFFF'";}?>>
<tr>
<td><?=$d2[company]?></td>
</tr>
<tr>
<td><?=$d2[name]?></td>
</tr>
<tr>
<td><?=$d2[phone]?></td>
</tr>
<tr>
<td><?=$d2[address]?><br><?=$d2[city]?> <?=$d2[state]?> <?=$d2[zip]?></td>
</tr>
<tr>
<td>
<?   
if ($d["attorneys_id"] == 1 || $d["attorneys_id"] == 44){
$filename = $d["client_file"].'-'.$d["date_received"]."-"."SERVER.PDF";
}else{
$filename = $d["case_no"]."-"."SERVER.PDF";
}
?>
</td>
</tr>
</table>    
</FIELDSET>
</td>
<? if ($d[server_ida]){ ?>
<td valign="top">
<FIELDSET>
<LEGEND ACCESSKEY=C>Process Server "a" #<?=$d[server_ida]?><? if ($d[svrPrinta] > 0){echo " - <small>PRINTED</small>";}?></LEGEND>
<?
mysql_select_db("core");
$r2=@mysql_query("select * from ps_users where id = '$d[server_ida]'");
$d2=mysql_fetch_array($r2, MYSQL_ASSOC);
?>
<table <? if ($d[svrPrinta] > 0){echo "bgcolor='#FFFFFF'";}?>>
<tr>
<td><?=$d2[company]?></td>
</tr>
<tr>
<td><?=$d2[name]?></td>
</tr>
<tr>
<td><?=$d2[phone]?></td>
</tr>
<tr>
<td><?=$d2[address]?><br><?=$d2[city]?> <?=$d2[state]?> <?=$d2[zip]?></td>
</tr>
<tr>
<td>
<?   
if ($d["attorneys_id"] == 1 || $d["attorneys_id"] == 44){
$filename = $d["client_file"].'-'.$d["date_received"]."-"."SERVERa.PDF";
}else{
$filename = $d["case_no"]."-"."SERVERa.PDF";
}
?>
</td>
</tr>
</table>    
</FIELDSET>
</td>
<? }?>
<? if ($d[server_idb]){ ?>
<td valign="top">
<FIELDSET>
<LEGEND ACCESSKEY=C>Process Server "b" #<?=$d[server_idb]?><? if ($d[svrPrintb] > 0){echo " - <small>PRINTED</small>";}?></LEGEND>
<?
mysql_select_db("core");
$r2=@mysql_query("select * from ps_users where id = '$d[server_idb]'");
$d2=mysql_fetch_array($r2, MYSQL_ASSOC);
?>
<table <? if ($d[svrPrintb] > 0){echo "bgcolor='#FFFFFF'";}?>>
<tr>
<td><?=$d2[company]?></td>
</tr>
<tr>
<td><?=$d2[name]?></td>
</tr>
<tr>
<td><?=$d2[phone]?></td>
</tr>
<tr>
<td><?=$d2[address]?><br><?=$d2[city]?> <?=$d2[state]?> <?=$d2[zip]?></td>
</tr>
<tr>
<td>
<?   
if ($d["attorneys_id"] == 1 || $d["attorneys_id"] == 44){
$filename = $d["client_file"].'-'.$d["date_received"]."-"."SERVERb.PDF";
}else{
$filename = $d["case_no"]."-"."SERVERb.PDF";
}
?>
</td>
</tr>
</table>    
</FIELDSET>
</td>
<? }?>
</tr>
<tr>
<? if ($d[server_idc]){ ?>
<td valign="top">
<FIELDSET>
<LEGEND ACCESSKEY=C>Process Server "c" #<?=$d[server_idc]?><? if ($d[svrPrintc] > 0){echo " - <small>PRINTED</small>";}?></LEGEND>
<?
mysql_select_db("core");
$r2=@mysql_query("select * from ps_users where id = '$d[server_idc]'");
$d2=mysql_fetch_array($r2, MYSQL_ASSOC);
?>
<table <? if ($d[svrPrintc] > 0){echo "bgcolor='#FFFFFF'";}?>>
<tr>
<td><?=$d2[company]?></td>
</tr>
<tr>
<td><?=$d2[name]?></td>
</tr>
<tr>
<td><?=$d2[phone]?></td>
</tr>
<tr>
<td><?=$d2[address]?><br><?=$d2[city]?> <?=$d2[state]?> <?=$d2[zip]?></td>
</tr>
<tr>
<td>
<?   
if ($d["attorneys_id"] == 1 || $d["attorneys_id"] == 44){
$filename = $d["client_file"].'-'.$d["date_received"]."-"."SERVERc.PDF";
}else{
$filename = $d["case_no"]."-"."SERVERc.PDF";
}
?>
</td>
</tr>
</table>    
</FIELDSET>
</td>
<? }?>
<? if ($d[server_idd]){ ?>
<td valign="top">
<FIELDSET>
<LEGEND ACCESSKEY=C>Process Server "d" #<?=$d[server_idd]?><? if ($d[svrPrintd] > 0){echo " - <small>PRINTED</small>";}?></LEGEND>
<?
mysql_select_db("core");
$r2=@mysql_query("select * from ps_users where id = '$d[server_idd]'");
$d2=mysql_fetch_array($r2, MYSQL_ASSOC);
?>
<table <? if ($d[svrPrintd] > 0){echo "bgcolor='#FFFFFF'";}?>>
<tr>
<td><?=$d2[company]?></td>
</tr>
<tr>
<td><?=$d2[name]?></td>
</tr>
<tr>
<td><?=$d2[phone]?></td>
</tr>
<tr>
<td><?=$d2[address]?><br><?=$d2[city]?> <?=$d2[state]?> <?=$d2[zip]?></td>
</tr>
<tr>
<td>
<?   
if ($d["attorneys_id"] == 1 || $d["attorneys_id"] == 44){
$filename = $d["client_file"].'-'.$d["date_received"]."-"."SERVERd.PDF";
}else{
$filename = $d["case_no"]."-"."SERVERd.PDF";
}
?>
</td>
</tr>
</table>    
</FIELDSET>
</td>
<? }?>
<? if ($d[server_ide]){ ?>
<td valign="top">
<FIELDSET>
<LEGEND ACCESSKEY=C>Process Server "e" #<?=$d[server_ide]?><? if ($d[svrPrinte] > 0){echo " - <small>PRINTED</small>";}?></LEGEND>
<?
mysql_select_db("core");
$r2=@mysql_query("select * from ps_users where id = '$d[server_ide]'");
$d2=mysql_fetch_array($r2, MYSQL_ASSOC);
?>
<table <? if ($d[svrPrinte] > 0){echo "bgcolor='#FFFFFF'";}?>>
<tr>
<td><?=$d2[company]?></td>
</tr>
<tr>
<td><?=$d2[name]?></td>
</tr>
<tr>
<td><?=$d2[phone]?></td>
</tr>
<tr>
<td><?=$d2[address]?><br><?=$d2[city]?> <?=$d2[state]?> <?=$d2[zip]?></td>
</tr>
<tr>
<td>
<?   
if ($d["attorneys_id"] == 1 || $d["attorneys_id"] == 44){
$filename = $d["client_file"].'-'.$d["date_received"]."-"."SERVERe.PDF";
}else{
$filename = $d["case_no"]."-"."SERVERe.PDF";
}
?>
</td>
</tr>
</table>    
</FIELDSET>
</td>
<? }?>
<td valign="top">
</td></tr><tr><td>

<select name="server1"><? if (!$d[server_id]){ ?><option value="">Select Server </option><? }else{ ?><option value="<?=$d[server_id]?>"><?=id2name($d[server_id]);?> (Server)</option><? } ?>
<?
$q7= "select * from ps_users where contract = 'YES' order by id ASC";
$r7=@mysql_query($q7) or die("Query: $q7<br>".mysql_error());
while ($d7=mysql_fetch_array($r7, MYSQL_ASSOC)) {
?>
<option value="<?=$d7[id]?>"><? if ($d7[company]){echo $d7[company].', '.$d7[name] ;}else{echo $d7[name] ;}?></option>
<?        } ?>
<option value=""></option>
</select><br />
<select name="server1a"><? if (!$d[server_ida]){ ?><option value="">Select Server 'A'</option><? }else{ ?><option value="<?=$d[server_ida]?>"><?=id2name($d[server_ida]);?> (Server A)</option><? } ?>
<?
$q8= "select * from ps_users where contract = 'YES' order by id ASC";
$r8=@mysql_query($q8) or die("Query: $q8<br>".mysql_error());
while ($d8=mysql_fetch_array($r8, MYSQL_ASSOC)) {
?>
<option value="<?=$d8[id]?>"><? if ($d8[company]){echo $d8[company].', '.$d8[name] ;}else{echo $d8[name] ;}?></option>
<?        } ?>
<option value=""></option>
</select><br />
<select name="server1b"><? if (!$d[server_idb]){ ?><option value="">Select Server 'B'</option><? }else{ ?><option value="<?=$d[server_idb]?>"><?=id2name($d[server_idb]);?> (Server B)</option><? } ?>
<?
$q9= "select * from ps_users where contract = 'YES' order by id ASC";
$r9=@mysql_query($q9) or die("Query: $q9<br>".mysql_error());
while ($d9=mysql_fetch_array($r9, MYSQL_ASSOC)) {
?>
<option value="<?=$d9[id]?>"><? if ($d9[company]){echo $d9[company].', '.$d9[name] ;}else{echo $d9[name] ;}?></option>
<?        } ?>
<option value=""></option>
</select>
<select name="server1c"><? if (!$d[server_idc]){ ?><option value="">Select Server 'C'</option><? }else{ ?><option value="<?=$d[server_idc]?>"><?=id2name($d[server_idc]);?> (Server C)</option><? } ?>
<?
$q10= "select * from ps_users where contract = 'YES' order by id ASC";
$r10=@mysql_query($q10) or die("Query: $q10<br>".mysql_error());
while ($d10=mysql_fetch_array($r10, MYSQL_ASSOC)) {
?>
<option value="<?=$d10[id]?>"><? if ($d10[company]){echo $d10[company].', '.$d10[name] ;}else{echo $d10[name] ;}?></option>
<?        } ?>
<option value=""></option>
</select>
<select name="server1d"><? if (!$d[server_idd]){ ?><option value="">Select Server 'D'</option><? }else{ ?><option value="<?=$d[server_idd]?>"><?=id2name($d[server_idd]);?> (Server D)</option><? } ?>
<?
$q11= "select * from ps_users where contract = 'YES' order by id ASC";
$r11=@mysql_query($q11) or die("Query: $q11<br>".mysql_error());
while ($d11=mysql_fetch_array($r11, MYSQL_ASSOC)) {
?>
<option value="<?=$d11[id]?>"><? if ($d11[company]){echo $d11[company].', '.$d11[name] ;}else{echo $d11[name] ;}?></option>
<?        } ?>
<option value=""></option>
</select>
<select name="server1e"><? if (!$d[server_ide]){ ?><option value="">Select Server 'E'</option><? }else{ ?><option value="<?=$d[server_ide]?>"><?=id2name($d[server_ide]);?> (Server E)</option><? } ?>
<?
$q12= "select * from ps_users where contract = 'YES' order by id ASC";
$r12=@mysql_query($q12) or die("Query: $q12<br>".mysql_error());
while ($d12=mysql_fetch_array($r12, MYSQL_ASSOC)) {
?>
<option value="<?=$d12[id]?>"><? if ($d12[company]){echo $d12[company].', '.$d12[name] ;}else{echo $d12[name] ;}?></option>
<?        } ?>
<option value=""></option>
</select>
</td>
</tr></table>
</FIELDSET>
</td><td valign="top" width="10%">
<?
	if($d[status]=="NEW" || $_GET[otd] == '1'){ 
		$src=str_replace('portal//var/www/dataFiles/service/orders/','PS_PACKETS/',$d[otd]);
		$src=str_replace('data/service/orders/','PS_PACKETS/',$src);
		$src=str_replace('portal/','',$src);
		//$src=str_replace('mdwestserve.com','alpha.mdwestserve.com',$src);
		/*if (!$src){
			$src=$d[otd];
		}*/
	}elseif(!$d[caseVerify]){
		$src="validateCase.php?case=$d[case_no]&packet=$d[packet_id]&county=$d[circuit_court]";
	}elseif(!$d[uspsVerify]){
		$src="supernova.php?packet=$d[packet_id]";
	}elseif(!$d[qualityControl]){
		$src="entryVerify.php?packet=$d[packet_id]&frame=no";
	}elseif($d[process_status] == "CANCELLED" || $d[filing_status]=="FILED WITH COURT" || $d[filing_status]=="FILED WITH COURT - FBS"){
		$src="http://mdwestserve.com/AC/minips_pay.php?id=$d[packet_id]";
	}else{
		$src="serviceReview.php?packet=$d[packet_id]"; 
	}
	$explode = explode("/",$d[otd]);
	$explodeCount=count($explode)-1;
?>
<table style="padding:0px;" width="100%">
	<tr>
		<td style='font-size:12px;' valign="bottom"><input name="pages" value="<?=$d[pages]?>" size="3"> # OTD Pages <?=testLink($d[otd])?> <b style="background-color:#FFFF00; padding:0px;"><?=trim($explode["$explodeCount"])?></b></td></form>
		<form action="http://staff.mdwestserve.com/temp/pageRemove.php"><td valign="bottom"><input type="hidden" name="id" value="<?=$d[packet_id]?>"><input type="hidden" name="type" value="OTD"><? if ($_GET[packet]){ ?><input type="hidden" name="packet" value="<?=$d[packet_id]?>"><? } ?><input name="skip" onclick="value=''" value="Remove Page #"> <input type="submit" value="GO!"></td></form>
	</tr>
	<tr>
		<td colspan="2" valign="bottom">
		<input name="otd" value="<?=$d[otd]?>" size="80"> <? if($d[status]=="NEW"){ echo "<a href='renameOTD.php?packet=$d[packet_id]&test=1'>FIX OTD LINK</a>";}else{echo "<a href='renameOTD.php?packet=$d[packet_id]'>FIX</a>";} ?>
		</td>
	</tr>
</table>
<iframe height="650px" width="900px" name="preview" id="preview" src="<?=$src?>" ></iframe>
</td></tr></table>
<? }?>
<script>document.title='<?=$_GET[packet]?>|<?=$d[status]?>|<?=$d[service_status]?>|<?=$d[process_status]?>|<?=$d[affidavit_status]?>'</script>
<? 
if ($_GET[type]){
	echo $_GET[type];
}
include 'footer.php';?>
<?
include 'common.php';
hardLog('Occupant Envelope Printout','user');

$_SESSION[inc] = 0;
function printSet($packet){
	$_SESSION[inc] = $_SESSION[inc]+1;
	$r=@mysql_query("select address1, address1a, address1b, address1c, address1d, address1e, city1, city1a, city1b, city1c, city1d, city1e, state1, state1a, state1b, state1c, state1d, state1e, zip1, zip1a, zip1b, zip1c, zip1d, zip1e, address2, address2a, address2b, address2c, address2d, address2e, city2, city2a, city2b, city2c, city2d, city2e, state2, state2a, state2b, state2c, state2d, state2e, zip2, zip2a, zip2b, zip2c, zip2d, zip2e, address3, address3a, address3b, address3c, address3d, address3e, city3, city3a, city3b, city3c, city3d, city3e, state3, state3a, state3b, state3c, state3d, state3e, zip3, zip3a, zip3b, zip3c, zip3d, zip3e, address4, address4a, address4b, address4c, address4d, address4e, city4, city4a, city4b, city4c, city4d, city4e, state4, state4a, state4b, state4c, state4d, state4e, zip4, zip4a, zip4b, zip4c, zip4d, zip4e, address5, address5a, address5b, address5c, address5d, address5e, city5, city5a, city5b, city5c, city5d, city5e, state5, state5a, state5b, state5c, state5d, state5e, zip5, zip5a, zip5b, zip5c, zip5d, zip5e, address6, address6a, address6b, address6c, address6d, address6e, city6, city6a, city6b, city6c, city6d, city6e, state6, state6a, state6b, state6c, state6d, state6e, zip6, zip6a, zip6b, zip6c, zip6d, zip6e, pobox, pobox2, pocity, pocity2, postate, postate2, pozip, pozip2 from ps_packets where packet_id = '$packet'");
	$d=mysql_fetch_array($r, MYSQL_ASSOC);
	$name = "OCCUPANT";
	$line1 = $d["address1"];
	$csz = $d["city1"].', '.$d["state1"].' '.$d["zip1"];
	$cord = "OTD$packet";

	?>
	<table style='page-break-after:always' align='center'><tr><td>
	<IMG SRC="http://staff.mdwestserve.com/barcode.php?barcode=<?=$cord?>&width=400&height=40"><br>
	<img  src="http://staff.mdwestserve.com/envelopecard.jpg.php?name=<?=strtoupper($name)?>&line1=<?=strtoupper(str_replace('#','no. ',$line1))?>&csz=<?=strtoupper($csz)?>">
	</td></tr></table>
	<?
}
function printSet2($packet){
	$_SESSION[inc] = $_SESSION[inc]+1;
	$r=@mysql_query("select * FROM occNotices WHERE packet_id='$packet'");
	$d=mysql_fetch_array($r, MYSQL_ASSOC);
	$name = "OCCUPANT";
	$line1 = $d["address"];
	$csz = $d["city"].', '.$d["state"].' '.$d["zip"];
	$cord = "OTD$packet";
	?>
	<table style='page-break-after:always' align='center'><tr><td>
	<IMG SRC="http://staff.mdwestserve.com/barcode.php?barcode=<?=$cord?>&width=400&height=40"><br>
	<img  src="http://staff.mdwestserve.com/envelopecard.jpg.php?name=<?=strtoupper($name)?>&line1=<?=strtoupper(str_replace('#','no. ',$line1))?>&csz=<?=strtoupper($csz)?>">
	</td></tr></table>
	<?
}
function getPacketData($packet){
	$q="select address1, address1a, address1b, address1c, address1d, address1e, city1, city1a, city1b, city1c, city1d, city1e, state1, state1a, state1b, state1c, state1d, state1e, zip1, zip1a, zip1b, zip1c, zip1d, zip1e, address2, address2a, address2b, address2c, address2d, address2e, city2, city2a, city2b, city2c, city2d, city2e, state2, state2a, state2b, state2c, state2d, state2e, zip2, zip2a, zip2b, zip2c, zip2d, zip2e, address3, address3a, address3b, address3c, address3d, address3e, city3, city3a, city3b, city3c, city3d, city3e, state3, state3a, state3b, state3c, state3d, state3e, zip3, zip3a, zip3b, zip3c, zip3d, zip3e, address4, address4a, address4b, address4c, address4d, address4e, city4, city4a, city4b, city4c, city4d, city4e, state4, state4a, state4b, state4c, state4d, state4e, zip4, zip4a, zip4b, zip4c, zip4d, zip4e, address5, address5a, address5b, address5c, address5d, address5e, city5, city5a, city5b, city5c, city5d, city5e, state5, state5a, state5b, state5c, state5d, state5e, zip5, zip5a, zip5b, zip5c, zip5d, zip5e, address6, address6a, address6b, address6c, address6d, address6e, city6, city6a, city6b, city6c, city6d, city6e, state6, state6a, state6b, state6c, state6d, state6e, zip6, zip6a, zip6b, zip6c, zip6d, zip6e, pobox, pobox2, pocity, pocity2, postate, postate2, pozip, pozip2, name1, name2, name3, name4, name5, name6 from ps_packets where packet_id = '$packet'";
	$r=@mysql_query($q);
	$d=mysql_fetch_array($r, MYSQL_ASSOC);	
	printSet($packet);	
	return $data;
}

function occNotice($cn){
	$r=@mysql_query("select * from occNotices where caseNO = '$cn'");
	$d=mysql_fetch_array($r,MYSQL_ASSOC);
	if($d[sendDate]){
		return 1;
	}else{
		return 0;
	}
}
if ($_GET[OTD]){
	$q="select client_file, packet_id from ps_packets where packet_id='$_GET[OTD]'";
	$r=@mysql_query($q);
	$d=mysql_fetch_array($r, MYSQL_ASSOC);
	if ($_GET[custom]){
		printSet2($d[packet_id]);
	}else{
		printSet($d[packet_id]);
	}
}else{
	$i2=0;
	$list='';
	$q="select uspsVerify, caseVerify, qualityControl from ps_packets where (process_status = 'ASSIGNED' OR process_status = 'READY' OR process_status='SERVICE COMPLETED' OR process_status='READY TO MAIL') AND filing_status <> 'FILED WITH COURT' AND filing_status <> 'FILED WITH COURT - FBS' AND filing_status <> 'SEND TO CLIENT' AND affidavit_status <> 'CANCELLED' AND (attorneys_id='3' OR attorneys_id='68' OR attorneys_id='7') AND (qualityControl='' OR uspsVerify='' OR caseVerify='') order by packet_id ASC";
	$r=@mysql_query($q) or die ("Query: $q<br>".mysql_error());
	 while($d=mysql_fetch_array($r, MYSQL_ASSOC)){$i2++;
		if (occNotice($d[case_no]) == 0){
			if ($list=''){ $list = $d[packet_id]; }else{ $list .= ', '.$d[packet_id]; }
		}
	 }
	 if ($list != ''){
		if ($i2 > 1){
			$msg="PACKETS $list HAVE NOT HAD ALL DATA ENTRY VERIFIED.  NO NOTICES MAY BE PRINTED UNTIL THIS IS REMEDIED.";
		}else{
			$msg="PACKET $list HAS NOT HAD ALL DATA ENTRY VERIFIED.  NO NOTICES MAY BE PRINTED UNTIL THIS IS REMEDIED.";
		}
		echo "<script>alert('$msg')</script>";
	 }else{
		$q="select case_no, packet_id from ps_packets where (process_status = 'ASSIGNED' OR process_status = 'READY' OR process_status='SERVICE COMPLETED' OR process_status='READY TO MAIL') AND filing_status <> 'FILED WITH COURT' AND filing_status <> 'FILED WITH COURT - FBS' AND filing_status <> 'SEND TO CLIENT' AND affidavit_status <> 'CANCELLED' AND (attorneys_id='3' OR attorneys_id='68' OR attorneys_id='7') order by packet_id";
		$r=@mysql_query($q);
		while($d=mysql_fetch_array($r, MYSQL_ASSOC)){ $i++;
			if (occNotice($d[case_no]) == 0){
				getPacketData($d[packet_id]);
			}
		}
	}
}
?>
<script>document.title='<?=$_SESSION[letters]?> Occupant Envelopes';</script>

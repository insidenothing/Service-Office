<?
include 'common.php';
function monthConvert($month){
	if ($month == '01'){ return 'January'; }
	if ($month == '02'){ return 'February'; }
	if ($month == '03'){ return 'March'; }
	if ($month == '04'){ return 'April'; }
	if ($month == '05'){ return 'May'; }
	if ($month == '06'){ return 'June'; }
	if ($month == '07'){ return 'July'; }
	if ($month == '08'){ return 'August'; }
	if ($month == '09'){ return 'September'; }
	if ($month == '10'){ return 'October'; }
	if ($month == '11'){ return 'November'; }
	if ($month == '12'){ return 'December'; }
}
function month2num($month){
	if (strtoupper($month) == 'JANUARY' || $month == 1){
		return '1';
	}elseif (strtoupper($month) == 'FEBRUARY' || $month == 2){
		return '2';
	}elseif (strtoupper($month) == 'MARCH' || $month == 3){
		return '3';
	}elseif (strtoupper($month) == 'APRIL' || $month == 4){
		return '4';
	}elseif (strtoupper($month) == 'MAY' || $month == 5){
		return '5'; 
	}elseif (strtoupper($month) == 'JUNE' || $month == 6){
		return '6';
	}elseif (strtoupper($month) == 'JULY' || $month == 7){
		return '7';
	}elseif (strtoupper($month) == 'AUGUST' || $month == 8){
		return '8';
	}elseif (strtoupper($month) == 'SEPTEMBER' || $month == 9){
		return '9';
	}elseif (strtoupper($month) == 'OCTOBER' || $month == 10){
		return '10';
	}elseif (strtoupper($month) == 'NOVEMBER' || $month == 11){
		return '11';
	}elseif (strtoupper($month) == 'DECEMBER' || $month == 12){
		return '12'; 
	}else{
		return $month;
	}
}
function dateExplode($date){
	$date=explode('-',$date);
	$date=monthConvert($date[1])." ".$date[2].", ".$date[0];
	return $date;
}
function dateImplode($date){
	$str=explode(' AT ',$date);
	$time=str_replace(' ','',$str[1]);
	$date2=explode(' ',$str[0]);
	$month=month2num(trim($date2[0]));
	$day=str_replace(',','',$date2[1]);
	$year=$date2[2];
	return $month.'/'.$day.'/'.$year.' @ '.$time;
}
function postDateImplode($date){
	$str=explode(' ',$date);
	$time=$str[3].$str[4];
	$month=month2num(trim($str[0]));
	$day=str_replace(',','',$str[1]);
	$year=$str[2];
	return $month.'/'.$day.'/'.$year.' @ '.$time;
}
function attorneyCustomLang($att,$str){
	$r=@mysql_query("SELECT * FROM ps_str_replace where attorneys_id = '$att'");
	while ($d=mysql_fetch_array($r, MYSQL_ASSOC)){
		if ($d['str_search'] && $d['str_replace'] && $str && $att){
			$str = str_replace($d['str_search'], strtoupper($d['str_replace']), $str);
			$str = str_replace(strtoupper($d['str_search']), strtoupper($d['str_replace']), $str);
			//echo "<script>alert('Replacing ".strtoupper($d['str_search'])." with ".strtoupper($d['str_replace']).".');< /script>";
		}
	}
	return $str;
}
function historyList($packet,$defendant,$attorneys_id,$svc){
	if ($svc == 'OTD'){
		$qn="SELECT * FROM ps_history WHERE packet_id = '$packet' and defendant_id = '$defendant' order by history_id ASC";
	}else{
		$qn="SELECT * FROM evictionHistory WHERE eviction_id = '$packet' and defendant_id = '$defendant' order by history_id ASC";
	}
		$rn=@mysql_query($qn);
		$list = "<div>";
		$counter=0;
		while ($dn=mysql_fetch_array($rn, MYSQL_ASSOC)){$counter++;
			$action_str=str_replace('<LI>','',strtoupper($dn[action_str]));
			$action_str=str_replace('</LI>','',$action_str);
				$list .=  "<div class='list'>#$dn[history_id] : ".id2server($dn[serverID]).' '.$dn[wizard].'<br>'.stripslashes(attorneyCustomLang($attorneys_id,$action_str));
				if ($dn[wizard] == 'BORROWER' || $dn[wizard] == 'NOT BORROWER'){
					$list .=  '<br>'.attorneyCustomLang($attorneys_id,$dn[residentDesc]);
				}
				$list .= "</div>";
		}
		$list .=  "</div>";
		return $list;
}
function nameDef($defendant,$packet,$svc){
	if ($svc='OTD'){
		$r=@mysql_query("SELECT name".$defendant." from ps_packets WHERE packet_id='$packet'") or die (mysql_error());
	}else{
		$r=@mysql_query("SELECT name".$defendant." from evictionPackets WHERE eviction_id='$packet'") or die (mysql_error());
	}
	$d=mysql_fetch_array($r,MYSQL_ASSOC);
	return strtoupper($d["name$defendant"]);
}
function isAddress($str,$address){
	$str=explode('<BR>',$str);
	$str=" ".trim($str[1])." ";
	$address=trim($address);
	$strpos=strpos($str,$address);
	if ($strpos != ''){
		return "true";
	}else{
		return "false";
	}
}
function deliveryExplode($packet,$defendant,$svc){
	if ($svc == 'OTD'){
		$qh="SELECT action_str, serverID, address, resident, wizard FROM ps_history WHERE packet_id='$packet' AND defendant_id='$defendant' AND (WIZARD='BORROWER' OR WIZARD='NOT BORROWER') AND onAffidavit='checked'";
	}else{
		$qh="SELECT action_str, serverID, address, resident, wizard FROM evictionHistory WHERE eviction_id='$packet' AND defendant_id='$defendant' AND (WIZARD='BORROWER' OR WIZARD='NOT BORROWER') AND onAffidavit='checked'";
	}
	$rh=@mysql_query($qh) or die (mysql_error());
	$dh=mysql_fetch_array($rh,MYSQL_ASSOC);
	if ($dh != ''){
		$action=explode('DATE OF SERVICE: ',strtoupper($dh[action_str]));
		$dt=explode('<BR>',$action[1]);
		$return[0]=dateImplode($dt[0]);
		$return[1]=id2name($dh[serverID]);
		if ($dh[wizard] == 'BORROWER'){
			$return[1] .= " To: ".nameDef($defendant,$packet,$svc);
		}else{
			$return[1] .= " To: ".strtoupper($dh[resident]);
		}
		$address=explode(',',$dh[address]);
		$return[2]=substr($address[0],0,16);
	}
	return $return;
}
function attemptExplode($packet,$defendant,$address,$type,$svc){
	if ($svc == 'OTD'){
		$qh="SELECT action_str, serverID FROM ps_history WHERE packet_id='$packet' AND defendant_id='$defendant' AND action_str LIKE '%$address%' AND WIZARD='$type' AND onAffidavit='checked'";
	}else{
		$qh="SELECT action_str, serverID FROM evictionHistory WHERE eviction_id='$packet' AND defendant_id='$defendant' AND action_str LIKE '%$address%' AND WIZARD='$type' AND onAffidavit='checked'";
	}
	$rh=@mysql_query($qh) or die (mysql_error());
	while ($dh=mysql_fetch_array($rh,MYSQL_ASSOC)){
		$isAdd=isAddress(strtoupper($dh[action_str]),strtoupper($address));
		if ($isAdd != "false"){
			$action=explode('</LI>',strtoupper($dh[action_str]));
			$dt=explode('<BR>',$action[1]);
			if ($type == 'POSTING DETAILS'){
				$return[0]=postDateImplode($dt[0]);
			}else{
				$return[0]=dateImplode($dt[0]);
			}
			$return[1]=id2name($dh[serverID]);
		}
	}
	return $return;
}
function serviceSheet($packet){
	$q="SELECT * from ps_packets, ps_pay WHERE ps_packets.packet_id='$packet' AND ps_packets.packet_id=ps_pay.packetID AND ps_pay.product='OTD' LIMIT 0,1";
	$r=@mysql_query($q) or die(mysql_error());
	$d=mysql_fetch_array($r, MYSQL_ASSOC);
	if (!$d[payID]){
		@mysql_query("INSERT INTO ps_pay (packetID,product) VALUES ('$packet','OTD')");
		$q="SELECT * from ps_packets, ps_pay WHERE ps_packets.packet_id='$packet' AND ps_packets.packet_id=ps_pay.packetID AND ps_pay.product='OTD' LIMIT 0,1";
		$r=@mysql_query($q) or die(mysql_error());
		$d=mysql_fetch_array($r, MYSQL_ASSOC);
	}
	$date=date("m/d/Y h:i:s A");
	if ($d[attorneys_id] == 70 || $d[attorneys_id] == 80){
		$sum=$d[bill410]+$d[bill420]+$d[bill440]+$d[bill460];
	}else{
		$sum=$d[bill410]+$d[bill420]+$d[bill430]+$d[bill440]+$d[bill450]+$d[bill460];
	}
	if ($sum == 0){
		$sum='';
	}
	ob_start();
	?>
	<style>
	body{padding:0px; margin:0px;}
	td{font-size:11px;}
	div{font-size:10px; text-align:left;}
	fieldset, legend, div, table, tr, td, input {padding:0px;}
	div.list {border-bottom:solid 1px; font-size:10px;}
	</style>
	<table align="center" style="border:1px solid; page-break-after:always;"><tr><td>
	<div class='list'>Quality Control for Process Serving Packet <?=$d[packet_id]?> - <b>Printed <?=$date?></b></div>
	<fieldset>
	<legend>Billing Matrix</legend>
	<table width="100%" align="center"><tr>
	<td width="14%" align="left" style="border-bottom:solid 1px;"><b>Service: <?=$d[bill410]?></b></td>
	<td width="14%" align="left" style="border-bottom:solid 1px;"><b>Gas: <?=$d[bill460]?></b></td>
	<td width="14%" align="left" style="border-bottom:solid 1px;"><b>Mailing: <?=$d[bill420]?></b></td>
	<td width="14%" align="left" style="border-bottom:solid 1px;"><b>Filing: <? if ($d[attorneys_id] == 70){ echo "BGW";}else{ echo $d[bill430];} ?></b></td>
	<td width="14%" align="left" style="border-bottom:solid 1px;"><b>Skip Trace: <?=$d[bill440]?></b></td>
	<? if ($data[attorneys_id] != 70 && $data[attorneys_id] != 80){ ?><td width="14%" align="left" style="border-bottom:solid 1px;"><b>HB472 (<?=substr($d[lossMit],0,3);?>): <?=$d[bill450]?></b></td><? } ?>
	<td width="14%" align="left" style="border-bottom:solid 1px;"><b>Total: <? echo $sum;?></b></td>
	</tr>
	<? if($d[affidavit_status2] == 'REOPENED'){ ?>
	<tr>
	<td width="50%" align="center" colspan='6' style="border-bottom:solid 1px;"><b style="font-size: 24px;">REOPENED</b><b> - ADDITIONAL COST:</b></td>
	<td align="center" style="border-bottom:solid 1px;">
	<table width="100%" align="center" valign="top">
	<tr><td align="left" style="border-bottom:solid 1px;"><b>Service: </b></td></tr>
	<tr><td align="left" style="border-bottom:solid 1px;"><b>Gas: </b></td></tr>
	<tr><td align="left" style="border-bottom:solid 1px;"><b>Mailing: </b></td></tr>
	<tr><td align="left" style="border-bottom:solid 1px;"><b>Filing: </b></td></tr>
	<tr><td align="left" style="border-bottom:solid 1px;"><b>Skip Trace: </b></td></tr>
	<tr><td align="left"><b>HB472: </b></td></tr>
	</tr></table>
	</td>
	</tr>
	<? }
	if ($d[rush]){
		echo "<tr><td align='center' colspan='6' style='border-bottom:solid 1px;'><b style='font-size: 24px;'>RUSH</b></b></td></tr>";
	}
	$q2="SELECT * FROM occNotices WHERE packet_id='$d[packet_id]'";
	$r2=@mysql_query($q2) or die("Query: $q1<br>".mysql_error());
	while ($d2=mysql_fetch_array($r2,MYSQL_ASSOC)){
		$notices .= "<tr><td colspan='6' align='center'>$d2[requirements] Notice Sent ".dateExplode($d2[sendDate])." - $".$d2[bill]."</td></tr>";
	}
	if ($notices != ''){
		echo $notices;
	}else{
		echo "<tr><td colspan='6' align='center'>NO NOTICES SENT FOR THIS PACKET</td></tr>";
	}
	?>
	</td></tr></table>
	</fieldset>
	<table width="100%" align="center"><tr><td>
	<?
	$i=0;
	while ($i < 6){$i++;
	//only 2 defendants per row
	if ($i == 3 || $i == 5){
	 echo "</td></tr><tr><td>";
	}elseif($i != 1){
		echo "</td><td>";
	}
	 if ($d["name$i"]){ ?>
	<fieldset>
	<legend accesskey="C"><?=$d["name$i"]?></legend>
	<table align="center" style="border-color:green;">
		<tr>
			<td><input type="checkbox"> <?=$d[address1]?>, <?=$d[city1]?>, <?=$d[state1]?> <?=$d[zip1]?></td>
		</tr>
		<? if ($d[address1a]){?>
		<tr>
			<td><input type="checkbox"> <?=$d[address1a]?>, <?=$d[city1a]?>, <?=$d[state1a]?> <?=$d[zip1a]?></td>
		</tr>
		<? } ?>
		<? if ($d[address1b]){?>
		<tr>
			<td><input type="checkbox"> <?=$d[address1b]?>, <?=$d[city1b]?>, <?=$d[state1b]?> <?=$d[zip1b]?></td>
		</tr>
		<? } ?>
		<? if ($d[address1c]){?>
		<tr>
			<td><input type="checkbox"> <?=$d[address1c]?>, <?=$d[city1c]?>, <?=$d[state1c]?> <?=$d[zip1c]?></td>
		</tr>
		<? } ?>
		<? if ($d[address1d]){?>
		<tr>
			<td><input type="checkbox"> <?=$d[address1d]?>, <?=$d[city1d]?>, <?=$d[state1d]?> <?=$d[zip1d]?></td>
		</tr>
		<? } ?>
		<? if ($d[address1e]){?>
		<tr>
			<td><input type="checkbox"> <?=$d[address1e]?>, <?=$d[city1e]?>, <?=$d[state1e]?> <?=$d[zip1e]?></td>
		</tr>
		<? } ?>
		<? if ($d[pobox]){?>
		<tr>
			<td><input type="checkbox"> <b>MAILING:</b> <?=$d[pobox]?>, <?=$d[pocity]?>, <?=$d[postate]?> <?=$d[pozip]?></td>
		</tr>
		<? } ?>
		<? if ($d[pobox2]){?>
		<tr>
			<td><input type="checkbox"> <b>MAILING:</b> <?=$d[pobox2]?>, <?=$d[pocity2]?>, <?=$d[postate2]?> <?=$d[pozip2]?></td>
		</tr>
		<? } ?>
	</table>
	</fieldset>
	<? } 
	}?>
	</td></tr></table>
	<table width="100%" align="center" cellspacing="0" cellpadding="0" align="center" style="border-color:blue;"><tr><td>
	<?
	$i=0;
	 while ($i < 6){$i++;
	if ($d["name$i"]){ ?> 
	<fieldset>
	<legend accesskey="C"><u>Process Service on <?=$d["name$i"]?></u>:</legend>
	<table cellspacing="0" align="center">
	<?
	$delivery='';
	$delivery=deliveryExplode($packet,$i,'OTD');
	if ($delivery != ''){
		$dt=$delivery[0];
		$server=$delivery[1];
		$deliveryAddress="@ ".$delivery[2];
	}else{
		$dt='';
		$server='';
		$deliveryAddress='';
	}?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> Personal Delivery <?=$deliveryAddress?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
	$address='';
	$address=strtoupper($d[address1].', '.$d[city1].', '.$d[state1].' '.$d[zip1]);
	$attempt='';
	$attempt=attemptExplode($packet,$i,$address,"FIRST EFFORT",'OTD');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 1st Attempt At <?=substr($d[address1],0,20)?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
	$address='';
	$address=strtoupper($d[address1].', '.$d[city1].', '.$d[state1].' '.$d[zip1]);
	$attempt='';
	$attempt=attemptExplode($packet,$i,$address,"SECOND EFFORT",'OTD');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 2nd Attempt At <?=substr($d[address1],0,20)?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
		foreach (range('a','e') as $letter){
	 if ($d["address1$letter"]){
	$address='';
	$address=strtoupper($d["address1$letter"].', '.$d["city1$letter"].', '.$d["state1$letter"].' '.$d["zip1$letter"]);
	$attempt='';
	$attempt=attemptExplode($packet,$i,$address,"FIRST EFFORT",'OTD');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}?>

		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 1st Attempt At <?=substr($d["address1$letter"],0,20)?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
	$address='';
	$address=strtoupper($d["address1$letter"].', '.$d["city1$letter"].', '.$d["state1$letter"].' '.$d["zip1$letter"]);
	$attempt='';
	$attempt=attemptExplode($packet,$i,$address,"SECOND EFFORT",'OTD');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 2nd Attempt At <?=substr($d["address1$letter"],0,20)?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<? }
	} 
	$address='';
	$address=strtoupper($d[address1].', '.$d[city1].', '.$d[state1].' '.$d[zip1]);
	$attempt='';
	$attempt=attemptExplode($packet,$i,$address,"POSTING DETAILS",'OTD');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}
	?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> Posting</td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
		<tr>
			<td colspan="4" align="center"><?=historyList($packet,$i,$d[attorneys_id],'OTD')?></td>
		</tr>
	</table>
	</fieldset>
	<? }
	} 
	if (trim($d[accountingNotes]) != ''){
		echo "<table width='100%' align='center' border='1' style='border-collapse:collapse;'><tr><td align='center'><b>ACCOUNTING NOTES</b><br>".strtoupper(stripslashes($d[accountingNotes]))."</td></tr></table>";
	}
	?>
	</td></tr></table>
	<table width="80%" align="center" cellspacing="0">
		<tr>
			<td><? if($d[date_received]){ echo ' '.$d[date_received];}else{?>___________ <? }?></td>
			<td><?=id2attorney($d[attorneys_id])?></td>
			<td><?=$d[client_file]?></td>
			<td><?=$d[circuit_court]?></td>
			<td><?=$d[case_no]?></td>
		</tr>
	</table>
	<? if ($d[attorneys_id] == 1){?>
		<table width="100%" align="center" cellspacing="0" border="1"><tr>
			<td align='center' colspan=5' style='font-weight:bold; border-top:1px solid black;'><small><?=strtoupper($d[name1])?> - <?=strtoupper($d[address1])?>, <?=strtoupper($d[city1])?>, <?=strtoupper($d[state1])?> <?=strtoupper($d[zip1])?></small></td>
		</tr></table>
	<? } ?>
	</td></tr></table>
<?
	$html = ob_get_clean();
	return $html;
}
function evSheet($eviction){
	$q="SELECT * FROM evictionPackets, ps_pay WHERE evictionPackets.eviction_id='$eviction' AND evictionPackets.eviction_id=ps_pay.packetID AND ps_pay.product='EV' LIMIT 0,1";
	$r=@mysql_query($q) or die(mysql_error());
	$d=mysql_fetch_array($r, MYSQL_ASSOC);
	if (!$d[payID]){
		@mysql_query("INSERT INTO ps_pay (packetID,product) VALUES ('$eviction','EV')");
		$q="SELECT * FROM evictionPackets, ps_pay WHERE evictionPackets.eviction_id='$eviction' AND evictionPackets.eviction_id=ps_pay.packetID AND ps_pay.product='EV' LIMIT 0,1";
		$r=@mysql_query($q) or die(mysql_error());
		$d=mysql_fetch_array($r, MYSQL_ASSOC);
	}
	$date=date("m/d/Y h:i:s A");
	ob_start();
	?>
	<table width="80%" align="center" style="page-break-after:always;"><tr><td>
	<fieldset >
	<legend accesskey="C">Quality Control for EVICTION <?=$d[eviction_id]?> - <b>Printed <?=$date?></b></legend>
	<fieldset>
	<legend>Billing Matrix</legend>
	<table width="100%" align="center"><tr>
	<td width="16%" align="left" style="border-bottom:solid 1px;"><b>Service: <?=$d[bill410]?></b></td>
	<td width="16%" align="left" style="border-bottom:solid 1px;"><b>Gas: <?=$d[bill460]?></b></td>
	<td width="16%" align="left" style="border-bottom:solid 1px;"><b>Mailing: <?=$d[bill420]?></b></td>
	<td width="16%" align="left" style="border-bottom:solid 1px;"><b>Filing: <? if ($d[attorneys_id] == 1 && $d[circuit_court] == 'PRINCE GEORGES'){ echo "<b>MAIL2CLIENT</b>";}else{ echo $d[bill430];}?></b></td>
	<td width="16%" align="left" style="border-bottom:solid 1px;"><b>Total: <? echo ($d[bill410]+$d[bill420]+$d[bill430]+$d[bill460]);?></b></td>
	</tr></table>
	</fieldset>
	<table width="100%" align="center"><tr><td>
	<? 
	$i=0;
	while ($i < 6){$i++;
		if ($d["name$i"]){
			if ($i == 3 || $i == 5){
				echo "</td></tr><tr><td>";
			}elseif($i != 1){
				echo "</td><td>";
			}
			?>
			<fieldset>
			<legend accesskey="C"><?=$d["name$i"]?></legend>
			<table align="center">
				<tr>
					<td><input type="checkbox"> <?=$d[address1]?>, <?=$d[city1]?>, <?=$d[state1]?> <?=$d[zip1]?></td>
				</tr>
			</table>
			</fieldset>
	<? 	}
	} ?>
	</td></tr></table>
	<table width="100%" align="center" cellspacing="0" cellpadding="0"><tr><td>
	<? if ($d["name1"]){ ?>
	<fieldset>
	<legend accesskey="C"><u>Process Service on ALL OCCUPANTS</u>:</legend>
	<table cellspacing="0" align="center">
	<?
	$delivery='';
	$delivery=deliveryExplode($eviction,1,'EV');
	if ($delivery != ''){
		$dt=$delivery[0];
		$server=$delivery[1];
		$deliveryAddress="@ ".$delivery[2];
	}else{
		$dt='';
		$server='';
		$deliveryAddress='';
	}?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> Personal Delivery</td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
	$address=strtoupper($d[address1]);
	$attempt='';
	$attempt=attemptExplode($eviction,1,$address,"FIRST EFFORT",'EV');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}
	?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 1st Attempt At <?=$d[address1]?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
	$address=strtoupper($d[address1]);
	$attempt='';
	$attempt=attemptExplode($eviction,1,$address,"SECOND EFFORT",'EV');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}
	?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 2nd Attempt At <?=$d[address1]?></td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
	<?
	$address=strtoupper($d[address1]);
	$attempt='';
	$attempt=attemptExplode($eviction,1,$address,"POSTING DETAILS",'EV');
	if ($attempt != ''){
		$dt=$attempt[0];
		$server=$attempt[1];
	}else{
		$dt='';
		$server='';
	}
	?>
		<tr>
			<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> Posting</td>
			<td width="30px" style="border-bottom:solid 1px">Date:</td>
			<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
			<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
		</tr>
		<tr>
			<td colspan="4" align="center"><?=historyList($eviction,1,$d[attorneys_id],'EV')?></td>
		</tr>
	</table>
	</fieldset>
	<? }
	
	
	
	
	$i2=1;
	while ($i2 <  6){$i2++;
		if ($d["name$i2"] && (strtoupper($d["onAffidavit$i2"]) != "CHECKED")){ ?>
		<fieldset>
		<legend accesskey="C"><u>Process Service on <?=$d["name$i2"]?></u>:</legend>
		<table cellspacing="0" align="center">
		<?
		$delivery='';
		$delivery=deliveryExplode($eviction,$i2);
		if ($delivery != ''){
			$dt=$delivery[0];
			$server=$delivery[1];
			$deliveryAddress="@ ".$delivery[2];
		}else{
			$dt='';
			$server='';
			$deliveryAddress='';
		}?>
			<tr>
				<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> Personal Delivery</td>
				<td width="30px" style="border-bottom:solid 1px">Date:</td>
				<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
				<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
			</tr>
		<?
		$address=strtoupper($d[address1]);
		$attempt='';
		$attempt=attemptExplode($eviction,$i2,$address,"FIRST EFFORT");
		if ($attempt != ''){
			$dt=$attempt[0];
			$server=$attempt[1];
		}else{
			$dt='';
			$server='';
		}
		?>
			<tr>
				<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 1st Attempt At <?=$d[address1]?></td>
				<td width="30px" style="border-bottom:solid 1px">Date:</td>
				<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
				<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
			</tr>
		<?
		$address=strtoupper($d[address1]);
		$attempt='';
		$attempt=attemptExplode($eviction,$i2,$address,"SECOND EFFORT");
		if ($attempt != ''){
			$dt=$attempt[0];
			$server=$attempt[1];
		}else{
			$dt='';
			$server='';
		}
		?>
			<tr>
				<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> 2nd Attempt At <?=$d[address1]?></td>
				<td width="30px" style="border-bottom:solid 1px">Date:</td>
				<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
				<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
			</tr>
		<?
		$address=strtoupper($d[address1]);
		$attempt='';
		$attempt=attemptExplode($eviction,$i2,$address,"POSTING DETAILS");
		if ($attempt != ''){
			$dt=$attempt[0];
			$server=$attempt[1];
		}else{
			$dt='';
			$server='';
		}
		?>
			<tr>
				<td width="250px" style="border-bottom:solid 1px"><input type="checkbox"> Posting</td>
				<td width="30px" style="border-bottom:solid 1px">Date:</td>
				<td width="170px" style="border-bottom:solid 1px"><?if ($dt != ''){ echo $dt;}else{echo '&nbsp;';}?></td>
				<td width="300px" style="border-bottom:solid 1px">By: <?=$server?></td>
			</tr>
			<tr>
				<td colspan="4" align="center"><?=historyList($eviction,$i2,$d[attorneys_id])?></td>
			</tr>
		</table>
		</fieldset>
		<? }
		
	} 
	if (trim($d[accountingNotes]) != ''){
		echo "<table width='100%' align='center' border='1' style='border-collapse:collapse;'><tr><td align='center'><b>ACCOUNTING NOTES</b><br>".strtoupper(stripslashes($d[accountingNotes]))."</td></tr></table>";
	}
	?>
	</td></tr></table>
	<table width="80%" align="center" cellspacing="0">
		<tr>
			<td><? if($d[date_received]){ echo ' '.$d[date_received];}else{?>___________ <? }?></td>
			<td><?=id2attorney($d[attorneys_id])?></td>
			<td><?=$d[client_file]?></td>
			<td><?=$d[circuit_court]?></td>
			<td><?=$d[case_no]?></td>
		</tr>
	</table>
	<? if ($d[attorneys_id] == 1){?>
		<table width="100%" align="center" cellspacing="0" border="1"><tr>
			<td align='center' colspan=5' style='font-weight:bold; border-top:1px solid black;'><small><?=strtoupper($d[name1])?> - <?=strtoupper($d[address1])?>, <?=strtoupper($d[city1])?>, <?=strtoupper($d[state1])?> <?=strtoupper($d[zip1])?></small></td>
		</tr></table>
	<? } ?>
	</fieldset>
	</td></tr></table>
	<?
	$html = ob_get_clean();
	return $html;
}
?>
<style>
body{padding:0px; margin:0px;}
td{font-size:11px;}
div{font-size:10px; text-align:left;}
div.list {border-bottom:solid 1px; font-size:10px;}
</style>
<?
if ($_GET[scanDate]){
	//select all service sheets for individual for specific date (for "MAIL ONLY" files)
	$q="select packet_id from ps_packets where service_status = 'MAIL ONLY' AND closeOut='".$_GET[mail]."' AND entry_id='".$_COOKIE[psdata][user_id]."' order by packet_id";
	$r=@mysql_query($q) or die(mysql_error());
	while ($d=mysql_fetch_array($r, MYSQL_ASSOC)){
	// echo OTD service sheets
		echo serviceSheet($d[packet_id]);
	}
}else{
	$q="SELECT packet_id from ps_packets WHERE process_status = 'ASSIGNED' AND (request_close = 'YES' OR request_closea = 'YES' OR request_closeb = 'YES' OR request_closec = 'YES' OR request_closed = 'YES' OR request_closee = 'YES')";
	$r=@mysql_query($q) or die(mysql_error());
	while ($d=mysql_fetch_array($r, MYSQL_ASSOC)){
	// echo OTD service sheets
		echo serviceSheet($d[packet_id]);
	}
	$q="SELECT eviction_id from evictionPackets WHERE process_status = 'ASSIGNED' AND request_close = 'YES'";
	$r=@mysql_query($q) or die(mysql_error());
	while ($d=mysql_fetch_array($r, MYSQL_ASSOC)){
	//echo EV service sheets
		echo EVSheet($d[eviction_id]);
	}
}
if ($_GET[autoPrint] == 1){
echo "<script>
if (window.self) window.print();
self.close();
</script>";
}
mysql_close();
?>
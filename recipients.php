<style>
table,tr,td,form,input{padding:0px;}
input{font-size:12px;}
tr:hover{background-color:FFFFDD;}
a:hover{background-color:555555;}
</style>
<?
include 'common.php';
function getClient($id){
	$r=@mysql_query("SELECT display_name from attorneys WHERE envID='$id'");
	$d=mysql_fetch_array($r,MYSQL_ASSOC);
	return $d[display_name];
}
function atDropDown($addressType){
	$list .= "<select style='background-color:#CCEEFF;' name='addressType'>";
	if ($addressType != ''){
		$list .= "<option>".stripslashes(strtoupper($addressType))."</option>";
	}else{
		$list .= "<option></option>";
	}
	$list .= "<option>CLIENT</option><option>COURT</option></select>";
	return $list;
}
if ($_POST[submit] && $_POST[addressType] != ''){
	//if new client, require that envelope image & attorney are linked before submitting query.
	if ($_POST[addressType] == 'CLIENT'){
		$clientEntry=1;
		echo "<form method='post' style='display:inline;'><input type='hidden' name='to1' value='$_POST[to1]'><input type='hidden' name='to2' value='$_POST[to2]'><input type='hidden' name='to3' value='$_POST[to3]'><input type='hidden' name='addressType' value='$_POST[addressType]'><table align='center' border='1'><tr><td colspan='2'>PLEASE SELECT CLIENT</td></tr>
		<tr><td><select name='att_id'>";
		$q8 = "SELECT * FROM attorneys where attorneys_id > '0' AND envID='' ORDER BY display_name ASC";
		$r8 = @mysql_query ($q8) or die(mysql_error());
		while ($data8 = mysql_fetch_array($r8, MYSQL_ASSOC)){ 
			echo "<option value='$data8[attorneys_id]'>$data8[display_name]</option>";
		}
		echo "</select></td><td><input type='submit' name='submit3' value='GO'></td></tr></table></form>";
	}else{
		@mysql_query("INSERT INTO envelopeImage (to1, to2, to3, addressType) VALUES ('".addslashes($_POST[to1])."','".addslashes($_POST[to2])."','".addslashes($_POST[to3])."', '".addslashes($_POST[addressType])."')");
		$newID=mysql_insert_id();
		echo "<center><h2>ENTRY $newID CREATED</h2></center>";
	}
}elseif($_POST[submit]){
	echo "<script>alert('You must select an Address Type!')</script>";
}
if ($_POST[submit2]){
	if ($_POST[addressType] == 'CLIENT' && $_POST[att_id]){
		@mysql_query("UPDATE envelopeImage SET to1='".addslashes($_POST[to1])."', to2='".addslashes($_POST[to2])."', to3='".addslashes($_POST[to3])."', addressType='".addslashes($_POST[addressType])."' WHERE envID='$_POST[envID]'");
		@mysql_query("UPDATE attorneys SET envID='$_POST[envID]' WHERE attorneys_id='$_POST[att_id]'");
		echo "<center><h2>ENTRY UPDATED</h2></center>";
	}elseif($_POST[addressType] != 'CLIENT'){
		@mysql_query("UPDATE envelopeImage SET to1='".addslashes($_POST[to1])."', to2='".addslashes($_POST[to2])."', to3='".addslashes($_POST[to3])."', addressType='".addslashes($_POST[addressType])."' WHERE envID='$_POST[envID]'");
		echo "<center><h2>ENTRY UPDATED</h2></center>";
	}else{
		echo "<script>alert('You must select an Attorney!')</script>";
	}
}
if ($_POST[submit3]){
	@mysql_query("INSERT INTO envelopeImage (to1, to2, to3, addressType) VALUES ('".addslashes($_POST[to1])."','".addslashes($_POST[to2])."','".addslashes($_POST[to3])."', '".addslashes($_POST[addressType])."')");
	$newID=mysql_insert_id();
	@mysql_query("UPDATE attorneys SET envID='$newID' WHERE attorneys_id='$_POST[att_id]'");
	echo "<center><h2>ENTRY $newID CREATED</h2></center>";
}
echo "<table align='center' style='border-collapse:collapse;' border='1'><tr><td>ID</td><td>Recipient</td><td>Address Line 1</td><td>Address Line 2</td><td>Address Type</td><td colspan='2'></td></tr>";
if ($_POST[edit]){
	$q1="SELECT * FROM envelopeImage WHERE envID='$_POST[edit]'";
	$r1=@mysql_query($q1) or die ("Query: $q1<br>".mysql_error());
	$d1=mysql_fetch_array($r1,MYSQL_ASSOC);
	echo "<form method='post' name='form1'><input type='hidden' name='envID' value='$_POST[edit]'><tr><td>$envID</td><td><input style='background-color:#CCEEFF;' name='to1' value='".stripslashes($d1[to1])."' size='70' maxlength='250'></td><td><input style='background-color:#CCEEFF;' name='to2' value='".stripslashes($d1[to2])."' size='45' maxlength='250'></td><td><input style='background-color:#CCEEFF;' name='to3' value='".stripslashes($d1[to3])."' size='35' maxlength='250'></td><td>".atDropDown($d1[addressType]);
	if ($d1[addressType] == 'CLIENT'){
		$q8 = "SELECT * FROM attorneys where attorneys_id > '0' ORDER BY display_name ASC";
		$r8 = @mysql_query ($q8) or die(mysql_error());
		while ($data8 = mysql_fetch_array($r8, MYSQL_ASSOC)){ 
			if ($data8[envID] == $_POST[edit]){
				$list = "<option value='$data8[attorneys_id]'>$data8[display_name]</option>".$list;
			}else{
				$list .= "<option value='$data8[attorneys_id]'>$data8[display_name]</option>";
			}
		}
		echo "<br>Link to Attorney:<br><select name='att_id'>$list</select>";
	}
	echo "</td><td colspan='2'><input type='submit' name='submit2' value='GO'></td></tr></form>";
}elseif($clientEntry != 1){
	echo "<form method='post' name='form1'><tr><td></td><td><input style='background-color:#CCEEFF;' name='to1' size='70'></td><td><input style='background-color:#CCEEFF;' name='to2' size='45'></td><td><input style='background-color:#CCEEFF;' name='to3' size='35'></td><td>".atDropDown('')."</td><td colspan='2'><input type='submit' name='submit' value='GO'></td></tr></form>";
}
echo "<script>form1.to1.focus()</script>";
if ($_POST[edit]){
	$q="SELECT * FROM envelopeImage WHERE envID <> '$_POST[edit]' ORDER BY envID ASC";
}else{
	$q="SELECT * FROM envelopeImage ORDER BY envID ASC";
}
$r=@mysql_query($q) or die("Query: $q<br>".mysql_error());
while($d=mysql_fetch_array($r,MYSQL_ASSOC)){ 
if (strpos(strtoupper($d[to1]),'SHAPIRO & BURSON') !== false){
	//only display green envelope links for Burson
	$link="<a href='http://staff.mdwestserve.com/otd/stuffPacket.2.php?id=$d[envID]&sb=1' target='_blank' style='background-color:black;color:00FF00; font-weight:bold;'>GREEN</a>";
}elseif($d[addressType] == 'CLIENT' || (strpos(strtoupper($d[to1]),'FOR ANNE ARUNDEL COUNTY') !== false) || (strpos(strtoupper($d[to1]),'FOR BALTIMORE CITY') !== false) || (strpos(strtoupper($d[to1]),'FOR DORCHESTER COUNTY') !== false)){
	//only display white envelope links for other clients, dorchester, baltimore city, and anne arundel
	$link="<a href='http://staff.mdwestserve.com/otd/stuffPacket.bgw.php?id=$d[envID]' target='_blank' style='background-color:black;color:white; font-weight:bold;'>WHITE</a>";
}else{
	//display both links for all else
	$link="<a href='http://staff.mdwestserve.com/otd/stuffPacket.bgw.php?id=$d[envID]' target='_blank' style='background-color:black;color:white; font-weight:bold;'>WHITE</a>|<a href='http://staff.mdwestserve.com/otd/stuffPacket.2.php?id=$d[envID]&sb=1' target='_blank' style='background-color:black;color:00FF00; font-weight:bold;'>GREEN</a>";
}
?>
	<form method='post'><input type='hidden' name='edit' value='<?=$d[envID]?>'><tr style='font-size:12px;'><td><?=$d[envID]?></td><td><?=stripslashes($d[to1])?></td><td><?=stripslashes($d[to2])?></td><td><?=stripslashes($d[to3])?></td><td><?=stripslashes($d[addressType])?><? if ($d[addressType] == 'CLIENT'){ echo "-".getClient($d[envID]); }?></td><td><input style='background-color:pink; height:25px;' type='submit' name='edit2' value='[edit]'></td><td><?=$link?></td></tr></form>
<?}
echo "</table>";
error_log("[".date('h:iA n/j/y')."] ".$_COOKIE[psdata][name]." Viewing Envelope Recipients \n",3,"/logs/user.log");
?>
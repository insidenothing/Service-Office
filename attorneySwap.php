<?
if($_COOKIE[psdata][level]=='Operations'){
mysql_connect();
mysql_select_db('core');
if($_POST[id] && $_POST[attid] && $_POST[core] && $_POST[field]){
$q="update $_POST[core] set attorneys_id = '$_POST[attid]' where $_POST[field] = '$_POST[id]' ";
echo $q.'<br>';
@mysql_query($q) or die(mysql_error());
}
?>
<form method="POST">
<table border="1">
 <tr>
  <td>Product</td>
  <td></td>
  <td></td>
  <td>Attorney ID</td>
  <td>Packet ID</td>
 </tr>
 <tr>
  <td>Presale</td>
  <td><input type="radio" name="core" value="ps_packets"></td>
  <td><input type="radio" name="field" value="packet_id"></td>
  <td><input name="attid"></td>
  <td><input name="id"></td>
 </tr>
 <tr>
  <td>Eviction</td>
  <td><input type="radio" name="core" value="evictionPackets"></td>
  <td><input type="radio" name="field" value="eviction_id"></td>
  <td><input name="attid"></td>
  <td><input name="id"></td>
 </tr>
 <tr>
  <td>Standard</td>
  <td><input type="radio" name="core" value="standard_packets"></td>
  <td><input type="radio" name="field" value="packet_id"></td>
  <td><input name="attid"></td>
  <td><input name="id"></td>
 </tr>
</table>
<input type="submit">
</form>
<hr>
<table border="1">
<tr>
<td>Attorney ID</td>
<td>Attorney Name</td>
</tr>
<?
$r=@mysql_query("select attorneys_id, display_name from attorneys order by attorneys_id");
while($d=mysql_fetch_array($r,MYSQL_ASSOC)){
?>
<tr>
<td><?=$d[attorneys_id];?></td>
<td><?=$d[display_name];?></td>
</tr>
<? } ?>
</table>
<?
}else{
header('Location: http://mdwestserve.com');
}
?>
<?
if (!$_COOKIE[admin][name]){
header('Location: http://staff.mdwestserve.com/administrator/login.php');
}
?>
<table align="center"><tr><td>Auto-loaded Modules</td> 
<?
foreach (glob("modules/*.php") as $filename)
{
     echo "<td><a href='$filename' target='box'>".strtoupper(str_replace('modules/','',str_replace('.php','',$filename)))."</a></td>";
}
?>
</tr></table>
<center><iframe name="box" id ="box" style="width:90%; height:90%;"></iframe></center>
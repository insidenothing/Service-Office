<?
mysql_connect();
mysql_select_db('service');


if($_POST[table_name] && $_POST[field_name] && $_POST[merge_name]){
@mysql_query("insert into attribute (table_name,field_name,merge_name) values ('$_POST[table_name]','$_POST[field_name]','$_POST[merge_name]')");
}


?>

<form method="post">
<table border="1" style="border-collapse:collapse;">
<tr>
<td>Add New Attribute</td>
<td>New Value</td>
<td>Example</td>
</tr>
<tr>
<td>table_name</td>
<td><input name="table_name"></td>
<td>server</td>
</tr>
<tr>
<td>field_name</td>
<td><input name="field_name"></td>
<td>name</td>
</tr>
<tr>
<td>merge_name</td>
<td><input name="merge_name"></td>
<td>[SERVERNAME]</td>
</tr>
<tr>
<td colspan="3"><input type="submit" value="Save"></td>
</tr>
</table>
</form>
Current Attributes 
<table border="1" style="border-collapse:collapse;">
<tr>
<td>table_name</td>
<td>field_name</td>
<td>merge_name</td>
</tr>

<?
$r=@mysql_query("select * from attribute order by table_name, field_name");
while($d=mysql_fetch_array($r,MYSQL_ASSOC)){
?>
<tr>
<td><?=$d[table_name];?></td>
<td><?=$d[field_name];?></td>
<td><?=$d[merge_name];?></td>
</tr>
<?  } ?>
</table>
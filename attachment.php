<?
mysql_connect();
mysql_select_db('core');
// manage attachment functions

// database settings
if($_POST){
$queryBuilder = '';
foreach ($_POST as $field => $value) {
    $queryBuilder .= " $field = '$value', ";
}
$queryBuilder =substr($queryBuilder, 0, -2);
$built = "update attachment set $queryBuilder where id = '$_GET[id]' ";
 @mysql_query($built) or die($built.'<br>Error: '.mysql_error());
 echo "Database Updated, data refreshed.";
}

// disk / file system settings


$r=@mysql_query("select * from attachment where id = '$_GET[id]' ");
$d=mysql_fetch_array($r,MYSQL_ASSOC);
?>
<form method="POST">
<table>
<tr>
<td><b>id</b></td>
<td><input disabled value="<?=$d[id];?>"></td>
</tr>
<tr>
<td><b>instruction_id</b></td>
<td><input name="instruction_id" value="<?=$d[instruction_id];?>"></td>
</tr>
<tr>
<td><b>packet_id</b></td>
<td><input name="packet_id" value="<?=$d[packet_id];?>"></td>
</tr>
<tr>
<td><b>user_id</b></td>
<td><input name="user_id" value="<?=$d[user_id];?>"></td>
</tr>
<tr>
<td><b>server_id</b></td>
<td><input name="server_id" value="<?=$d[server_id];?>"></td>
</tr>
<tr>
<td><b>processed</b></td>
<td><input name="processed" value="<?=$d[processed];?>"></td>
</tr>
<tr>
<td><b>url</b>  ** do not change without manually moving file **</td>
<td><input name="url" value="<?=$d[url];?>"></td>
</tr>
<tr>
<td><b>path</b> ** do not change without manually moving file **</td>
<td><input name="path" value="<?=$d[path];?>"></td>
</tr>
<tr>
<td><b>description</b></td>
<td><input name="description" value="<?=$d[description];?>"></td>
</tr>
<tr>
<td><b>type</b></td>
<td><input name="type" value="<?=$d[type];?>"></td>
</tr>
<tr>
<td><b>pages</b></td>
<td><input name="pages" value="<?=$d[pages];?>"></td>
</tr>
<tr>
<td><b>absolute_url</b> ** do not change without manually moving file **</td>
<td><input name="absolute_url" value="<?=$d[absolute_url];?>"></td>
</tr>
<tr>
<td><b>status</b></td>
<td><input name="status" value="<?=$d[status];?>"></td>
</tr>
</table>
<input type="submit" value="Update Database Information">
</form>
<iframe src="<?=$d[absolute_url];?>" width="100%" height="300px">
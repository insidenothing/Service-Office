<? 
$last_line = system('rm -f *.html', $retval); // clear all html for debugging
mysql_connect();
mysql_select_db('core');
ini_set("memory_limit","50M");



function diff($old, $new){
        foreach($old as $oindex => $ovalue){
                $nkeys = array_keys($new, $ovalue);
                foreach($nkeys as $nindex){
                        $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                                $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                        if($matrix[$oindex][$nindex] > $maxlen){
                                $maxlen = $matrix[$oindex][$nindex];
                                $omax = $oindex + 1 - $maxlen;
                                $nmax = $nindex + 1 - $maxlen;
                        }
                }       
        }
        if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
        return array_merge(
                diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
                array_slice($new, $nmax, $maxlen),
                diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

function htmlDiff($old, $new, $id){
        $diff = diff(explode(' ', $old), explode(' ', $new));
        foreach($diff as $k){
                if(is_array($k))
                        $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                                (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
                else $ret .= $k . ' ';
        }
        @mysql_query("insert into affidavitChangeLog (packet, core, changelog) values ('$id','O','".addslashes($ret)."')");
		return $ret;
}

function pullProof($id){ 
	$url = "http://staff.mdwestserve.com/obAffidavit.php?packet=$id&def=ALL!&level=".$_COOKIE[psdata][level]."&user_id=".$_COOKIE[psdata][user_id];
	//$url=urlencode($url);
	error_log("[".date('h:iA n/j/y')."] [".$_COOKIE[psdata][name]."] [".trim($id)."] [Attempting to load] [".trim($url)."] \n", 3, '/logs/fail.log');
	$timeout=5;
    $curl = curl_init();
    curl_setopt ($curl, CURLOPT_URL, $url);
    curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt ($curl, CURLOPT_USERAGENT, sprintf("Mozilla/%d.0",rand(4,5)));
    curl_setopt ($curl, CURLOPT_HEADER, (int)$header);
    curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $html = curl_exec ($curl);
	if(curl_errno($ch)){
		error_log("[".date('h:iA n/j/y')."] [".$_COOKIE[psdata][name]."] [".trim($id)."] [Curl Error] [".curl_error($ch)."] \n", 3, '/logs/fail.log');
	}
	if(!$html){
		error_log("[".date('h:iA n/j/y')."] [".$_COOKIE[psdata][name]."] [".trim($id)."] [Failed to load url] [".trim($url)."] \n", 3, '/logs/fail.log');
	}else{
		//error_log("[".date('h:iA n/j/y')."] [".$_COOKIE[psdata][name]."] [".trim($id)."] [Return following html] [".trim($html)."] \n", 3, '/logs/fail.log');
        $q = "SELECT LiveAffidavit, attorneys_id FROM ps_packets WHERE packet_id = '$_GET[id]'";
        $r = @mysql_query ($q) or die(mysql_error());
        $d = mysql_fetch_array($r, MYSQL_ASSOC);
		if (trim($d[LiveAffidavit]) != ''){
			if (file_exists(trim($d[LiveAffidavit]))){
				//retrieve html for diff
				$url2=str_replace('/data/service/affidavits/','http://mdwestserve.com/aM/',trim($d[LiveAffidavit]));
				curl_setopt ($curl, CURLOPT_URL, $url2);
				$old = curl_exec ($curl);
				curl_close ($curl);
				//delete old html file
				system('rm -f '.trim($d[LiveAffidavit]), $retval);
			}else{
				curl_close ($curl);
			}
		}else{
			curl_close ($curl);
		}
		$dir='/data/service/affidavits/';
		$year=date('Y');
		$month=date('m');
		$day=date('d');
		$path=$dir.$year.'/'.$month.'/'.$day;
		if(!file_exists($dir.$year)){
			//create year folder, with appropriate permissions
			mkdir ($dir.$year,0777);
		}
		if(!file_exists($dir.$year.'/'.$month)){
			//create month folder, with appropriate permissions
			mkdir ($dir.$year.'/'.$month,0777);
		}
		if(!file_exists($path)){
			//create day folder, with appropriate permissions
			mkdir ($path,0777);
		}
		$myFile = "$id.html";
		$fullPath=$path."/".$myFile;
		$fh = fopen($fullPath, 'w') or die("can't open file");
		$la=trim($html);
		fwrite($fh, $la);
		fclose($fh);
		@mysql_query("update ps_packets set LiveAffidavit = '$fullPath' where packet_id = '$id'") or die(mysql_error());
		/*if ($old){
			htmlDiff($old, $html, $id);
		}*/
	}
    return $html;
}
function buildProof($id){
	$buffer = pullProof($id);
	$myFile = "$id.html";
	$fh = fopen($myFile, 'w') or die("can't open file");
	fwrite($fh, $buffer);
	fclose($fh);
}


function loadAD($id){
$r=@mysql_query("select attach1 from schedule_items where schedule_id = '$id'");
$d=mysql_fetch_array($r,MYSQL_ASSOC);
$doc = str_replace('http://staff.hwestauctions.com/bursonFTP/','',$d[attach1]);
exec('cp \'/home/burson/'.$doc.'\' '.$id.'.rtf');
$error = system('python DocumentConverter.py /sandbox/CORE/toPDF/'.$id.'.rtf /sandbox/CORE/toPDF/'.$id.'.html',$result);
$filename = "/sandbox/CORE/toPDF/$id.html";
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
$html = addslashes($contents);
@mysql_query("update schedule_items set LiveAdHTML = '$html' where schedule_id = '$id'");
fclose($handle);
$last_line = system('rm -f '.$id.'.rtf', $retval);
$last_line = system('rm -f '.$id.'.html', $retval);
}







if ($_GET['id']){
	//loadAD($_GET['id']);
	buildProof($_GET['id']);
	header('Location: index.php?id='.$_GET[id]);
}else{
	echo "missing packet id?";
}
?>



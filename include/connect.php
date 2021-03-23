<?php
$htn = "###";
$usn = "###";
$pss = "###";
$dbn = '###';
$conn = mysqli_connect($htn, $usn, $pss, $dbn);
if(mysqli_connect_errno() > 0){
	echo '接続に失敗しました';
	exit;
}
?>

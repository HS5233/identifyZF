<?php
// echo microtime().'<br/>';
include('identifyClass.php');
$identify = new identifyClass('http://jwc.xhu.edu.cn/CheckCode.aspx');
// echo $identify->creatWords();
// echo $identify->detectWords();
// echo $identify->getWords();
// $i = 100;
// while($i--){
	// echo $i.'<br/>';
	// $identify->identify();
// }
// echo microtime().'<br/>';
// echo 'done';
echo $identify->identify();
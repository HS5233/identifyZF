<?php
// 字典格式：
// 1.去掉第一行和最后一行；
// 2.去掉前5列。
// 3.每张图片规格为(宽x高):12x21
error_reporting(0);
set_time_limit(0);

class identifyClass{
	//验证码地址
	public $CheckCodeUrl = 'http://jwc.xhu.edu.cn/CheckCode.aspx';
	
	//根据规格切割验证码，生成单字符图片
	public function creatWords(){
		//获取次数
		$times = 5;
		//标识，用于区分，避免覆盖
		$tag = 'x';
		//没有目录则创建
		if(!is_dir('words_tmp')){
			mkdir('words_tmp');
		}
		while($times--){
			$res = imagecreatefromgif($this->CheckCodeUrl);     //创建图像
			for($i=0;$i<4;$i++){
				$tmpNum = 'words_tmp/'.$tag.$times.$i.'.gif';
				$tmp = imagecreatetruecolor(12,21);
				ImageCopyResized($tmp,$res,0,0,5+$i*12,1,12,21,12,21);
				imagegif($tmp,$tmpNum);
				imagedestroy($tmp);
			}
			imagedestroy($res);
		}
		return 'done!';
	}
	//检测目录下的图片是否有相同的
	public function detectWords(){
		//字典图片所在目录
		$dir = 'detect';
		
		$filesStr = '重复的图片：';
		//遍历目录下图片
		if(is_dir($dir)){
			if($dh = opendir($dir)){
				while(($file = readdir($dh)) !== false){
					if($file!="." && $file!=".."){
						$res = imagecreatefromgif($dir.'/'.$file);  
						$img_w = imagesx($res);       //图像宽度
						$img_h = imagesy($res);
						//根据图片二值化生成数组
						for($i=0;$i < $img_h;$i++){
							for($j=0; $j < $img_w;$j++){
								$rgb = imagecolorat($res,$j,$i);
								$rgbarray = imagecolorsforindex($res, $rgb);
								if($rgbarray['red']+$rgbarray['green']<50){
									$data_array[$i][$j] = 1;  //验证码部分为1
								}else{
									$data_array[$i][$j] = 0;  //空白为0
								}
							}
						}
						imagedestroy($res);
						//清理噪点
						// $count_num = 0;
						// while($count_num<1){    //执行count_num轮，一般1轮就行
							for($i=0; $i < $img_h; $i++){
								for($j=0; $j < $img_w;$j++){
									$num = 0;
									if($data_array[$i][$j] == 1){
										$num += $data_array[$i-1][$j];                    // 上
										$num += $data_array[$i+1][$j];                  // 下
										$num += $data_array[$i][$j-1];                  // 左
										$num += $data_array[$i][$j+1];                  // 右
										$num += $data_array[$i-1][$j-1];                    // 上左
										$num += $data_array[$i-1][$j+1];                    // 上右
										$num += $data_array[$i+1][$j-1];                    // 下左
										$num += $data_array[$i+1][$j+1];                    // 下右
										if($num < 2)$data_array[$i][$j] = 0;
									}
								}
							}
							// ++$count_num;
						// }
						
						//去掉上下空白
						for($h=0;$h<21;$h++){
							$tmp =0;
							for($w=0;$w<12;$w++){
								$tmp += $data_array[$h][$w];
							}
							if($tmp==0){
								unset($data_array[$h]);
							}else break;
						}
						for($h=20;$h>=0;$h--){
							$tmp =0;
							for($w=0;$w<12;$w++){
								$tmp += $data_array[$h][$w];
							}
							if($tmp==0){
								unset($data_array[$h]);
							}else break;
						}
						$data_array = array_merge($data_array);
						
						//去掉左右空白
							$img_h = count($data_array);
							for($w=0;$w<12;$w++){
								$tmp = 0;
								for($h=0;$h<$img_h;$h++){
									$tmp += $data_array[$h][$w];
								}
								if($tmp==0){
									for($h=0;$h<$img_h;$h++){
										unset($data_array[$h][$w]);
									}
								}else break;
							}
							for($w=11;$w>=0;$w--){
								$tmp = 0;
								for($h=0;$h<$img_h;$h++){
									$tmp += $data_array[$h][$w];
								}
								if($tmp==0){
									for($h=0;$h<$img_h;$h++){
										unset($data_array[$h][$w]);
									}
								}else break;
							}
							for($h=0;$h<$img_h;$h++){
								$data_array[$h] = array_merge($data_array[$h]);
							}
						
						//根据数组生成字符串
						$wordGet = '';
						for($i=0;$i < $img_h;$i++){
							for($j=0; $j < $img_w;$j++){
								$wordGet .= $data_array[$i][$j];
							}
							$wordGet .= '*';
						}
						if(in_array($wordGet,$wordsArr)){
							$filesStr .= $file.'|';
						}else{
							$wordsArr[] = $wordGet;
							echo $wordGet.'<br/>';
						}
					}
				}
			}
			closedir($dh);
		}
		//输出多余图片名称
		return $filesStr;
	}
	//根据图片字典生成Json字典
	public function getWords(){
		//字典图片所在目录
		$dir = 'words';
		//遍历目录下图片
		if(is_dir($dir)){
			if($dh = opendir($dir)){
				while(($file = readdir($dh)) !== false){
					if($file!="." && $file!=".."){
						//获取字符名
						$word = substr($file,0,1);
						//设置数组下标
						if(!isset($wordCheck)){
							$wordCheck = $word;
							$wordNum = 0;
						}
						if($wordCheck!=$word){
							$wordCheck = $word;
							$wordNum = 0;
						}
						$res = imagecreatefromgif($dir.'/'.$file);  
						$img_w = imagesx($res);       //图像宽度
						$img_h = imagesy($res);
						//根据图片二值化生成数组
						for($i=0;$i < $img_h;$i++){
							for($j=0; $j < $img_w;$j++){
								$rgb = imagecolorat($res,$j,$i);
								$rgbarray = imagecolorsforindex($res, $rgb);
								if($rgbarray['red']+$rgbarray['green']<50){
									$data_array[$i][$j] = 1;  //验证码部分为1
								}else{
									$data_array[$i][$j] = 0;  //空白为0
								}
							}
						}
						imagedestroy($res);
						//清理噪点
						// $count_num = 0;
						// while($count_num<1){    //执行count_num轮，一般1轮就行
							for($i=0; $i < $img_h; $i++){
								for($j=0; $j < $img_w;$j++){
									$num = 0;
									if($data_array[$i][$j] == 1){
										$num += $data_array[$i-1][$j];                    // 上
										$num += $data_array[$i+1][$j];                  // 下
										$num += $data_array[$i][$j-1];                  // 左
										$num += $data_array[$i][$j+1];                  // 右
										$num += $data_array[$i-1][$j-1];                    // 上左
										$num += $data_array[$i-1][$j+1];                    // 上右
										$num += $data_array[$i+1][$j-1];                    // 下左
										$num += $data_array[$i+1][$j+1];                    // 下右
										if($num < 2)$data_array[$i][$j] = 0;
									}
								}
							}
							// ++$count_num;
						// }
						
						//去掉上下空白
						for($h=0;$h<21;$h++){
							$tmp =0;
							for($w=0;$w<12;$w++){
								$tmp += $data_array[$h][$w];
							}
							if($tmp==0){
								unset($data_array[$h]);
							}else break;
						}
						for($h=20;$h>=0;$h--){
							$tmp =0;
							for($w=0;$w<12;$w++){
								$tmp += $data_array[$h][$w];
							}
							if($tmp==0){
								unset($data_array[$h]);
							}else break;
						}
						$data_array = array_merge($data_array);
						
						//去掉左右空白
							$img_h = count($data_array);
							for($w=0;$w<12;$w++){
								$tmp = 0;
								for($h=0;$h<$img_h;$h++){
									$tmp += $data_array[$h][$w];
								}
								if($tmp==0){
									for($h=0;$h<$img_h;$h++){
										unset($data_array[$h][$w]);
									}
								}else break;
							}
							for($w=11;$w>=0;$w--){
								$tmp = 0;
								for($h=0;$h<$img_h;$h++){
									$tmp += $data_array[$h][$w];
								}
								if($tmp==0){
									for($h=0;$h<$img_h;$h++){
										unset($data_array[$h][$w]);
									}
								}else break;
							}
							for($h=0;$h<$img_h;$h++){
								$data_array[$h] = array_merge($data_array[$h]);
							}
						
						//根据数组生成字符串
						$wordGet[$word][$wordNum] = '';
						for($i=0;$i < $img_h;$i++){
							for($j=0; $j < $img_w;$j++){
								$wordGet[$word][$wordNum] .= $data_array[$i][$j];
							}
							//增加不相同因素
							$wordGet[$word][$wordNum] .= '*';
						}
						++$wordNum;
					}
				}
			}
			closedir($dh);
		}
		//字符串保存到json文件
		file_put_contents('words.json',json_encode($wordGet));
		return 'done!';
	}
	public function identify(){
		$res = imagecreatefromgif($this->CheckCodeUrl);	 //创建图像     
		$img_w = imagesx($res)-1;	   //图像宽度
		$img_h = imagesy($res)-1;		//图像高度
		
		//在文件目录下生成当前识别的图片，测试使用，正式使用把下面这两行注释
		// imagegif($res,'image.gif');
		// echo '<img src="image.gif"/><br/>';
		
		//二值化
		for($i=1,$_i=0;$i < $img_h;$_i++,$i++){
			for($j=5,$_j=0; $j < $img_w;$_j++,$j++){
				$rgb = imagecolorat($res,$j,$i);
				$rgbarray = imagecolorsforindex($res, $rgb);
				if($rgbarray['red']+$rgbarray['green']<50){
					$data_array[$_i][$_j] = 1;  //验证码部分为1
				}else{
					$data_array[$_i][$_j] = 0;  //空白为0
				}
			}
		}
		imagedestroy($res);
		
		//去掉下面5行和右面23行
		$img_h -= 5;
		$img_w -= 23;
		
		//清除噪点
		// $count_num = 0;
		// while($count_num<1){	//执行count_num轮，一般1轮就行
			for($i=0; $i < $img_h; $i++){
				for($j=0; $j < $img_w;$j++){
					$num = 0;
					if($data_array[$i][$j] == 1){
						$num += $data_array[$i-1][$j];					// 上
						$num += $data_array[$i+1][$j];				  // 下
						$num += $data_array[$i][$j-1];				  // 左
						$num += $data_array[$i][$j+1];				  // 右
						$num += $data_array[$i-1][$j-1];					// 上左
						$num += $data_array[$i-1][$j+1];					// 上右
						$num += $data_array[$i+1][$j-1];					// 下左
						$num += $data_array[$i+1][$j+1];					// 下右

						if($num < 2)$data_array[$i][$j] = 0;
					}
				}
			}
			// ++$count_num;
		// }
			
		// 切割 0-11 12-23 24-35 36-47
		for($i=0;$i<$img_w;$i++){
			for($j=0;$j<$img_h;$j++){
				if($i>35){
					$Vword[3][$j][$i-36] = $data_array[$j][$i];
				}else if($i>23){
					$Vword[2][$j][$i-24] = $data_array[$j][$i];
				}else if($i>11){
					$Vword[1][$j][$i-12] = $data_array[$j][$i];
				}else{
					$Vword[0][$j][$i] = $data_array[$j][$i];
				}
			}
		}
		//去掉上下空白
		for($i=0;$i<4;$i++){
			for($h=0;$h<21;$h++){
				$tmp =0;
				for($w=0;$w<12;$w++){
					$tmp += $Vword[$i][$h][$w];
				}
				if($tmp==0){
					unset($Vword[$i][$h]);
				}else break;
			}
			for($h=20;$h>=0;$h--){
				$tmp =0;
				for($w=0;$w<12;$w++){
					$tmp += $Vword[$i][$h][$w];
				}
				if($tmp==0){
					unset($Vword[$i][$h]);
				}else break;
			}
			$Vword[$i] = array_merge($Vword[$i]);
		}
		//去掉左右空白
		for($i=0;$i<4;$i++){
			$img_h = count($Vword[$i]);
			for($w=0;$w<12;$w++){
				$tmp = 0;
				for($h=0;$h<$img_h;$h++){
					$tmp += $Vword[$i][$h][$w];
				}
				if($tmp==0){
					for($h=0;$h<$img_h;$h++){
						unset($Vword[$i][$h][$w]);
					}
				}else break;
			}
			for($w=11;$w>=0;$w--){
				$tmp = 0;
				for($h=0;$h<$img_h;$h++){
					$tmp += $Vword[$i][$h][$w];
				}
				if($tmp==0){
					for($h=0;$h<$img_h;$h++){
						unset($Vword[$i][$h][$w]);
					}
				}else break;
			}
			for($h=0;$h<$img_h;$h++){
				$Vword[$i][$h] = array_merge($Vword[$i][$h]);
			}
		}
		//拼接为字符串
		for($num=0;$num<4;$num++){
			$wordGet[$num] = '';
			$img_h = count($Vword[$num]);
			$img_w = count($Vword[$num][0]);
			 for($i=0;$i < $img_h;$i++){
				for($j=0; $j < $img_w;$j++){
					$wordGet[$num] .= $Vword[$num][$i][$j];
				}
				//增加不相同因素
				$wordGet[$num] .= '*';
			}
		}
		//读取json数据，使用4个线程识别验证码
		$words = json_decode(file_get_contents('words.json'));
		$thread[] = new IdentifyThread($wordGet[0],$words);  
		$thread[] = new IdentifyThread($wordGet[1],$words);  
		$thread[] = new IdentifyThread($wordGet[2],$words);  
		$thread[] = new IdentifyThread($wordGet[3],$words);  
		foreach($thread as $value){
			$value->start();
		}
		//等待线程结束
		while(true){
			$state = false;
			for($i=0;$i<4;$i++){
				if($thread[$i]->state){
					$state = true;
				}
			}
			if($state==false) break;
		}
		unset($thread);
		return $thread[0]->str.$thread[1]->str.$thread[2]->str.$thread[3]->str;
	}
}

//多线程类
class IdentifyThread extends Thread {
	public $state;
	public $str;
	public $words;
	public function __construct($string,$words){  
		$this->str = $string;
		$this->words = $words;
		$this->state = true;  
	}  
	public function run(){
		foreach($this->words as $key=>$value){
			foreach($value as $v){
				similar_text($this->str,$v,$similarResult);
				if($similarResult>$result['similar']){
					$result['similar'] = $similarResult;
					$result['str'] = $key;
				}
			}
		}
		$this->str = $result['str'];
		$this->state = false;
	}  
}
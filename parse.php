<?php
//日本地図を取得して関係ありそうなところのピクセル位置情報の配列を生成するやつ
//map.phpが生成されるよ！

$im = imagecreatefrompng('http://realtime-earthquake-monitor.bosai.go.jp/static/images/base_map.png');
$width = imagesx($im) - 1;
$height = imagesy($im) - 1;

$map = array();

$base = array(0,0,102);
$black = array(0,0,0);
for($y=0;$y++<$height;){
	for($x=0;$x++<$width;){
		$rgb = imagecolorat($im, $x, $y);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		$rgb = array($r, $g, $b);
		if($rgb == $base || $rgb == $black) continue;
		$map[] = array($x, $y);
	}
}
file_put_contents('map.php', '<?php return ' . str_replace(',),', '),', preg_replace('/[0-9]+=>/', '', str_replace(array(PHP_EOL, ' '), '', var_export($map, true)))) . ';');
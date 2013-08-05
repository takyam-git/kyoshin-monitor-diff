<?php
class Diff
{
	protected $map = null;
	protected $mongo = null;
	protected $db = null;

	protected $before = array();

	public function __construct(array $map)
	{
		$this->map = $map;
		$this->mongo = new MongoClient();
		$this->db = $this->mongo->selectDB('kyoshin');
	}

	protected function get_url($datetime = null)
	{
		if(is_null($datetime)){
			$target_time = time() - 10;
			$target_time % 2 !== 0 and $target_time -= 1;
			$datetime = date('YmdHis', $target_time);
		}
		return "http://realtime-earthquake-monitor.bosai.go.jp/realtimeimage/acmap_s/{$datetime}.acmap_s.gif";
	}

	public function reload()
	{
		//取得対象日時を生成
		$target_time = time() - 10;
		$target_time % 2 !== 0 and $target_time -= 1;
		$datetime = date('YmdHis', $target_time);

		//URLを取得
		$url = $this->get_url($datetime);

		echo $url . ' start...' . PHP_EOL;

		//画像を取得
		$gif = @imagecreatefromgif($url);
		if (!$gif) {
			return false;
		}

		//現在のピクセル配列を取得
		$current = array();
		foreach ($this->map as $pos) {
			$rgb = imagecolorat($gif, $pos[0], $pos[1]);
			$current[] = str_pad(($rgb >> 16) & 0xFF, 3, '0') .
				str_pad(($rgb >> 8) & 0xFF, 3, '0') .
				str_pad($rgb & 0xFF, 3, '0');
		}

		//最初の1回めなら何もしない
		if (count($this->before) === 0) {
			$this->before = $current;
			return true;
		}

		//2回め以降ならDIFFを取得してMongoに突っ込む
		$diff = array_diff($this->before, $current);
		$row = array(
			'datetime' => date('Y-m-d H:i:s', $target_time),
			'has_diff' => count($diff) > 0,
			'diffs' => array(),
		);
		foreach ($diff as $key => $value) {
			$row['diffs'][] = array(
				'x' => $this->map[$key][0],
				'y' => $this->map[$key][1],
				'color' => $value,
			);
		}
		$this->db->diff->insert($row);

		//beforeを更新
		$this->before = $current;
	}
}

//ありそうなピクセル情報を取得
$map = include('map.php');
$diff = new Diff($map);

//無限るうううううぷ
while (1) {
	$diff->reload();
	sleep(2);
}
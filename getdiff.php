<?php
class Diff
{
	protected $map = null;
	protected $mongo = null;
	protected $db = null;

	protected $before = null;

	/**
	 * @param array $map
	 */
	public function __construct(array $map)
	{
		$this->map = $map;
		$this->mongo = new MongoClient();
		$this->db = $this->mongo->selectDB('earthquake_monitor');
	}

	/**
	 * @param null $datetime
	 * @return string
	 */
	protected function get_url($datetime = null)
	{
		if (is_null($datetime)) {
			$target_time = time() - 10;
			$target_time % 2 !== 0 and $target_time -= 1;
			$datetime = date('YmdHis', $target_time);
		}
		return "http://realtime-earthquake-monitor.bosai.go.jp/realtimeimage/acmap_s/{$datetime}.acmap_s.gif";
	}

	/**
	 * @param bool $is_base
	 * @return bool
	 */
	public function reload()
	{
		//取得対象日時を生成
		$target_time = time() - 5; //現時刻の画像があるかわからないので5秒前
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
		$has_diff = false;
		$current = array();
		foreach ($this->map as $key => $pos) {
			//カラーパレットのインデックスから色情報を取得
			$color_index = imagecolorat($gif, $pos[0], $pos[1]);
			$color = imagecolorsforindex($gif, $color_index);

			//透明な場合はスキップ
			if($color['red'] === 0 && $color['green'] === 0 && $color['blue'] === 0 && $color['alpha'] > 0){
				//beforeが透明じゃなければdiffがあるとする
				if(!$has_diff && $this->before && is_array($this->before) && isset($this->before[$key])){
					$has_diff = true;
				}
				continue;
			}

			//10進数のカラーコードを16進数のカラーコードに変換して格納
			$color['rgba'] = str_pad(dechex($color['red']), 2, '0', STR_PAD_LEFT) .
							str_pad(dechex($color['green']), 2, '0', STR_PAD_LEFT) .
							str_pad(dechex($color['blue']), 2, '0', STR_PAD_LEFT) .
							str_pad(dechex($color['alpha']), 2, '0', STR_PAD_LEFT);

			//位置情報を格納
			$color['x'] = $pos[0];
			$color['y'] = $pos[1];

			//データセットに追加
			$current[$key] = $color;

			//差分の有無を確認する
			if(!$has_diff && $this->before && is_array($this->before)){
				if(isset($this->before[$key]['rgba']) && $this->before[$key]['rgba'] !== $color['rgba']){
					$has_diff = true;
				}
			}
		}

		//Mongoに突っ込む
		$this->db->diff->batchInsert(array(
			'datetime' => date('Y-m-d H:i:s', $target_time),
			'has_diff' => $has_diff,
			'pixels' => $current,
		));

		//beforeを更新
		$this->before = $current;
	}
}

//ありそうなピクセル情報を取得
$map = include('map.php');
$diff = new Diff($map);

//無限るうううううぷ
while (true) {
	$diff->reload();
	sleep(2);
}
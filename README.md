#強震モニタのDIFFとる何か
某強震モニタの差分をひたすら取得するための何か。
http://realtime-earthquake-monitor.bosai.go.jp/
大体2秒に1回更新されるので、ピクセルのDIFFだけどひたすらMongoに突っ込む。
何に使うのかは後で考えればいいんじゃないかな。

## requirement
* pecl mongo ( $ pecl install mongo )
* mongodb
* GD

## Usage
1. (re-generate map.php if you need) ``$ php parse.php``
1. ``$ php getdiff.php``

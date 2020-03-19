# i2020zoom
zoom utils for interaction　2020
インタラクション2020で使った、zoom会議室一覧と、発表者へのメール送信/リンク更新用発表者用フォーム/リンクつきプログラム表示のphpスクリプト達です。

## ファイルと機能
- ./data		発表者一覧(presens.csv) リンク(links.csv) などデータファイルの置き場
- email.php		全発表者にemailを送るためのフォーム
- form.php		発表者がリンク先とコメントを更新するためのフォーム
- rooms.php		zoomの会議室に今居る人を一覧表示するプログラム。zoomEvent.phpの記録を読んで更新する。
- zoomEvent.inc zoomEvent.php		zoom会議室への入室/退出が合ったときに来る webhook を受けて記録するプログラム。 https://marketplace.zoom.us/ でwebhookのみのアプリを作って、webhookが飛ぶように設定する必要がある。
- zoom*.php		zoomのログを記録したり、まとめて設定を変えたりするためのプログラム。https://marketplace.zoom.us/で JWT(JSON Web Token)を作って、貼り付ける必要がある。
## ブランチ
master は当日使ったもの。
csvFixed は、csvファイルの扱いや、formでのhtml特殊文字の扱いを修正したものです。当日使ったものはあまりきれいでないので。

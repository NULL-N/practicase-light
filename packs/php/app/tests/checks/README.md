# tests/checks/ — 課題別テストの置き場

`tools/check.php <課題ID>` の **[2/3] 課題別テスト**は、このディレクトリの
`<課題ID>.php`(例: `T-001.php`)を自動で発見して実行する。

- ファイルが存在しない課題は「自動テストなし」としてスキップされる(レビュー・報告系の課題など)
- 単体で実行する場合: `docker compose exec app php packs/php/app/tests/run.php checks/T-001.php`
- 書き方は `tests/*Test.php` と同じ(`test()` + assert ヘルパ)

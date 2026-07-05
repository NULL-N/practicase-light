# T-018 仕様メモ

参照する設計資料・コード:

- `packs/php/app/src/Service/AuthService.php`(`attempt()`。`status !== 'active'` は
  パスワード検証より前に弾かれる=停止中はパスワードが合っていてもログイン不可)
- `docs/01_設計資料/database.md` の `users` テーブル(`role`: `engineer`/`client`/`admin`、
  `status`: `active`/`suspended`)

必要な条件:

- `packs/php/sql/T-018.sql` は `users` を `email = 'tabuchi@example.com'` で絞り込む
- 列名は `id`, `email`, `role`, `status`
- `reports/T-018_login_investigation.md`(`docs/templates/inquiry_investigation_report.md`
  が土台)に、事象・確認したこと・原因・影響範囲・対応方針・再発防止の6点を書く
- 報告書には `users` / `AuthService` / `suspended`(または「停止」)という語も書く
  (原因調査の的が外れていないかの裏付け)
- コード修正は行わない(scope は `packs/php/sql/T-018.sql` のみ)

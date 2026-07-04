# T-014 仕様メモ

参照する設計資料:

- `docs/01_設計資料/database.md` の `projects` テーブル

この課題ではアプリの PHP コードは変更しません。
DB から必要な行と列を取り出す SQL を書く練習です。

必要な条件:

- 対象テーブルは `projects`
- `status` が `open` の行だけを対象にする
- 返す列は `title`, `deadline`, `hourly_rate`
- 並び順は `deadline` の昇順

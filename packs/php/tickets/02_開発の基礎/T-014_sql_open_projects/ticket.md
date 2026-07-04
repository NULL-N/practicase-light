---
id: T-014
title: SQLでopen案件を締切順に取り出す
level: 2
track: dev
type: sql
priority: normal
estimated_minutes: 35
role: developer
status: open
scope:
  - "packs/php/sql/T-014.sql"
depends_on:
  - "T-000"
pack: php
---

# T-014: SQLでopen案件を締切順に取り出す

**起票: 早瀬(開発リーダー)**

運用確認で「現在募集中の案件を、締切が近い順に一覧で見たい」と依頼されました。
アプリを直す課題ではありません。DB の中身を確認するための SQL を1本書いてください。

## この課題について

初めての SQL 課題です。
`SELECT` / `FROM` / `WHERE` / `ORDER BY` だけで解けます。

## やること

1. status を `in_progress` にする
2. `docs/01_設計資料/database.md` で `projects` テーブルの列を確認する
3. `packs/php/sql/T-014.sql` に SQL を書く
4. check を実行する

```text
docker compose exec app php tools/check.php T-014
```

## 出したい列

| 列名 | 意味 |
|---|---|
| `title` | 案件名 |
| `deadline` | 応募締切日 |
| `hourly_rate` | 時間単価 |

条件:

- `status = 'open'` の案件だけ
- `deadline` が早い順

## 完了条件

- `check T-014` が PASS
- `reports/` の振り返りに「`WHERE` と `ORDER BY` がそれぞれ何をしているか」を1行で書く

> 詰まったら `support/hints.md` を1段ずつ開いてください。

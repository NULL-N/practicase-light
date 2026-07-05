---
id: T-016
title: SQLで案件ごとの応募状況を集計する
level: 3
track: dev
type: sql
priority: normal
estimated_minutes: 60
role: developer
status: open
scope:
  - "packs/php/sql/T-016.sql"
depends_on:
  - "T-000"
pack: php
---

# T-016: SQLで案件ごとの応募状況を集計する

**起票: 早瀬(開発リーダー)**

クライアント向けの運用確認として、案件ごとの応募数と承認済み応募数を見たいです。
SQL で集計してください。

## この課題について

SQL 中級の入口です。
`LEFT JOIN` と条件付き集計を使います。

応募が0件の案件も、一覧から落としてはいけません。
ここが T-015 より一段難しいところです。

## やること

1. status を `in_progress` にする
2. `docs/01_設計資料/database.md` で `projects` と `applications` の関係を見る
3. `packs/php/sql/T-016.sql` に SQL を書く
4. check を実行する

```text
docker compose exec app php tools/check.php T-016
```

## 出したい列

| 列名 | 意味 |
|---|---|
| `project_title` | 案件名 |
| `application_count` | 応募数 |
| `accepted_count` | 承認済み応募数 |

条件:

- `projects.status = 'open'` の案件だけ
- 応募が0件の open 案件も出す
- `accepted_count` は `applications.status = 'accepted'` だけを数える
- 案件名順

## 完了条件

- `check T-016` が PASS
- 振り返りに「なぜ `JOIN` ではなく `LEFT JOIN` が必要だったか」を1〜2行で書く

> 詰まったら `support/hints.md` を1段ずつ開いてください。

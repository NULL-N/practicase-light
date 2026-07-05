---
id: T-015
title: SQLで企業ごとの募集中案件数を集計する
level: 2
track: dev
type: sql
priority: normal
estimated_minutes: 45
role: developer
status: open
scope:
  - "packs/php/sql/T-015.sql"
depends_on:
  - "T-000"
pack: php
---

# T-015: SQLで企業ごとの募集中案件数を集計する

**起票: 小野寺(CS)**

営業から「どの企業がいま何件くらい案件を出しているか、ざっくり見たい」と相談がありました。
企業ごとの募集中案件数を SQL で集計してください。

## この課題について

SQL 初級の集計課題です。
`JOIN` / `GROUP BY` / `COUNT` を使います。

## やること

1. status を `in_progress` にする
2. `docs/01_設計資料/database.md` で `companies` と `projects` の関係を見る
3. `packs/php/sql/T-015.sql` に SQL を書く
4. check を実行する

```text
docker compose exec app php tools/check.php T-015
```

## 出したい列

| 列名 | 意味 |
|---|---|
| `company_name` | 企業名 |
| `open_project_count` | その企業の募集中案件数 |

条件:

- `projects.status = 'open'` の案件だけを数える
- open 案件を持つ企業だけを出す
- 件数が多い順。同数なら企業名順

## 完了条件

- `check T-015` が PASS
- 振り返りに「なぜ `companies` と `projects` を JOIN したか」を1行で書く

> 詰まったら `support/hints.md` を1段ずつ開いてください。

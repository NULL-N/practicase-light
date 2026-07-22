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

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`T-014`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-014-sql-open-projects
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-T-014-sql-open-projects`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. Redmineで担当者を自分にし、ステータスを `New` → `In Progress` にする
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

## 提出と完了(共通手順)

この節は、上の課題固有手順を提出までつなぐ補足です。手順1は作業前、手順2以降はcheckがPASSした後に行います。

1. Redmineでこのチケットを開き、担当者を自分にして、見積をコメントし、ステータスを
   `New` → `In Progress`にする(本文ですでに実施している場合は繰り返さない)
2. commit・pushする前に`support/rubric.md`の「提出前」を確認する。満たしていない項目があれば修正し、checkをやり直す
3. 変更をcommit・pushし、Pull Requestを作る。この時点ではまだmergeしない
4. Pull Requestをmergeする
5. `support/debrief/`がある課題は、Pull Requestをmergeした後に開いて自分の提出と突き合わせる。
   突き合わせで見つけた違いは振り返りに記録し、必要な修正は別チケットで扱う
   その結果を振り返りに書く。本文でファイル名の指定がなければ
   `reports/T-014_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/T-014-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

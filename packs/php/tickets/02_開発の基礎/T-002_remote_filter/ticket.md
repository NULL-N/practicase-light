---
id: T-002
title: 案件検索の「リモート可のみ」が絞り込みに効いていない
level: 2
track: dev
type: fix
priority: normal
estimated_minutes: 90
role: developer
status: open
scope:
  - "packs/php/app/src/Repository/ProjectRepository.php"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# T-002: 案件検索の「リモート可のみ」が絞り込みに効いていない

**起票: 早瀬(開発リーダー)**

CS 経由でエンジニア利用者から問い合わせがありました。
案件一覧(S-03)で**「リモート可のみ」にチェックを入れて検索しても、出社前提の案件が表示され続ける**とのことです。

手元でも再現しました: kiryu@example.com でログイン → 案件一覧 → 「リモート可のみ」にチェック → 検索。
「社内勤怠ツールの不具合修正」(リモート不可)が結果に残ります。

## 期待動作

仕様書 `support/spec.md`(正本: features.md **F-03**)の検索条件どおり。
チェック ON なら is_remote = 1 のみ、**OFF のときは絞り込まない**(両方出す)。

## 調査の入口

- 画面の入力は `public/projects/index.php`、検索の実体は Repository にある(ARC-2)
- 「画面 → パラメータ → SQL」の順に、条件がどこまで届いているかを追うこと

> 詰まったら、このフォルダの `support/hints.md` を段階的に。SQL を組み立てるコードの読み方は
> 同じフォルダの `support/code-reading.md`(手本部分の逐行解説)。コード全体の地図は `docs/03_参考資料/code-tour.md`。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`T-002`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-002-remote-filter
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-T-002-remote-filter`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## 完了条件

- フィルタが仕様どおり動く(ON / OFF の両側を確認)
- 修正がテストで固定されている(TEST-1)
- `docker compose exec app php tools/check.php T-002` が PASS
- PR 本文(fix_report)に原因の説明がある

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
   `reports/T-002_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/T-002-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

---
id: T-001
title: 案件登録で不正な値が登録できる
level: 1
track: dev
type: fix
priority: high
estimated_minutes: 60
role: developer
status: open
scope:
  - "packs/php/app/src/Service/ProjectValidator.php"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# T-001: 案件登録で不正な値が登録できる

**起票: 早瀬(開発リーダー)**

社内の受け入れ確認で、案件登録(S-12)に**仕様どおりに弾かれない入力がある**ことが分かりました。
確認できている症状は次の3つです。

- 時間単価にマイナスの値(例: -100)を入れても登録できる
- 募集人数に 0 を入れても登録できる
- 応募締切日に過去の日付を入れても登録できる

## 業務上の問題

不正な案件が公開されると、エンジニア側の検索・応募に実害が出ます(単価 -100 円の案件が並ぶサービスは信用を失います)。
リリース前に直してください。

## 期待動作

仕様書 `support/spec.md`(正本: docs/01_設計資料/features.md **F-02**)の検証ルール表のとおり。

## 調査の入口

- 案件登録の検証は `src/Service/ProjectValidator.php` が担当している(ARC-3)
- 仕様の表と実装を**1項目ずつ**突き合わせること。症状として報告された3つ以外に漏れがないかも確認する

> **初めてのチケットの人へ**: このチケット本文は実務そのままの書き方です。進め方の完全ガイドが
> `docs/00_はじめに/first-ticket-walkthrough.md` にあります(印刷または別ウィンドウで開き、VS Code は作業に集中する使い方を推奨。
> 印刷用 HTML 版あり)。詰まったらこのフォルダの `support/hints.md` を段階的に。
> コードの読み方が分からないときは、このフォルダの `support/code-reading.md`(既存コードの逐行解説)。コード全体の地図は `docs/03_参考資料/code-tour.md`。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`T-001`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-001-job-validation
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-T-001-job-validation`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## 完了条件

- 仕様の検証ルールがすべて実装されている
- 修正内容がテストで固定されている(バグ再現 → 修正 → green。TEST-1)
- `docker compose exec app php tools/check.php T-001` が PASS
- Pull Request の本文が fix_report テンプレートで書かれている

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
   `reports/T-001_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/T-001-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

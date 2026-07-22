---
id: T-005
title: 案件詳細に「残り応募日数」を表示したい
level: 2
track: dev
type: feature
priority: normal
estimated_minutes: 120
role: developer
status: open
scope:
  - "packs/php/app/src/Support/RemainingDays.php"
  - "packs/php/app/public/projects/show.php"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# T-005: 案件詳細に「残り応募日数」を表示したい

**起票: 早瀬(開発リーダー)**

クライアント各社との定例で、モクレン商事の田淵さんから要望がありました。
「締切の日付だけだと急ぎ具合が伝わらない。**あと何日で締め切られるのか**を案件ページに出してほしい」とのことです。
運営側でも採用が決まったので、小さな仕様追加として対応してください。

## 要求

案件詳細(S-04)の応募締切の欄に、締切日に加えて**残り日数の表示**を追加する。
表示の正確なルールは `support/spec.md` に定義した。

## 設計上の指定

- 残り日数の計算・文言化は **`App\Support\RemainingDays::label(string $deadline): string`** を新設して行うこと
  (画面に計算ロジックを埋め込まない。ARC-1 / テスト可能にするため)
- 基準日は**システム日付**とする。自動テストでは Clock の基準日固定機能を使うこと(ARC-5)

> 詰まったら、このフォルダの `support/hints.md` を段階的に。新機能でも「書く前に読む」— 使う道具(Clock)と
> 繋ぎ先の画面の読み方は、同じフォルダの `support/code-reading.md`。コード全体の地図は `docs/03_参考資料/code-tour.md`。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`T-005`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-005-remaining-days
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-T-005-remaining-days`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## 完了条件

- support/spec.md の表示ルールをすべて満たす
- RemainingDays がテストで固定されている(境界: 本日・翌日・過去)
- `docker compose exec app php tools/check.php T-005` が PASS
- 既存の表示・機能を壊していない(共通テスト green)

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
   `reports/T-005_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/T-005-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

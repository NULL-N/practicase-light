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

## 完了条件

- support/spec.md の表示ルールをすべて満たす
- RemainingDays がテストで固定されている(境界: 本日・翌日・過去)
- `check T-005` が PASS
- 既存の表示・機能を壊していない(共通テスト green)

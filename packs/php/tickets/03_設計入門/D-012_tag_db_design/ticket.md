---
id: D-012
title: DB設計:タグをどう持つか(tags / project_tags)
level: 2
track: design
type: design
priority: normal
estimated_minutes: 50
role: designer
status: open
scope:
  - "docs/01_設計資料/database.md"
depends_on:
  - "D-011"
pack: php
---

# D-012: DB設計:タグをどう持つか(tags / project_tags)

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 直すのは DB 設計書(docs/01_設計資料/database.md)だけです。

F-20(案件タグ)の基本設計が確定しました。次は、**タグを DB でどう持つか**を設計してください。

早瀬さんの申し送り:

> - 最初に「案件とタグの関係は 1対多 か、多対多 か」を自分の言葉で判定してください。
>   ここを間違えると、後の全部が壊れます
> - テーブル定義の表と ER 図の両方を更新すること。表だけ・図だけの片手落ちは、
>   読む人によって見る場所が違うので事故になります
> - 設計方針 D-1〜D-7(database.md 冒頭)に従うこと。**自分の追加だけ流儀が違う**のが一番読みにくい

## やること(この順で)

1. status を `in_progress` に。見積も先に
2. `support/spec.md` で前提(F-20 の確定内容)を確認する
3. `docs/01_設計資料/database.md` に:
   - `tags` と `project_tags` のテーブル定義を追加(既存の 3.x 節の表の型で)
   - 多対多にした理由を1〜2行で書く
   - ER 図(mermaid)に2テーブルと関係線を追加
4. check:

   ```text
   docker compose exec app php tools/check.php D-012
   ```

5. PR → `support/rubric.md` でセルフレビュー → retrospective → `closed`

## 完了条件

- `check D-012` が PASS(tags / project_tags の定義が存在すること)
- 同じ案件に同じタグを二重に付けられない仕組みが、定義に入っている
- 多対多を選んだ理由が書かれている

> 詰まったら `support/hints.md`。既存の applications(3.4節)の書き方が一番近い手本です。

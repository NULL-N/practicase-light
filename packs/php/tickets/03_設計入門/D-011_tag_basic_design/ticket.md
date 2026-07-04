---
id: D-011
title: 基本設計:案件タグ(F-20)を機能仕様書と画面設計に起こす
level: 2
track: design
type: design
priority: normal
estimated_minutes: 50
role: designer
status: open
scope:
  - "docs/01_設計資料/features.md"
  - "docs/01_設計資料/screens.md"
depends_on:
  - "D-010"
pack: php
---

# D-011: 基本設計:案件タグ(F-20)を機能仕様書と画面設計に起こす

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 直すのは設計書(docs/01_設計資料/)だけです。

D-010 の確認事項に、田淵さんから回答が返ってきました(`support/spec.md` に全文)。
要件は確定です。今度はそれを、**開発チームの共通言語 = 設計書**に起こしてください。

早瀬さんの申し送り:

> - features.md に **F-20(案件タグ)** の節を追加してください。書き方は、既存の F-03 を型にすること
> - screens.md の該当画面(S-03 / S-04 / S-12)にも、タグの扱いを追記してください
> - **エラーメッセージの文言と DB の持ち方は、まだ書かないでください。** それは次の工程
>   (DB設計・詳細設計)の仕事です。基本設計は「何を・誰が・どの画面で・何をしないか」を決める工程です

## やること(この順で)

1. status を `in_progress` に。見積も先に
2. `support/spec.md` で確定要件を読む(D-010 の回答)
3. `docs/01_設計資料/features.md` を開き、F-09 の後に **F-20** の節を追加する
   (概要/付けるルール/絞り込みのルール/やらないこと)
4. `docs/01_設計資料/screens.md` の S-03・S-04・S-12 に、タグの扱いを1行ずつ追記する
5. check:

   ```text
   docker compose exec app php tools/check.php D-011
   ```

6. PR → `support/rubric.md` でセルフレビュー → retrospective → `closed`

## 完了条件

- `check D-011` が PASS(F-20 の節と、画面側への追記が存在すること)
- F-20 に「やらないこと」が明記されている(編集機能・タグ管理画面は今回やらない)
- エラーメッセージの文言・テーブル定義を**書いていない**こと(工程を混ぜない)

> 詰まったら `support/hints.md`。既存の書き方(F-03)の真似から始めるのが近道です。

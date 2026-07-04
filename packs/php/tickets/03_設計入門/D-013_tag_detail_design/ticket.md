---
id: D-013
title: 詳細設計:タグ機能を実装者に渡せる粒度に落とす
level: 3
track: design
type: design
priority: normal
estimated_minutes: 60
role: designer
status: open
scope:
depends_on:
  - "D-012"
pack: php
---

# D-013: 詳細設計:タグ機能を実装者に渡せる粒度に落とす

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 成果物は詳細設計書
> `reports/D-013_detail_design.md` の1枚です。

F-20 の基本設計(D-011)と DB 設計(D-012)は確定しました。最後の設計工程です。
この機能は**別の担当者が実装する**前提で、その人が**一度も質問に来なくて済む**粒度まで
落としてください。

早瀬さんの申し送り:

> - 詳細設計の完成条件は「きれいに書けている」ではなく「**実装者が迷わない**」です。
>   書きながら常に「これを読んだ人は、次にどのファイルを開いて何をするか分かるか?」と自問すること
> - URL・入力値・エラー文言は**表**にすること(F-02 の検証ルール表が手本)
> - どの層(public / Service / Repository)に何を足すかまで割り当てること(ARC-1〜3)

## やること(この順で)

1. status を `in_progress` に。見積も先に
2. `support/spec.md` の節立てに沿って `reports/D-013_detail_design.md` を書く
3. check:

   ```text
   docker compose exec app php tools/check.php D-013
   ```

4. PR → `support/rubric.md` でセルフレビュー → retrospective → `closed`

## 完了条件

- `check D-013` が PASS(設計書の実在と、必須の節がそろっていること)
- 入出力(URL・パラメータ)とエラー文言が表になっている
- Service / Repository への割り当てが書かれている(「どこかでやる」が残っていない)
- テスト観点に境界(0個/3個/4個など)が含まれている

> 詰まったら `support/hints.md`。「基本設計に書いたこと」と「ここで書くこと」の違いに
> 迷ったら、D-011 の spec の線引き表に戻ってください。

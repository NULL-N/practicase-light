---
id: tutorial-2
title: 【チュートリアル2】タグ別の案件数を数える部品を作る
level: 1
track: dev
type: feature
priority: normal
estimated_minutes: 40
role: developer
status: open
scope:
  - "packs/php/app/src/Support/TagSummary.php"
  - "packs/php/app/tests/**"
depends_on:
  - "tutorial"
pack: php
---

# tutorial-2: タグ別の案件数を数える部品を作る

**起票: 早瀬(開発リーダー)**

チュートリアル2つ目は、**少し実務に寄せます** — 今度は「直す」ではなく「**作る**」仕事です。

管理側の画面に「タグ別の案件数」を出す計画が進んでいます。画面は別チケットで作るので、
まず**集計の部品だけ先に**作ってください(部品を先に作って画面を後で繋ぐのは、実務でよくある進め方です)。

## 作るもの

`packs/php/app/src/Support/TagSummary.php` の `countByTag()` の中身。
ファイルは用意してあります(TODO の場所に実装します)。

- 入力: 案件の配列(各案件は `title` と `tags` = タグ名の配列を持つ)
- 出力: **タグ名 => 件数** の配列

```text
入力: [ {title: A, tags: [PHP, SQL]}, {title: B, tags: [PHP]} ]
出力: { PHP: 2, SQL: 1 }
```

## 設計上の指定

- **foreach を2つ重ねて**書くこと(外側で案件を1件ずつ、内側でその案件のタグを1つずつ) —
  「配列の中の配列」を処理する、実務で最頻出の型を身につけるためです

## 仕様は「テスト」に書いてある(この課題の実務ポイント)

正確な仕様書は `packs/php/app/tests/checks/tutorial-2.php` です。
実務では、文章の仕様書よりも**テストコードが仕様**であることがよくあります。
4つの `test(...)` を読めば「何を入れると・何が返るべきか」が全部わかります —
**書き始める前に、まずテストを読んでください。**

## やること(この順で)

1. Redmine でカスタムフィールド `PractiCase Ticket ID` が `tutorial-2` の issue を開く
   - 担当者を自分にして、status を **New → In Progress** にする
   - 前回の実績と比べた見積をコメント(note)へ一言書く
   - Redmine を使う間、この `ticket.md` の front matter は変更しない
2. `support/spec.md` と、**テスト(checks/tutorial-2.php)を読む**
3. `TagSummary.php` の TODO に実装する(迷ったらこのフォルダの `support/hints.md` を1段ずつ)
4. check を回す — **PASS になるまで**が実装です:

   ```text
   docker compose exec app php tools/check.php tutorial-2
   ```

5. `reports/tutorial-2_fix_report.md` に3行報告(作ったもの / 工夫した点 / check の結果)
6. Redmine の issue に `check tutorial-2: PASS` と報告ファイル名をコメントし、status を
   **In Progress → Resolved → Closed** にする — 2周目クリアです

> **Redmine が使えないときだけ**: この `ticket.md` の front matter を `open` →
> `in_progress` → `resolved` → `closed` と更新して進めます。Redmine への自動同期はないため、
> 復旧後に必要な進捗だけ手動で合わせます。

## 完了条件

- `check tutorial-2` が PASS(4つのテスト全部)
- 3行の報告が reports/ にある
- Redmine の issue が Closed になり、PASS結果のコメントが残っている
  (fallback時は front matter が `closed`)

## 完了したら(次の一歩)

チュートリアルはここまでです。次はいよいよ最初の本番課題 **T-001** —
`packs/php/tickets/02_開発の基礎/T-001_job_validation/ticket.md` を開いてください
(ここからは「直す」対象がテスト用の1語ではなく、実際のバリデーションロジックになります)。

> チューター拡張を入れていれば、クエストログの「チュートリアル2」がこの流れを1歩ずつ案内します。

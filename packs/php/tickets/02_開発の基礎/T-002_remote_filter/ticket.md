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

## 完了条件

- フィルタが仕様どおり動く(ON / OFF の両側を確認)
- 修正がテストで固定されている(TEST-1)
- `check T-002` が PASS
- PR 本文(fix_report)に原因の説明がある

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

## 完了条件

- 仕様の検証ルールがすべて実装されている
- 修正内容がテストで固定されている(バグ再現 → 修正 → green。TEST-1)
- `check T-001` が PASS
- Pull Request の本文が fix_report テンプレートで書かれている

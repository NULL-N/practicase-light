# フォルダ地図

この文書は、PractiCase のフォルダ構造を「学習者が見る順番」で整理した地図です。
実務のリポジトリは、目的別にフォルダが分かれていて最初は分かりにくいものです。
PractiCase では構造を単純化しすぎず、代わりにこの地図で迷子を防ぎます。

## まず見る場所

| 場所 | 何があるか | いつ見るか |
|---|---|---|
| `README.md` | 教材全体の説明 | 最初に一度 |
| `LEARN_HERE.md` | 学習者用の入口 | 迷ったとき |
| `docs/00_はじめに/start-to-tutorial-guide.md` | 導入からチュートリアル2完了まで | 最初の1回 |
| `START_HERE.md` | チューター拡張あり/なしの始め方 | 最初の1回 |

## 学習中に触る場所

| 場所 | 何があるか | 触り方 |
|---|---|---|
| `packs/php/tickets/` | 課題。1フォルダ = 1チケット | 各課題の `ticket.md` から読む(補助資料は同フォルダの `support/` に) |
| `packs/php/app/` | PHPアプリ本体 | 課題に指示された範囲だけ読む/直す |
| `packs/php/sql/` | SQL課題の提出ファイル | T-014 で SQL を書く |
| `reports/` | 自分の報告書・振り返り | 課題ごとに `.md` を作る |

## 調査・確認で見る場所

`docs/` は目的別の章フォルダに分かれています。`00_はじめに`(導入ガイド)・`01_設計資料`(設計書一式)・
`02_作業ルール`(運用・規約)・`03_参考資料`(読み物)・`templates`(ひな形)・`assets`(画像)。

| 場所 | 何があるか | いつ見るか |
|---|---|---|
| `docs/01_設計資料/` | 機能仕様書・DB設計・画面遷移図 | チケットや spec が参照したとき |
| `docs/templates/` | 報告書・振り返りのテンプレート | 提出物を書くとき |
| `docs/02_作業ルール/workflow.md` | チケット、ブランチ、PR、status の運用 | T-000後に一読 |
| `docs/02_作業ルール/git-and-pr-guide.md` | GitHub、PR、コンフリクトの手順 | Git操作で迷ったとき |
| `docs/03_参考資料/code-tour.md` | コードの歩き方 | public/src/tests の関係で迷ったとき |

## 使うが、普段は触らない場所

| 場所 | 何があるか | 注意 |
|---|---|---|
| `tools/` | `check.php`、`init-db.php` など | 通常はコマンドとして使うだけ |
| `.github/workflows/` | GitHub Actions | 学習者が編集する場所ではない |
| `extensions/` | VS Code チューター拡張 | インストールするだけ。課題解決では編集しない |
| `.vscode/` | VS Code 用の補助設定 | 必要なときだけ |

## packs/php/ の中身

| 場所 | 役割 |
|---|---|
| `packs/php/tickets/` | 課題本体 |
| `packs/php/app/` | PHPアプリ本体 |
| `packs/php/sql/` | SQL課題の提出ファイル |

## packs/php/app/ の中身

| 場所 | 役割 |
|---|---|
| `public/` | ブラウザから開く画面 |
| `src/` | Service / Repository / Support などのロジック |
| `tests/` | 共通テストと課題別チェック |
| `database/` | スキーマとシードデータ |
| `templates/` | 共通ヘッダーなど |

URL と `public/` は対応します。
例えば `/client/project_new.php` は `packs/php/app/public/client/project_new.php` が入口です。

課題フォルダの中はこの形です。**最初に開くのは常に `ticket.md`**、それ以外は必要になったときに見る補助資料です。

```text
tickets/02_開発の基礎/T-001_job_validation/
  ticket.md   ← 入口(チケット本体)
  support/    ← 補助資料(spec / hints / rubric / code-reading)
```

## 迷ったときの探し方

1. まず `ticket.md` を読む
2. `support/spec.md` が指す設計資料を見る
3. 画面のURLから `public/` の入口を探す
4. `use` や `new` されているクラスを `src/` へ辿る
5. check が落ちたら、FAIL メッセージと `tests/checks/<課題ID>.php` を読む

VS Code では、基本的に **Ctrl+P → ファイル名** が一番速いです。

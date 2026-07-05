# チケット front matter 仕様

各課題の `ticket.md` は、先頭に YAML front matter を持つ。
目的は 3 つ:

1. チェックツール(`tools/check.php`)が課題 ID・scope を機械的に読めること
2. 将来 Redmine 等のチケット管理システムへ変換できること
3. 学習者が status を自分で更新することで、チケット運用(ステータス管理)の練習になること

---

## 1. フィールド定義

| フィールド | 必須 | 型 | 説明 | 例 |
|---|---|---|---|---|
| id | ○ | 文字列 | 課題 ID。`T-` (作業) / `R-` (レビュー) / `D-` (設計) + 3桁。特例として導入課題のみ `tutorial`・`tutorial-2` 等の連番 | `T-001` |
| title | ○ | 文字列 | チケット題名(業務文体) | `案件登録で不正な値が登録できる` |
| level | ○ | 整数 1〜5 | 難度レベル | `1` |
| track | ○(省略時 dev) | 列挙 | **学習トラック(モード)**。`dev` = コードを読む・直す・テストする・レビューする / `design` = 設計書・図・仕様を更新する(コードは書かない)。type(作業種別)とは別の軸(例: `track: design` + `type: design-change`)。将来 `ops` / `review` を追加可能 | `dev` |
| type | ○ | 列挙 | `setup` / `fix` / `feature` / `sql`(SQLを提出物として書く) / `rework`(差し戻し対応) / `review` / `integration-test` / `bug-report` / `refactor` / `test-impl`(テストが無い箇所にテストを書く) / `design-change`(設計書を更新する。コードは書かない) / `design-review`(設計PRをレビューする) / `investigation`(障害・異常を調査し報告書を提出する。ログから調査してコード修正まで行う課題と、DBから調査しSQL+報告書のみを提出する課題の両方がある) / `conflict`(コンフリクトを解消する。Git 操作が学習対象) / `hotfix`(緊急対応。最小の修正で止血し、他の作業と混ぜない) / `release`(リリース作業。ノート・チェックリスト・タグ) / `handover`(引き継ぎ文書の作成。最終課題) / `design`(既存の設計書を読む) / `requirement`(曖昧な要望を整理する) / `system-test`(総合テスト仕様を書く) | `fix` |
| dojo_stage | − | 整数 1〜10 | 特定の設計課題群でのみ使用。1本の題材を10段階で育てる進行順(既存の`level`=全課題共通の絶対難度とは別軸)。対象外のチケットには付けない | `3` |
| priority | ○ | 列挙 | `low` / `normal` / `high` | `high` |
| estimated_minutes | ○ | 整数 | 予定工数(分)。振り返りで実績と比較する | `60` |
| role | ○ | 文字列 | 学習者が演じる役割 | `developer` |
| status | ○ | 列挙 | `open` / `in_progress` / `resolved` / `closed`。**学習者が自分で書き換える** | `open` |
| scope | ○ | 文字列の配列 | 変更を許可するパス(glob)。チェックツールが diff を検査する。type=review 等の非変更系は `[]` | `["packs/php/app/src/Service/**"]` |
| depends_on | − | 文字列の配列 | 先行して完了しているべき課題 ID | `["T-000"]` |
| pack | ○ | 文字列 | 教材パック名 | `php` |

## 1.1 形式の制約

front matter に書けるのは **1階層のスカラー(文字列・数値)と文字列の配列のみ**。
ネストしたマップや複数行値は使わない(チェックツールの簡易パーサが読める範囲に揃える)。
id が `T-000` 形式でない・front matter が壊れている場合、チェックツールが警告を出す。

## 2. status の運用ルール(学習者向け)

| 状態 | いつ変えるか |
|---|---|
| open | 初期状態(配布時) |
| in_progress | 着手した時(ブランチを切った時) |
| resolved | 修正・成果物を提出した時(Pull Request を出した時) |
| closed | セルフレビュー(debrief 突き合わせ)と振り返りまで終えた時 |

status の書き換え自体を作業の一部とする。実務のチケット管理(Redmine 等)では
「ステータスをいつ動かすか」がそのまま報告になる。その練習を Markdown 上で先に行う。

## 3. Redmine 変換マッピング(Phase 2 で使用)

| front matter | Redmine |
|---|---|
| id | チケット番号(参照用にカスタムフィールドへ) |
| title | 題名 |
| type | トラッカー(fix / bug-report → バグ、feature / rework → 機能、review / integration-test / setup → 作業) |
| priority | 優先度(low → 低め、normal → 通常、high → 高め) |
| estimated_minutes | 予定工数(時間に換算: 60分 = 1.0h) |
| status | ステータス(open → 新規、in_progress → 進行中、resolved → 解決、closed → 終了) |

## 4. 完全サンプル

```yaml
---
id: T-001
title: 案件登録で不正な値が登録できる
level: 1
type: fix
priority: high
estimated_minutes: 60
role: developer
status: open
scope:
  - "packs/php/app/src/Service/**"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---
```

## 5. チェックツールとの連携

正式な実行形式(全 OS 共通・make 不要):

```text
docker compose exec app php tools/check.php T-001
```

`make check T=T-001` は make が使える環境向けの補助ショートカット。ドキュメント・チケットで案内する正式手順は上の形式とする。

- `scope`: 提出前チェックが git diff のパスを scope と突き合わせ、範囲外の変更があれば警告する(CHG-1 の機械化)
- `id`: チェック対象のテストスイート選択に使う(課題ごとの合格条件)
- 形式が不正な front matter はチェックツールがエラーにする(制作側のミスも検出する)

# チケット front matter 仕様

各課題の `ticket.md` は、先頭に YAML front matter を持つ。
目的は 3 つ:

1. チェックツール(`tools/check.php`)が課題 ID・scope を機械的に読めること
2. Redmine の初期 issue を seed するための構造化データになること
3. Redmine が使えないときも、front matter の status で学習を継続できること

---

## 1. フィールド定義

| フィールド | 必須 | 型 | 説明 | 例 |
|---|---|---|---|---|
| id | ○ | 文字列 | 課題 ID。`T-` (作業) / `R-` (レビュー) / `D-` (設計) / `C-` (クラウド・外部API連携) + 3桁。特例として導入課題のみ `tutorial`・`tutorial-2` 等の連番 | `T-001` |
| title | ○ | 文字列 | チケット題名(業務文体) | `案件登録で不正な値が登録できる` |
| level | ○ | 整数 1〜5 | 難度レベル | `1` |
| track | ○(省略時 dev) | 列挙 | **学習トラック(モード)**。`dev` = コードを読む・直す・テストする・レビューする / `design` = 設計書・図・仕様を更新する(コードは書かない) / `cloud` = 教材内のローカル外部APIを呼び出す・切り分ける(対象はアプリ本体ではなく外部API)。type(作業種別)とは別の軸(例: `track: design` + `type: design-change`)。将来 `ops` / `review` を追加可能 | `dev` |
| type | ○ | 列挙 | `setup` / `fix` / `feature` / `sql`(SQLを提出物として書く) / `rework`(差し戻し対応) / `review` / `integration-test` / `bug-report` / `refactor` / `test-impl`(テストが無い箇所にテストを書く) / `design-change`(設計書を更新する。コードは書かない) / `design-review`(設計PRをレビューする) / `investigation`(障害・異常を調査し報告書を提出する。ログから調査してコード修正まで行う課題と、DBから調査しSQL+報告書のみを提出する課題の両方がある) / `conflict`(コンフリクトを解消する。Git 操作が学習対象) / `hotfix`(緊急対応。最小の修正で止血し、他の作業と混ぜない) / `release`(リリース作業。ノート・チェックリスト・タグ) / `handover`(引き継ぎ文書の作成。最終課題) / `design`(既存の設計書を読む) / `requirement`(曖昧な要望を整理する) / `system-test`(総合テスト仕様を書く) / `cloud`(外部APIを呼び出し、応答と記録を確認する) | `fix` |
| dojo_stage | − | 整数 1〜10 | 特定の設計課題群でのみ使用。1本の題材を10段階で育てる進行順(既存の`level`=全課題共通の絶対難度とは別軸)。対象外のチケットには付けない | `3` |
| priority | ○ | 列挙 | `low` / `normal` / `high` | `high` |
| estimated_minutes | ○ | 整数 | 予定工数(分)。振り返りで実績と比較する | `60` |
| role | ○ | 文字列 | 学習者が演じる役割 | `developer` |
| status | ○ | 列挙 | `open` / `in_progress` / `resolved` / `closed`。配布時は初期値。通常はRedmineで進捗を操作し、接続不能時のfallbackだけ学習者が書き換える | `open` |
| scope | ○ | 文字列の配列 | 変更を許可するパス(glob)。チェックツールが diff を検査する。type=review 等の非変更系は `[]` | `["packs/php/app/src/Service/**"]` |
| depends_on | − | 文字列の配列 | 先行して完了しているべき課題 ID | `["T-000"]` |
| pack | ○ | 文字列 | 教材パック名 | `php` |

## 1.1 形式の制約

front matter に書けるのは **1階層のスカラー(文字列・数値)と文字列の配列のみ**。
ネストしたマップや複数行値は使わない(チェックツールの簡易パーサが読める範囲に揃える)。
id が `T-000` 形式でない・front matter が壊れている場合、チェックツールが警告を出す。

## 2. status の運用ルール(学習者向け)

通常は Redmine の issue を次の順で進める。`ticket.md` の status は変更しない。

| Redmine | いつ変えるか |
|---|---|
| New | 初期状態(seed直後) |
| In Progress | 担当者と見積を記録し、着手した時 |
| Resolved | checkがPASSし、成果物を提出した時 |
| Closed | セルフレビューと振り返りまで終えた時 |

Redmine が起動しない・接続できない場合だけ、front matter を
`open` → `in_progress` → `resolved` → `closed` と更新する。Redmine と front matter は
自動同期されないため、復旧後に必要な進捗だけ手動で合わせる。

`check.php` は Redmine と front matter の status を合否判定に使わない。進捗管理と
テストのPASS/FAILは独立している。

## 3. Redmine seed マッピング

| front matter | Redmine |
|---|---|
| id | カスタムフィールド「PractiCase Ticket ID」 |
| title | 題名 |
| type | 説明欄の「種別」に記録。トラッカーは全課題で `PractiCase課題` を使用 |
| priority | 優先度(low → 低め、normal → 通常、high → 高め) |
| estimated_minutes | 説明欄の「目安」に分単位で記録。Redmineの予定工数フィールドへは自動反映しない |
| status | 初期ステータス(open → New、in_progress → In Progress、resolved → Resolved、closed → Closed) |

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

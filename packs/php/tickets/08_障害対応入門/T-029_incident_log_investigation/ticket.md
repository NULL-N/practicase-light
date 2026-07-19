---
id: T-029
title: 承認ボタンで500エラーになる障害をログから調査する
level: 3
track: dev
type: investigation
priority: high
estimated_minutes: 60
role: developer
status: open
scope:
  - "reports/T-029.md"
depends_on:
  - "T-018"
pack: php
---

# T-029: 承認ボタンで500エラーになる障害をログから調査する

**起票: 小野寺(CS)経由、志村さん(株式会社アオバ計画)からの緊急問い合わせ**

> 「応募を承認しようとしたら、真っ白なエラー画面(500)になりました。
> 何度か試しましたが同じです。別の応募は普通に承認できたので、
> 何が起きているのか調べてもらえますか。」

## この課題について

T-018 では、DB を SQL で調べて問い合わせに答えました。今回は**エラーログ**が一次資料です。
実務の障害対応で最初にやることは、直すことではありません。**ログから事実を確定すること**です。
いつ・どこで・誰に・何が・何回起きたのか — これが揃って初めて、修正の判断ができます。

この課題では**コードは1行も変更しません**(修正は次の課題の仕事です)。
調査結果を、機械が確認できる形式の報告書にまとめるところまでが仕事です。

## ログの場所

この教材のアプリは、動作ログを1行1イベントで記録しています(書式の説明は `support/spec.md`)。

| ログ | 場所 | 役割 |
|---|---|---|
| **固定ログ**(調査対象・判定の正本) | `support/incident-app-log.txt` | 障害発生時のログを保全したもの。**報告書はこのログから書く** |
| live ログ(任意の操作体験) | `packs/php/app/logs/app.log` | いま自分が画面を操作すると増えていく実ログ |

live ログは体験用です。`docker compose exec app tail -f packs/php/app/logs/app.log` を
開いたままログイン等を操作すると、ログが増える様子を観察できます(check の判定には使いません)。

## やること

1. status を `in_progress` にする
1. 固定ログをターミナルで調査する。まず全体を眺め、次に絞り込む:

```text
docker compose exec app cat "packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt"
docker compose exec app grep "\[error\]" "packs/php/tickets/08_障害対応入門/T-029_incident_log_investigation/support/incident-app-log.txt"
```

1. **調査の順番**(実務の絞り込みの型。詳しくは `support/spec.md`):
   **発生時刻 → どの画面(path)・誰(user) → 何のイベント(event) → request_id で1リクエストずつ追う
   → 例外の発生箇所(at) → 同じエラーの件数**
1. 調べた事実を `reports/T-029.md` に書く(ファイル名はこの名前で固定)
1. check を実行する

```text
docker compose exec app php tools/check.php T-029
```

## 報告書の書き方

`reports/T-029.md` に、次の**契約ブロック**をそのまま貼り付けて、値を埋めてください。
check はこのブロックだけを機械判定します(ブロックの外には、調査メモや時系列など
自由に書いてかまいません)。

```text
<!-- practicase-contract:start -->
{
  "occurred_at": "",
  "path": "",
  "user": "",
  "event": "",
  "source_file": "",
  "source_line": 0,
  "impact_count": 0,
  "direct_cause": "",
  "remediation_plan": ""
}
<!-- practicase-contract:end -->
```

- 上の7項目(`occurred_at`〜`impact_count`)は**固定ログから読み取れる事実**です。
  各項目の定義(どの行のどの値を書くか)は `support/spec.md` にあります
- `direct_cause`(直接原因)と `remediation_plan`(修正方針)は**自分の言葉**で1〜3文。
  機械は「書いてあるか」だけを見ます。内容の質は `support/rubric.md` で自分でレビューしてください

## 自分の環境でも再現してみる(任意)

固定ログと同じ500エラーは、自分の環境でも起こせます。client でログインして応募一覧を開き、
ブラウザの開発者ツール(F12)で承認フォームの `application_id` の value を `99999` の
ような存在しない番号に書き換えてから「承認する」を押してみてください。
真っ白な500画面と、live ログに増える `[error]` の1行 — 志村さんが見たものと同じです。

## 完了条件

- `check T-029` が PASS
- `reports/T-029.md` が提出されている
- コード(`packs/php/app/` 配下)を変更していない

> 詰まったら `support/hints.md` を1段ずつ開いてください。

---
id: T-031
title: 承認画面の500エラーを修正する
level: 3
track: dev
type: fix
priority: high
estimated_minutes: 45
role: developer
status: open
scope:
  - "packs/php/app/public/client/application_decide.php"
depends_on:
  - "T-029"
pack: php
---

# T-031: 承認画面の500エラーを修正する

**起票: 小野寺(CS)— T-029 のあなたの調査報告を受けて**

> 「調査ありがとうございました。原因の場所が特定できたとのことなので、
> 修正をお願いします。志村さんには『修正が入り次第ご連絡します』と伝えてあります。」

## この課題について

T-029 で、あなたはこの障害の事実を確定しました。今回はその調査報告を受けて、
**実際にコードを直します**。調査と修正が別の課題になっているのは、実務の型です —
「何が起きているか」が確定してから、最小の修正を入れます。

まず自分の `reports/T-029.md` を読み直してください。ポイントは2つの場所の区別です:

- **例外が発生した場所**(ログの `at=` — `Flash.php:15`)
- **欠陥がある場所**(ログの `message` の `called in ... application_decide.php on line 40`)

**直すのは欠陥がある場所です。** `Flash.php` には触りません(この課題の scope でも
機械的に禁止されています)。`Flash::error()` は「string を受け取る」という正しい契約を
守っているだけで、悪いのは **null を渡してしまう呼び出し側**です。

## 何が欠けているのか

`packs/php/app/public/client/application_decide.php` を開くと、承認/却下の結果
`$result` を受けたあと、**「該当する応募が見つからない(null)」場合の分岐がありません**。
この画面の仕様では、該当なし・他社の応募・不正な操作値はすべて **404 応答**にします
(`support/spec.md` 参照)。復元する防御はこの3行です:

```php
if ($result === null) {
    abort404(); // D-1 / G-4
}
```

置く場所が重要です: **`match(...)` で `$result` を受けた直後、
成功判定 `if ($result === true)` と `Flash::error(...)` に到達する前**。
後ろに置いても例外は防げません。

## やること

1. Redmineで担当者を自分にし、ステータスを`New` → `In Progress`にする。
   見積もコメントして送信する
2. `reports/T-029.md` の `direct_cause` / `remediation_plan` を読み直す
3. `packs/php/app/public/client/application_decide.php` の該当箇所に null 防御を復元する
4. check を実行する

```text
docker compose exec app php tools/check.php T-031
```

1. 動作でも確認する(回帰確認 — `support/spec.md` の手順)。
   存在しない応募IDへの操作が **404** になり、正常な承認/却下が
   今までどおり動くこと

## 完了条件

- `check T-031` が PASS(T-029 も PASS のままであること)
- `packs/php/app/public/client/application_decide.php` 以外を変更していない
- 振り返りに「例外の発生場所と欠陥の場所がなぜ違ったか」を1〜2行で書く

> 詰まったら `support/hints.md` を1段ずつ開いてください。

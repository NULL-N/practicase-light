---
id: T-018
title: ログインできない問い合わせを切り分ける
level: 3
track: dev
type: investigation
priority: normal
estimated_minutes: 60
role: developer
status: open
scope:
  - "packs/php/sql/T-018.sql"
depends_on:
  - "T-014"
pack: php
---

# T-018: ログインできない問い合わせを切り分ける

**起票: 小野寺(CS)経由、田淵さん(株式会社モクレン商事)からの問い合わせ**

> 「昨日まで入れていたのに、今日は『メールアドレスまたはパスワードが正しくありません』
> としか出ません。パスワードは変えていません。」
> (田淵さんのメールアドレス: `tabuchi@example.com`)

## この課題について

画面のログインエラーは、いつも同じ文言です。実は `AuthService::attempt()` を見ると、
「該当ユーザーがいない」「パスワードが違う」「アカウントが停止中」の**どれであっても
同じ結果を返す**作りになっています。なりすまし対策として、わざと理由を隠しているためです。

つまり、**画面を見ているだけでは原因が分かりません**。原因を特定するには、DB の中身を
直接確認する必要があります。この課題では、コードは直しません。原因を見つけて、
報告するところまでが仕事です。

## やること

1. status を `in_progress` にする
1. `packs/php/app/src/Service/AuthService.php` を開き、ログインが失敗する条件を
   上から順に読む(`if` が3つ並んでいます)。ファイルが見つからないときは、
   VS Code で **Ctrl+P** を押し、`AuthService.php` と入力すると開けます
1. `docs/01_設計資料/database.md` で `users.status`(`active` / `suspended`)を確認する
1. `packs/php/sql/T-018.sql` に、田淵さんのアカウント状態を確認する SQL を書く
1. check を実行する

```text
docker compose exec app php tools/check.php T-018
```

1. `docs/templates/inquiry_investigation_report.md` を土台に
   `reports/T-018_login_investigation.md` を作成する

## SQLの条件

- `users` を `email = 'tabuchi@example.com'` で絞り込む
- 列は `id`, `email`, `role`, `status`

## 報告書の条件

`reports/T-018_login_investigation.md` に、最低限次の6つを書く:

- 事象(問い合わせの内容)
- 確認したこと(何を・どの順で確認したか)
- 原因(`AuthService` の仕様と `users.status` から特定できること)
- 影響範囲(同じ状態の他のユーザーがいないか)
- 対応方針(今後どう対応すべきか。今回は自分では実行しない)
- 再発防止

## 完了条件

- `check T-018` が PASS
- `reports/T-018_login_investigation.md` が提出されている
- コード(`AuthService.php` 等)を変更していない

> 詰まったら `support/hints.md` を1段ずつ開いてください。

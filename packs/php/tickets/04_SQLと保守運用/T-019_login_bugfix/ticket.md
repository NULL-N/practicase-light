---
id: T-019
title: ログインできない不具合を修正する
level: 3
track: dev
type: fix
priority: high
estimated_minutes: 45
role: developer
status: open
scope:
  - "packs/php/app/src/Service/AuthService.php"
depends_on:
  - "T-018"
pack: php
---

# T-019: ログインできない不具合を修正する

**起票: 小野寺(CS)からの報告**

> 「今日、システムにログインできなくなりました。昨日までは普通に使えていました。
> パスワードは変えていません。」(小野寺さん本人・運営ロールのアカウントより)

## この課題について

T-018 は「SQL とコードを読んで原因を調査する」課題でした。今回は一歩進んで、
**見つけた原因のコードを実際に直します**。

今回は T-018 とは別の問い合わせです。田淵さんではなく、**小野寺さん(運営ロール)本人**が
ログインできなくなったという報告です。`AuthService::attempt()` を読んで、原因を探してください。

## やること

1. status を `in_progress` にする
2. `packs/php/app/src/Service/AuthService.php` を開き、`attempt()` の中身を上から順に読む
3. どの条件が、本来通るべきログインを止めてしまっているかを見つける
4. 該当箇所を直す(条件を1つ削除するだけで直ります)
5. check を実行する

```text
docker compose exec app php tools/check.php T-019
```

## 条件

- 運営(`admin`)ロールのユーザーが、正しいメールアドレス・パスワードでログインできること
- 直した後も、次はすべて今までどおり正しく動くこと
  - 正しい資格情報でのログイン(`engineer` / `client` を含む)
  - パスワード不一致は拒否
  - 存在しないメールアドレスは拒否
  - `suspended` なユーザーは拒否(T-018 で確認した仕様)

## 完了条件

- `check T-019` が PASS
- 振り返りに「どこが・なぜ間違っていたか」を1〜2行で書く

> 詰まったら `support/hints.md` を1段ずつ開いてください。

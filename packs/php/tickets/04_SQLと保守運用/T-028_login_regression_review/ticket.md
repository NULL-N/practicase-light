---
id: T-028
title: ログイン修正の差分をレビューする
level: 3
track: dev
type: review
priority: normal
estimated_minutes: 45
role: developer
status: open
scope: []
depends_on:
  - "T-019"
pack: php
---

# T-028: ログイン修正の差分をレビューする

**起票: 早瀬(開発リーダー)**

> 「T-019 の admin ログイン修正、浅葱さんが対応してくれた PR です。マージ前に見てもらえますか。」

## この課題について

T-019 では、自分の手で `AuthService::attempt()` を直しました。今回は逆の立場です。
**別の人(浅葱さん)が書いた「T-019 と同じ不具合を直す PR」をレビューします。**

この PR は、**一見ちゃんと直っているように見えます**。admin はログインできるようになって
います。しかし、実務でいちばん多い事故がここに潜んでいます——
**1つを直したときに、別の正常な動作・安全な条件を巻き込んで壊してしまう**という事故です。

この課題では、コードは直しません。`support/` の PR 説明・差分を読み、
問題を見つけて報告するところまでが仕事です。

## やること

1. status を `in_progress` にする
2. `support/pr-description.md` を読む(何を・なぜ直したと書いてあるか)
3. `support/diff.patch` を読む(実際に適用はしない。読むだけでよい)
4. `support/related-support/spec.md` を参考に、T-018・T-019 で確認した仕様と照らし合わせる
5. 見つけた問題を、`reports/T-028_review.md` にタグを付けてまとめる
6. check を実行する

```text
docker compose exec app php tools/check.php T-028
```

## 報告書の条件

`reports/T-028_review.md` に、見つけた指摘をタグを付けて書く:

- `[bug]` 直っていない・新しく壊れている問題(**2件以上**)
- `[impact]` その問題が実務で起きたときの影響(**1件以上**)
- `[fix]` または「対応方針」 どう直すべきか(**1件以上**)

タグの意味に迷ったら、`support/hints.md` を見てください。

## 完了条件

- `check T-028` が PASS
- `reports/T-028_review.md` が提出されている
- コードは一切変更していない(この課題はレビューのみ)

> 詰まったら `support/hints.md` を1段ずつ開いてください。

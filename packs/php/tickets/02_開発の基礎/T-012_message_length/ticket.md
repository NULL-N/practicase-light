---
id: T-012
title: 応募メッセージの文字数制限が効いていない
level: 2
track: dev
type: fix
priority: normal
estimated_minutes: 45
role: developer
status: open
scope:
  - "packs/php/app/src/Service/**"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# T-012: 応募メッセージの文字数制限が効いていない

**起票: 早瀬(開発リーダー)/ 報告: 三村**

三村くんから報告が上がってきました。原文のまま貼ります:

> 動作確認していて気づいたんですが、応募メッセージに長い文章(コピペで2000文字くらい)を
> 貼っても、普通に応募できちゃいました。仕様だと500文字以内のはずです……
> どこが悪いかまでは見られていません、すみません!

再現と切り分けはできていません。**仕様上の正しい境界がどこか**の確認から、修正まであなたにお願いします。

## この課題について

**T-001 の復習ドリルです。** 手順の細かい案内はもうありません — T-001 で覚えた流れ
(仕様を読む → 境界を決める → 検証がどこにあるべきかを探す → 直す → check)を、自分で再現してください。
場所は前回と違います。でも、探し方は変わりません。

## やること

1. status を `in_progress` に。見積も先に(**T-001 のときの実績と比べてどうか** — ドリルの見積は前回が基準です)
2. `support/spec.md` で仕様の場所を確認 → 境界を自分の言葉で決める(「2000文字くらい」を仕様の数字に直す)
3. 調査 → 修正 → check:

   ```text
   docker compose exec app php tools/check.php T-012
   ```

4. PR を作る(fix_report の型)→ `support/rubric.md` でセルフレビュー → retrospective → `closed` に

## 完了条件

- `check T-012` が PASS(境界の両側と、**DB に書き込まれないこと**まで見ています)
- retrospective に「T-001 と比べて、どこが早くなったか / まだ迷ったか」が書かれている

> 詰まったら `support/hints.md`(今回は3段だけです)。

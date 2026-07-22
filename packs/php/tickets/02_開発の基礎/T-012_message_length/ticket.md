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

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`T-012`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-012-message-length
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-T-012-message-length`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. Redmineで担当者を自分にし、ステータスを `New` → `In Progress` にする。見積もコメントして送信する
   (**T-001 のときの実績と比べてどうか** — ドリルの見積は前回が基準です)
2. `support/spec.md` で仕様の場所を確認 → 境界を自分の言葉で決める(「2000文字くらい」を仕様の数字に直す)
3. 調査 → 修正 → check:

   ```text
   docker compose exec app php tools/check.php T-012
   ```

4. `support/rubric.md`の「提出前」でセルフレビューし、問題があれば修正してcheckをやり直す
5. 変更をcommit・pushし、Pull Requestを作る(fix_reportの型)。この時点ではまだmergeしない
6. Pull Requestをmergeする
7. `reports/T-012_retrospective.md`に振り返りを書く
8. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
9. `support/rubric.md`の「提出後」を確認する

## 完了条件

- `check T-012` が PASS(境界の両側と、**DB に書き込まれないこと**まで見ています)
- retrospective に「T-001 と比べて、どこが早くなったか / まだ迷ったか」が書かれている

> 詰まったら `support/hints.md`(今回は3段だけです)。

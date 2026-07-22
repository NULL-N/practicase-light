---
id: T-013
title: 検索キーワードが案件内容に効いていない
level: 2
track: dev
type: fix
priority: normal
estimated_minutes: 45
role: developer
status: open
scope:
  - "packs/php/app/src/Repository/ProjectRepository.php"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# T-013: 検索キーワードが案件内容に効いていない

**起票: 早瀬(開発リーダー)/ 経由: 小野寺(CS)**

CS 経由でエンジニア利用者(浅葱さん)から問い合わせです:

> 前に見かけた、説明に「月末締め処理」って書いてあった案件にやっぱり応募したくて、
> 「**月末**」で検索したんですが出てきません。もう締め切られたんでしょうか?

小野寺さんが確認したところ、該当しそうな案件「社内勤怠ツールの不具合修正」は **open のまま掲載中**です。
締め切られていないのに検索で出ない — 調査と修正をお願いします。

## この課題について

**T-002 の復習ドリルです。** あのとき使った調査の型(症状 → 画面 → パラメータ → SQL → 仕様と照合)を、
そのまま自分で回してください。同じ画面の、同じメソッドの調査です。ただし今回の症状は逆向き —
T-002 は「絞り込まれない」、今回は「**絞り込まれすぎる**」。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`T-013`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-013-keyword-description
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-T-013-keyword-description`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. Redmineで担当者を自分にし、ステータスを `New` → `In Progress` にする。見積もコメントして送信する
   (T-002 の実績が基準です)
2. ブラウザで再現(kiryu@example.com → 案件一覧 → 「月末」で検索)
3. `support/spec.md` で仕様の場所を確認 → 調査 → 修正 → check:

   ```text
   docker compose exec app php tools/check.php T-013
   ```

4. `support/rubric.md`の「提出前」でセルフレビューし、問題があれば修正してcheckをやり直す
5. 変更をcommit・pushし、Pull Requestを作る。この時点ではまだmergeしない
6. Pull Requestをmergeする
7. `reports/T-013_retrospective.md`に振り返りを書く
8. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
9. `support/rubric.md`の「提出後」を確認する

## 完了条件

- `check T-013` が PASS
- retrospective に「**同じ『条件の欠落』なのに、T-002 と症状が逆になったのはなぜか**」の説明が1〜2行で書かれている
  (これが言えたら、このドリルは満点です)

> 詰まったら `support/hints.md`(今回は3段だけです)。

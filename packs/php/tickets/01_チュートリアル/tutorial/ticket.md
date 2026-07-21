---
id: tutorial
title: 【チュートリアル】案件詳細の項目名が、ほかの画面と違う
level: 1
track: dev
type: fix
priority: normal
estimated_minutes: 30
role: developer
status: open
scope:
  - "packs/php/app/public/projects/show.php"
  - "packs/php/app/tests/**"
depends_on:
  - "T-000"
pack: php
---

# tutorial: 案件詳細の項目名が、ほかの画面と違う

**起票: 早瀬(開発リーダー)**

本番課題に入る前に、2つのチュートリアルで仕事の流れを練習します。
この1本目では、小さな不具合を直して「直す流れ」を一周します。
案件一覧と案件詳細で、同じ情報に異なる項目名が使われています。案件詳細画面の
誤った表記を1語だけ修正してください。

> Redmineでは作業項目を「チケット」と呼びます(英語表記やAPIでは`issue`)。
> 課題の内容はこのファイル、進捗の記録はRedmine、と役割を分けています。
> Redmineを使っている間は、`ticket.md`のfront matterを変更しません。

## チュートリアル表示(この課題だけの特別装備)

```text
http://localhost:8180/login.php?tutorial=1
```

このURLを開くと、画面ごとに「次の1歩」だけが明るく表示され、それ以外は暗くなります。
終了するときは吹き出しの「チュートリアル表示を終了する」を押してください(再開は同じURL)。
VS Codeのチューター拡張(導入: `docs/00_はじめに/setup-guide.md`)を入れていれば、
左のチューターパネルがVS Code側の手順も1歩ずつ案内します。

## やること(この順で)

1. Redmineへログインし、チケットを開く
   - RedmineのPractiCaseプロジェクトで、`PractiCase Ticket ID`が`tutorial`のチケットを開く
   - 担当者を自分にし、ステータスを`New` → `In Progress`にする
   - コメントに「何分で終わりそうか」を一言書き、送信する
   - URLが`/issues/12`なら、チケット番号は`12`。この数字をブランチ名に使う
   - 送信後、Redmineの画面は閉じずにVS Codeへ戻る
2. ターミナルで、mainから作業用ブランチを作る

   ```text
   git switch main
   git switch -c feature/redmine-<チケット番号>-tutorial-label
   ```

   `<チケット番号>`はRedmineのURLで確認した数字へ置き換えます。
   Redmineが使えない場合は、代わりに`feature/tutorial-label`を使います。
3. 上のURLから、**明るい場所をたどって案件詳細まで進み、症状を自分の目で見る** —
   明るい行の項目名を、直前に通った一覧の列名と見比べる
4. `support/spec.md`で、どちらの表記が正しいかを確認する
5. 画面の文字でコードを探す(この課題でいちばん覚えてほしい技):
   - VS Codeで`Ctrl+Shift+F`を押す
     (`F` = Find。`Ctrl+F`は現在のファイル、`Ctrl+Shift+F`は全ファイルを横断して検索します)
   - 画面に見えている誤った方の言葉を、そのまま検索窓へ入力する
   - 結果は2箇所出ます。1つは画面のファイル、もう1つは`tests/checks/`(この課題の合格条件)。
     直すのは画面の方だけです。複数ヒットから変更対象を見分けるところまでが仕事です
6. 1語直して保存し、checkを実行する

   ```text
   docker compose exec app php tools/check.php tutorial
   ```

   `結果: PASS`になるまで直します
7. `reports/tutorial_fix_report.md`を作り、3行だけ書く(何が起きていたか / どこをどう直したか / checkの結果)
8. 変更を確認してcommitする

   ```text
   git status --short
   git add packs/php/app/public/projects/show.php reports/tutorial_fix_report.md
   git commit -m "tutorial: 案件詳細の項目名を修正"
   ```

9. mainへローカルmergeし、作業ブランチを削除する

   ```text
   git switch main
   git merge --no-ff --no-edit feature/redmine-<チケット番号>-tutorial-label
   git branch -d feature/redmine-<チケット番号>-tutorial-label
   git status --short
   ```

   `<チケット番号>`は手順2と同じ数字へ置き換えます。
   fallback時はブランチ名を`feature/tutorial-label`へ読み替えます。
   最後の`git status --short`に何も表示されなければ、次の課題をcleanな状態で始められます。
10. Redmineのチケットへ`check tutorial: PASS`と報告ファイル名をコメントし、
    ステータスを`In Progress` → `Resolved` → `Closed`にする。これで1周目が完了です

> **Redmineが使えないときだけ**: 学習は止めず、この`ticket.md`のfront matterを
> `open` → `in_progress` → `resolved` → `closed`と更新します。
> Redmineへは自動同期されないため、復旧後に必要なら手動で進捗を合わせます。

## この課題でやらないこと

この課題ではPull Requestの作成や、関連資料を最初から読み込む作業はまだ行いません。
ブランチ作成・commit・mainへのローカルmergeまでを練習し、Pull RequestはT-001で扱います。
ここでは「見る → 探す → 直す → check → 報告 → commit → merge → Closed」の
1周目を体で覚えれば十分です。

## 完了条件

- `check tutorial` が PASS
- 3行の報告が`reports/`にある
- 作業内容をcommitし、mainへmergeした
- 作業ブランチを削除し、現在のブランチがmain
- `git status --short`に何も表示されない
- Redmineのチケットが`Closed`になり、PASS結果のコメントが残っている
  (fallback時はfront matterが`closed`)

## 完了したら(次の一歩)

次は2本目のチュートリアルです。
`packs/php/tickets/01_チュートリアル/tutorial-2/ticket.md`を開き、
今度は「作る流れ」を一周します。2本を終えたら、最初の本番課題T-001へ進みます。

詰まったら`support/hints.md`(2段だけ)を開いてください。

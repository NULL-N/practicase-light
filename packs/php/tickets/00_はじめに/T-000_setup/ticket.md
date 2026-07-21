---
id: T-000
title: 開発環境のセットアップと着任報告
level: 1
track: dev
type: setup
priority: high
estimated_minutes: 60
role: developer
status: open
scope: []
depends_on: []
pack: php
---

# T-000: 開発環境のセットアップと着任報告

**起票: NULL-N(Product Owner)**

NULL-N Works へようこそ。PractiCase は、新しい現場へ出るあなたのために作った仮想実務アプリです。

最初の仕事は、PractiCase の開発環境を自分のマシンに用意することから始めましょう。
実務に限りなく寄せた作りにしているので、あなたがしっかりと文章を読まなくてはなりません。

それでは良い体験を!

> Redmineでは作業項目を「チケット」と呼びます(英語表記やAPIでは`issue`)。
> 課題内容はこの`ticket.md`、進捗はRedmine、合否は`check.php`で管理します。

## やること

1. Redmineで`PractiCase Ticket ID`が`T-000`のチケットを開く
   - 担当者を自分にし、ステータスを`New` → `In Progress`にする
   - セットアップに何分かかりそうかをコメントして送信する
   - URLが`/issues/3`ならチケット番号は`3`。この数字をbranch名に使う
2. VS Codeへ戻り、mainから作業用branchを作る

   ```text
   git switch main
   git status --short
   git switch -c feature/redmine-<チケット番号>-T-000-setup
   ```

3. `docs/00_はじめに/setup-guide.md` の手順どおりに環境を作る(**GitHub アカウント作成 → Use this template で自分の
   リポジトリを作る → clone** → 起動 → DB 初期化)。この教材は GitHub の利用を前提とします
4. ブラウザから kiryu@example.com でログインし、案件一覧が表示されることを確認する。
   **確認はそれで十分 — アプリを触り込む必要はない。** このアプリは課題の症状を確認するための
   題材であり、画面の意味はチケットを読んでから初めて生まれる
5. チェックツールを実行し、課題一覧が出ることを確認する
6. **セットアップ完了報告**を書く — `docs/templates/setup_report.md` を
   `reports/T-000_setup_report.md` へコピーして、各見出しを自分の環境の内容で埋める
7. `docker compose exec app php tools/check.php T-000`を実行し、PASSを確認する
8. 報告をcommitし、mainへローカルmergeする

   ```text
   git add reports/T-000_setup_report.md
   git commit -m "T-000: セットアップ完了を報告"
   git switch main
   git merge --no-ff --no-edit feature/redmine-<チケット番号>-T-000-setup
   git branch -d feature/redmine-<チケット番号>-T-000-setup
   git status --short
   ```

9. Redmineへ`check T-000: PASS`と報告ファイル名をコメントして送信し、
   ステータスを`In Progress` → `Resolved` → `Closed`にする

> **Redmineが使えないときだけ**: front matterを`open` → `in_progress` → `resolved` →
> `closed`と更新し、branch名は`feature/T-000-setup`へ読み替えます。
> Redmineへは自動同期されないため、復旧後に必要なら手動で進捗を合わせます。

## 完了報告に書くこと(テンプレートの見出しと対応)

- 環境情報(OS、Docker のバージョン)
- 実施手順と結果(詰まった点があれば、どう解決したかも)
- 動作確認(どのアカウントでログインし、何を見たか)
- 詰まった点と解決(詰まらなかった場合は「特になし」でよい)

## 完了条件

- `docker compose exec app php tools/check.php T-000` が PASS になる
  (= 最初のコミットが存在し、余計な変更がない状態)
- 完了報告が reports/ にある
- 作業内容をcommitしてmainへmergeし、`git status --short`に何も表示されない
- Redmineのチケットが`Closed`で、PASS結果のコメントが残っている
  (fallback時はfront matterが`closed`)

## 完了したら(次の一歩)

`packs/php/tickets/01_チュートリアル/tutorial/ticket.md` を開く。**学習はチケットから始まる。アプリからは始まらない。**
チュートリアル(30分)で「直す流れ」を一周してから、最初の本番課題 T-001 へ。
以降はすべて「チケットを読む → 症状をアプリで再現する → コードを読む → 直す → check → 提出」の順で進む。
アプリ全体を眺めたくなったら、ログイン画面のクイックログインで3ロールを切り替えて一巡してみてもよい(任意)。

## 進め方のヒント

- 以降のチケットも、Redmineで着手 → branch → check → commit/merge → PASS報告 → Closedの順で進める。
  詳細は`docs/02_作業ルール/workflow.md`にまとまっている
- エラーで詰まったら、このフォルダの `support/hints.md`(トラブルシューティング)。環境構築の詰まりは
  恥ではなく、全員が通る道 — 詰まりと解決は、そのまま完了報告のネタになる

# セットアップガイド(T-000 の作業手順)

このページでは、PractiCase for PHP を用意し、最初の課題 **T-000** を完了するまでを説明します。
`tutorial` / `tutorial-2` まで続けて進める場合は、
**[start-to-tutorial-guide.md](start-to-tutorial-guide.md)**(印刷用の
**[HTML 版](start-to-tutorial-guide.html)**)を使ってください。

T-000 の完了条件は次の6つです。

1. 教材を自分の GitHub リポジトリへコピーする
2. アプリ・PCP・Redmineを起動し、ブラウザでログインする
3. Redmineを初期設定し、この版の全課題を投入する
4. RedmineでT-000へ着手し、作業用branchを作る
5. セットアップ結果を報告書にまとめ、T-000のcheckを通す
6. 報告をcommit・mainへmergeし、RedmineのチケットをClosedにする

## 前提(すべて無料)

| 必要なもの | 確認コマンド |
|---|---|
| Git | `git --version` |
| Docker(Docker Desktop または互換環境) | `docker --version` と `docker compose version` |
| VS Code | エディタを起動できること |

Windows / macOS / Linux で同じ手順を使えます。`make` は不要です。

### Gitが入っていない場合

Windowsでは、PowerShellで次のコマンドを実行します。

```text
winget install --id Git.Git -e --source winget
```

`winget`を利用できない場合は、[Git公式のWindows向けページ](https://git-scm.com/install/windows)から
Git for Windowsをインストールします。macOS / Linuxでは、
[Git公式のインストール案内](https://git-scm.com/install/)からOSに合う手順を選んでください。

インストール後はPowerShellまたはターミナルを開き直し、次のコマンドで確認します。

```text
git --version
```

バージョン番号が表示されれば準備完了です。

## 1. 教材を自分のものにする

この教材は、コード・Pull Request・レビュー・CIを実務と同じ流れで体験するため、GitHubの利用を前提とします。

1. [GitHub](https://github.com/signup)の無料アカウントを作る
2. 配布元リポジトリで **Use this template → Create a new repository** を選ぶ
3. 作成した自分のリポジトリを clone する

```text
git clone https://github.com/<自分のアカウント>/<リポジトリ名>.git
```

作成したリポジトリは自分専用です。壊しても配布元には影響しないので、安心して実験できます。
Git の利用者名とメールが未設定なら、最初に設定してください。

```text
git config user.name "自分の名前"
git config user.email "GitHubのメール"
```

GitHub を利用できない場合だけ、後述の「ZIP 方式」を使います。

## 2. VS Code でリポジトリを開く

VS Code の「ファイル → フォルダーを開く」で、`README.md` と `docker-compose.yml` がある
**リポジトリの一番上のフォルダー**を開きます。

- `packs/php` などの下位フォルダーを開くと、`docs/` がファイル検索の対象外になります
- ファイルを探すときは **Ctrl+P → ファイル名を入力** が最短です

## 3. アプリを起動する

> このアプリは学習用に意図的なバグを含みます。VPSやレンタルサーバーへ公開せず、
> 自分のPCの中だけで動かしてください。既定では`127.0.0.1`からだけアクセスできます。

VS Code でターミナルを開き、リポジトリの一番上で実行します。

```text
docker compose --profile redmine up -d
docker compose exec app php tools/init-db.php
```

Redmineを初期設定し、この版に含まれる全課題を投入します。

PowerShell:

```powershell
Get-Content -Raw tools/redmine/bootstrap.rb | docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -'
docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all
```

Git Bash:

```bash
docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -' < tools/redmine/bootstrap.rb
docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all
```

ブラウザで <http://localhost:8180/login.php> を開き、
`docs/03_参考資料/world.md` の利用者名簿にあるアカウント
(例: `kiryu@example.com` / `password123`)でログインできれば起動成功です。

続いて<http://127.0.0.1:8280>を開き、教材専用アカウント
`practicase` / `practicase123`でRedmineへログインします。

ポートが使用中なら、アプリとRedmineの両方を別の番号へ変更します。

```text
# PowerShell
$env:PRACTICASE_PORT='8380'; $env:PRACTICASE_REDMINE_PORT='8480'; docker compose --profile redmine up -d

# bash
PRACTICASE_PORT=8380 PRACTICASE_REDMINE_PORT=8480 docker compose --profile redmine up -d
```

## 4. check を確認し、T-000 を完了する

まず課題一覧を表示します。

```text
docker compose exec app php tools/check.php
```

次に`packs/php/tickets/00_はじめに/T-000_setup/ticket.md`を読み、次の順で進めます。

1. Redmineで`PractiCase Ticket ID`が`T-000`のチケットを開き、担当者・見積・`In Progress`を記録する
2. Redmineのチケット番号を使ってmainから作業用branchを作る
3. `docs/templates/setup_report.md`を`reports/T-000_setup_report.md`としてコピーする
4. テンプレートの4見出しへ、自分の環境で確認した事実を書く
5. T-000のcheckを実行する
6. 報告をcommitし、mainへローカルmergeする
7. RedmineへPASS結果をコメントし、`Resolved` → `Closed`にする

```text
docker compose exec app php tools/check.php T-000
```

テンプレートは必要な報告項目を示すもので、答えを埋めた見本ではありません。
詰まった点がなければ「特になし」と書いて構いません。

T-000が完了したら、`packs/php/tickets/01_チュートリアル/tutorial/ticket.md`を開きます。
以降の進め方は`docs/02_作業ルール/workflow.md`を参照してください。
Redmineの詳しい運用、停止、fallbackは`docs/02_作業ルール/redmine-guide.md`にあります。

## 学習前に選べる設定

以下は必須ではありません。必要なものだけ設定してください。

### AIコード補完を無効にする(推奨)

初回は、自分でチケットを読み、コードを探して直す経験を優先します。
GitHub Copilotなどを使っている場合は、拡張ビュー(Ctrl+Shift+X)で対象を右クリックし、
**無効にする(ワークスペース)**を選びます。
無効化はこのワークスペースだけに適用され、他のプロジェクトには影響しません。
この教材が拡張を勝手に無効化することはありません。

### チューター拡張を入れる(任意)

VS Code内で「次の一歩」を案内する教材専用の拡張が同梱されています。
この拡張は**通信なし・ローカル完結・依存ゼロ**で動作します。

1. Ctrl+Shift+Pで **Extensions: Install from VSIX** を選ぶ
2. `extensions/practicase-tutor/` にある`.vsix`ファイルを選ぶ
3. 左のアクティビティバーにチューターのアイコンが表示されれば完了

インストールは任意です。入れなくても、すべての課題を完走できます。
設計と安全性の詳細は`extensions/practicase-tutor/README.md`を参照してください。

### アプリを自動起動する(任意)

フォルダーを開いたときに「自動タスクを許可しますか?」と表示された場合、許可すると
`.vscode/tasks.json`によりアプリが自動起動します。許可しなくても支障はありません。
終了時は`docker compose stop`を実行してください。

## GitHubを利用できない場合: ZIP方式(非推奨)

ZIPを展開してローカルだけでも進められますが、Pull Request・レビュー・CIを体験できません。
可能な限りGitHub方式を使ってください。ZIPの場合は展開後に、最初のコミットを作ります。

```text
git init -b main
git add .
git commit -m "T-000: セットアップ開始"
```

## 環境の後始末・作り直し

- 停止: `docker compose --profile redmine stop`
- DBを初期状態へ戻す: `docker compose exec app php tools/init-db.php`(何度でも実行可)
- Redmineだけを初期状態へ戻す: `docs/02_作業ルール/redmine-guide.md`のreset手順

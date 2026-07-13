# セットアップガイド(T-000 の作業手順)

このページでは、PractiCase for PHP を用意し、最初の課題 **T-000** を完了するまでを説明します。
`tutorial` / `tutorial-2` まで続けて進める場合は、
**[start-to-tutorial-guide.md](start-to-tutorial-guide.md)**(印刷用の
**[HTML 版](start-to-tutorial-guide.html)**)を使ってください。

T-000 の完了条件は次の4つです。

1. 教材を自分の GitHub リポジトリへコピーする
2. アプリを起動し、ブラウザでログインする
3. check ツールで課題一覧を表示する
4. セットアップ結果を報告書にまとめ、T-000 の check を通す

## 前提(すべて無料)

| 必要なもの | 確認コマンド |
|---|---|
| Git | `git --version` |
| Docker(Docker Desktop または互換環境) | `docker --version` と `docker compose version` |
| VS Code | エディタを起動できること |

Windows / macOS / Linux で同じ手順を使えます。`make` は不要です。

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
docker compose up -d
docker compose exec app php tools/init-db.php
```

ブラウザで <http://localhost:8180/login.php> を開き、
`docs/03_参考資料/world.md` の利用者名簿にあるアカウント
(例: `kiryu@example.com` / `password123`)でログインできれば起動成功です。

ポート`8180`が使用中なら、`PRACTICASE_PORT`を`8280`、`8380`のように**100ずつ**増やして試します。

```text
# PowerShell
$env:PRACTICASE_PORT='8280'; docker compose up -d

# bash
PRACTICASE_PORT=8280 docker compose up -d
```

## 4. check を確認し、T-000 を完了する

まず課題一覧を表示します。

```text
docker compose exec app php tools/check.php
```

次に`packs/php/tickets/00_はじめに/T-000_setup/ticket.md`を読み、次の順で進めます。

1. `docs/templates/setup_report.md` を `reports/T-000_setup_report.md` としてコピーする
2. テンプレートの4見出しへ、自分の環境で確認した事実を書く
3. チケットのstatusを更新する
4. T-000のcheckを実行する

```text
docker compose exec app php tools/check.php T-000
```

テンプレートは必要な報告項目を示すもので、答えを埋めた見本ではありません。
詰まった点がなければ「特になし」と書いて構いません。

T-000が完了したら、`packs/php/tickets/01_チュートリアル/tutorial/ticket.md`を開きます。
以降の進め方は`docs/02_作業ルール/workflow.md`を参照してください。

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

- 停止: `docker compose down`
- DBを初期状態へ戻す: `docker compose exec app php tools/init-db.php`(何度でも実行可)

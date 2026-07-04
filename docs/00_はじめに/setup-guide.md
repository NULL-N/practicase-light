# セットアップガイド(T-000 の作業手順)

PractiCase を自分の環境に用意する。ここでの作業そのものが最初の課題 **T-000** になっている。

導入から `tutorial` / `tutorial-2` 完了までを1枚で進めたい場合は、
先に **[docs/00_はじめに/start-to-tutorial-guide.md](start-to-tutorial-guide.md)** を開いてください。
印刷・PDF保存しやすい HTML 版は **[docs/00_はじめに/start-to-tutorial-guide.html](start-to-tutorial-guide.html)** です。

## 前提(すべて無料)

| 必要なもの | 確認コマンド |
|---|---|
| Git | `git --version` |
| Docker(Docker Desktop または互換環境) | `docker --version` と `docker compose version` |

Windows / macOS / Linux いずれでも手順は同じ。`make` は**不要**(あれば補助として使える)。

## 1. 教材を自分のものにする

**この教材は GitHub の利用を前提とします。** 実務では GitHub(コード・Pull Request・レビュー)と
チケット管理を組み合わせて仕事を進めるため、その現場のツールチェーンをそのまま体験します。

### 標準の手順(GitHub)

1. GitHub アカウントを作る(無料。https://github.com/signup)
2. 配布元リポジトリのページで **「Use this template」→「Create a new repository」**。
   自分のアカウントに、履歴の繋がらない自分専用のコピーができる
3. 作った自分のリポジトリを clone する:

   ```text
   git clone https://github.com/<自分のアカウント>/<リポジトリ名>.git
   ```

**このコピーは完全にあなたのもの**。壊しても配布元には影響しません(実験してよい)。
git の利用者名・メールが未設定なら、先に設定します:
`git config user.name "自分の名前"` / `git config user.email "GitHubのメール"`。

### オフライン時のみ: ZIP 方式(非推奨)

GitHub をどうしても使えない場合に限り、ZIP をダウンロードして展開し、ローカルだけで進められます。
ただし**本物の Pull Request・レビュー・CI を体験できない**ため、この教材の狙いの一部が失われます。
可能な限り GitHub 方式で進めてください。ZIP の場合は展開後に `git init -b main` → `git add .` →
`git commit -m "T-000: セットアップ開始"` で最初のコミット(チェックツールのベースライン)を作ります。

## 1.5 エディタで開く

VS Code の「ファイル → フォルダーを開く」で、**このリポジトリの一番上のフォルダ**
(README.md や docker-compose.yml がある階層)を開いてください。

- 下位フォルダ(packs/php など)を開くと、docs/ がファイル検索(Ctrl+P)の範囲外になり、
  仕様書などが「見つからない」状態になります
- 以降、ファイル移動は **Ctrl+P → ファイル名を入力** が最速です

### (推奨)AI コード補完は無効にして始める

初回学習では、AI コード補完・生成 AI 拡張(GitHub Copilot など)を無効にして進めることを推奨します。
この教材の目的は、あなた自身がチケットを読み、コードを探し、修正し、check で確かめる流れを
体験することです。補完に頼ると、いちばん学びの大きい「自分で探して直す」体験がなくなってしまいます。

無効化はあなた自身の操作で行います: 拡張ビュー(Ctrl+Shift+X)で該当の拡張を右クリック →
「**無効にする(ワークスペース)**」。このフォルダでだけ無効になり、他のプロジェクトには影響しません。
この教材が勝手に拡張を無効化することはありません。

### (任意)チューター拡張 — 最初の課題を画面誘導つきで

VS Code 内で「次の1歩」を案内する教材専用の拡張が同梱されています
(通信なし・ローカル完結・依存ゼロ。設計と安全性の説明: `extensions/practicase-tutor/README.md`)。

1. VS Code で Ctrl+Shift+P → 「**Extensions: Install from VSIX**」と入力して選ぶ
2. `extensions/practicase-tutor/` フォルダにある `.vsix` ファイルを選択
3. 左のアクティビティバーに 🎓 アイコンが増えれば導入完了(tutorial 課題で使います)

入れなくても、すべての課題は従来の手順書だけで完走できます。

### (任意)フォルダを開いたらアプリを自動起動

VS Code でこのフォルダを開いたとき「**自動タスクを許可しますか?**」という通知が出たら、
許可すると**開くたびにアプリが自動で起動**します(`.vscode/tasks.json` の仕組み。許可しなくても支障なし)。
終了だけは自動化できないので、学習を終えるときは `docker compose stop` を実行してください。

## 2. アプリを起動する

> **注意**: このアプリは学習用に**意図的なバグを含んでいます**。インターネットに公開する
> サーバ(VPS・レンタルサーバ等)には置かず、自分の PC の中だけで動かしてください。
> 既定で 127.0.0.1 からしかアクセスできない設定になっています(docker-compose.yml)。

リポジトリのルート(docker-compose.yml がある場所)で:

```text
docker compose up -d
docker compose exec app php tools/init-db.php
```

ブラウザで http://localhost:8180/login.php を開き、`docs/03_参考資料/world.md` の利用者名簿にある
アカウント(例: kiryu@example.com / password123)でログインできれば起動成功。

- ポート 8180 が使用中の場合: 環境変数 `PRACTICASE_PORT` で変更できる(+100 ずつ試す)
  - PowerShell: `$env:PRACTICASE_PORT='8280'; docker compose up -d`
  - bash: `PRACTICASE_PORT=8280 docker compose up -d`

## 3. チェックツールを確認する

```text
docker compose exec app php tools/check.php
```

課題一覧が表示されれば、学習を始める準備は完了。

## 4. T-000 を完了させる

`packs/php/tickets/00_はじめに/T-000_setup/ticket.md` を開き、チケットとして最後まで運ぶ
(status 更新 → 完了報告 → 振り返り)。以降の進め方は `docs/02_作業ルール/workflow.md`。

終えたら **`packs/php/tickets/01_チュートリアル/tutorial/ticket.md` を開く**(チュートリアル。「直す流れ」を30分で一周する)— 学習はチケットから始まる。

## 環境の後始末・作り直し

- 停止: `docker compose down`
- DB を初期状態に戻す: `docker compose exec app php tools/init-db.php`(何度でも可)

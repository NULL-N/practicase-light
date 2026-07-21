<p align="center">
  <img src="docs/assets/light-banner.svg" alt="PractiCase Light for PHP" width="100%">
</p>
<p align="center">
  <img src="docs/assets/light-badges.svg" alt="33 課題 / PHP 8.5 / Docker / Redmine / 実務フロー / 無料・課金なし">
</p>

# PractiCase Light for PHP

**コードを書く力を、今以上に現場で通用する力へ。**

PractiCase Light for PHPは、チケットを受け取り、コードを調査・修正し、テストし、報告して閉じるまでを
繰り返す実務体験型のエンジニア育成教材です。入門向けの33課題が含まれます。

完成済みのアプリを操作するだけではなく、
**Redmine × VS Code × check × 報告**という仕事の流れを通して学びます。

## 必要な環境

- GitHubアカウント(教材の取得・Pull Requestに使用)
- Git(ブランチ作成・commit・mergeに使用)
- VS Code(コードの調査・修正に使用)
- Docker Desktop(Docker Composeを含む。教材環境の起動に使用)

RedmineとPostgreSQLをホストへ個別にインストールする必要はありません。
Docker Composeが固定済みのイメージを取得・起動します。

## 導入手順

1. 自分の作業用リポジトリをcloneし、VS Codeでリポジトリの一番上を開きます

   ```text
   git clone https://github.com/<自分のアカウント>/<リポジトリ名>.git
   cd <リポジトリ名>
   ```

2. アプリ、PCP、Redmineを起動し、アプリのDBを初期化します

   ```text
   docker compose --profile redmine up -d
   docker compose exec app php tools/init-db.php
   ```

3. PowerShellでRedmineを初期設定し、Lightの全33課題を投入します

   ```powershell
   Get-Content -Raw tools/redmine/bootstrap.rb | docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -'
   docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all
   ```

   Git Bashのコマンドや停止・再初期化手順は
   [Redmine運用ガイド](docs/02_作業ルール/redmine-guide.md)にあります。

4. ブラウザで動作を確認します

   | 開く場所 | 役割 |
   |---|---|
   | `http://127.0.0.1:8280` | Redmine。チケット選択、担当、進捗、コメント |
   | `http://localhost:8180` | PractiCaseアプリ。症状の再現と修正後の確認 |

5. [導入からチュートリアル2完了までの手順書](docs/00_はじめに/start-to-tutorial-guide.md)を開き、
   `T-000` → `tutorial` → `tutorial-2`の順で進めます

## 使う場所と役割

| 場所 | 役割 |
|---|---|
| **Redmine** | チケットを選び、担当者・ステータス・見積・結果を記録する場所 |
| **VS Code** | `ticket.md`、仕様、コードを読み、実装する場所 |
| **ターミナル** | Git、Docker、`tools/check.php`を実行する場所 |
| **ブラウザ** | アプリの症状を再現し、修正結果を確認する場所 |

それぞれ役割が別です。Redmineは進捗、`ticket.md`は課題内容、`check.php`は合否を管理します。
Redmineではチケットを英語やAPI上で`issue`と表記することがあります。

## 1枚のチケットを完了する流れ

1. Redmineでチケットを開き、自分を担当者にして`New` → `In Progress`
2. ブラウザで症状を再現する
3. VS Codeでbranchを作り、`ticket.md`に従って調査・実装する
4. ターミナルで`docker compose exec app php tools/check.php <課題ID>`を実行する
5. ブラウザで修正結果を確認し、報告を作成してcommitする
6. RedmineへPASS結果をコメントし、`Resolved` → `Closed`

Redmineが使えないときだけ、`ticket.md`のfront matterで進捗を管理できます。
Redmineとfront matterは自動同期されません。

## 次に読む文書

- [START_HERE.md](START_HERE.md): 状況別の開始地点
- [LEARN_HERE.md](LEARN_HERE.md): 学習者向け文書の索引
- [セットアップガイド](docs/00_はじめに/setup-guide.md): 導入の詳細
- [Redmine運用ガイド](docs/02_作業ルール/redmine-guide.md): 起動、seed、停止、fallback
- [作業フロー](docs/02_作業ルール/workflow.md): 33課題の進行順と運用ルール
- [フォルダ地図](docs/03_参考資料/folder-map.md): ファイルの場所と役割

初回学習では、AIコード補完・生成AI拡張を無効にして、自分で読む・探す・直す・確かめる流れを
一度経験することを推奨します。設定方法は[セットアップガイド](docs/00_はじめに/setup-guide.md)にあります。

## 利用条件

本教材の著作・利用・再配布条件は[TERMS.md](TERMS.md)、AIツール利用時の遵守事項は
[AI_USAGE.md](AI_USAGE.md)を確認してください。

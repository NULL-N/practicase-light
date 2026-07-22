---
id: C-001
title: 通知基盤PCPで通知を1件送り、記録を確認する
level: 1
track: cloud
type: cloud
priority: normal
estimated_minutes: 45
role: developer
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# C-001 通知基盤PCPで通知を1件送り、記録を確認する

## 背景

応募のステータス更新をユーザーへ知らせる手段として、社内で運用している
通知基盤 **PCP(PractiCase Cloud Platform)** を使う構想が挙がっている。
PCP は HTTP の API を持つ独立したサービスで、うちのアプリとは別のコンテナで動いている。

本格的に組み込む前に、まず開発者として PCP の通知 API を1件だけ呼び出し、
「何を送ると・何が返り・どんな記録が残るのか」を自分の手で確かめて報告してほしい。

この章から先は、**自分のアプリの外にあるサービス**を相手にする。
コードを直す課題ではなく、「仕様を読む → 呼び出す → 結果と記録を確認する → 報告する」
という外部 API 連携の基本動作そのものが成果物になる。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`C-001`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-C-001-first-notification-call
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-C-001-first-notification-call`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## この課題でやること

1. `support/spec.md`(PCP 通知 API 仕様書)を読む
2. `app` コンテナの中から疎通確認する:

   ```text
   docker compose exec app curl http://pcp:8080/v1/health
   ```

3. 教材用テストキーを使って、通知を **1件** 作成する(`POST /v1/notifications`)
4. レスポンスの通知ID(`id`)と状態(`status`)を確認する
5. `GET /v1/notifications/{id}` で状態を確認する — 直後は `queued`、
   **2秒ほど待ってから**もう一度確認すると `delivered` に変わる
6. 宛先を `fail-` で始まる名前(例: `fail-user-999`)にした通知も1件送り、
   `failed` になることを確認する
7. `GET /v1/audit-log` で監査ログを確認する — 自分の操作がどう記録されているか、
   **API キーがどう表示されているか**に注目する
8. 報告書を `reports/C-001_report.md` に書く

## 報告書の形式(必須)

次の3つの見出しをこの文言のまま使うこと:

```text
## 何を送ったか
## 何が返ったか
## 監査ログに何が残ったか
```

- 「何が返ったか」には、実際に返ってきた**通知ID(`ntf_` で始まる値)**と
  状態の変化(`queued` → `delivered` / `failed`)を必ず書く
- 「監査ログに何が残ったか」には、API キーが**どう記録されていたか**
  (全文か・一部か)と、そこから読み取れる意図を書く

## 注意(安全)

- 使ってよいのは教材用テストキー(`PCP_TEST_KEY_` で始まるダミー)だけ。
  **実在のサービスの API キーやパスワードを、コマンドにも報告書にも絶対に書かない**
- PCP は教材内で完結するローカルのサービスで、外部への通信は発生しない

## 完了の定義

- [ ] 通知を1件以上、自分のリクエストで作成した
- [ ] `queued` → `delivered` と、`fail-` 宛先の `failed` を確認した
- [ ] 監査ログで自分の操作と API キーの記録のされ方を確認した
- [ ] `reports/C-001_report.md` を3つの見出しで書いた
- [ ] `docker compose exec app php tools/check.php C-001` が PASS

## 提出と完了(共通手順)

この節は、上の課題固有手順を提出までつなぐ補足です。手順1は作業前、手順2以降はcheckがPASSした後に行います。

1. Redmineでこのチケットを開き、担当者を自分にして、見積をコメントし、ステータスを
   `New` → `In Progress`にする(本文ですでに実施している場合は繰り返さない)
2. commit・pushする前に`support/rubric.md`の「提出前」を確認する。満たしていない項目があれば修正し、checkをやり直す
3. 変更をcommit・pushし、Pull Requestを作る。この時点ではまだmergeしない
4. Pull Requestをmergeする
5. `support/debrief/`がある課題は、Pull Requestをmergeした後に開いて自分の提出と突き合わせる。
   突き合わせで見つけた違いは振り返りに記録し、必要な修正は別チケットで扱う
   その結果を振り返りに書く。本文でファイル名の指定がなければ
   `reports/C-001_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/C-001-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

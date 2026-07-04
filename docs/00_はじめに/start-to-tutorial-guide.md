# 導入からチュートリアル2完了までの手順書

この文書は、PractiCase を手元に用意してから、`T-000`、`tutorial`、`tutorial-2` を終えるまでの一本道です。
印刷するか、ブラウザの別ウィンドウで開いたまま進めてください。

同じ内容の `docs/00_はじめに/start-to-tutorial-guide.html` をブラウザで開くと、印刷または PDF 保存しやすい形で読めます。

## 完了の定義

この手順書を終えた時点で、次の状態になっていれば導入完了です。

| 項目 | 完了状態 |
|---|---|
| 環境 | `docker compose up -d` でアプリが起動している |
| DB | `docker compose exec app php tools/init-db.php` を実行済み |
| T-000 | `check T-000` が PASS、完了報告が `reports/` にある |
| tutorial | `check tutorial` が PASS、3行報告が `reports/` にある |
| tutorial-2 | `check tutorial-2` が PASS、3行報告が `reports/` にある |
| 次の一歩 | `packs/php/tickets/02_開発の基礎/T-001_job_validation/ticket.md` を開ける状態 |

## 0. 使う場所を決める

作業は、必ず自分のコピーで行います。配布元や master 原本で課題を解かないでください。

GitHub を使う場合は、配布元リポジトリから自分のリポジトリを作り、clone します。

```text
git clone https://github.com/<自分のアカウント>/<リポジトリ名>.git
```

GitHub を使えない場合だけ、ZIP を展開してローカルリポジトリ化します。

```text
git init -b main
git add .
git commit -m "T-000: セットアップ開始"
```

## 1. VS Code でリポジトリの一番上を開く

VS Code の「ファイル → フォルダーを開く」で、`README.md` と `docker-compose.yml` がある階層を開きます。

下位フォルダだけを開くと、`docs/` や `packs/php/tickets/` が検索範囲外になり、手順どおりに進められません。

ファイルを探すときは、基本的に **Ctrl+P → ファイル名** で開きます。

## 2. 任意でチューター拡張を入れる

チューター拡張は任意です。入れなくてもすべての課題は完走できます。

入れる場合:

1. VS Code で `Ctrl+Shift+P`
2. `Extensions: Install from VSIX` を選ぶ
3. `extensions/practicase-tutor/` フォルダにある `.vsix` ファイルを選ぶ
4. `Ctrl+Shift+P` → `Reload Window`

入れ終えると、VS Code にチュートリアル用の案内が表示されます。
この拡張はローカル完結で、外部通信はしません。

## 3. アプリを起動する

ターミナルを開き、リポジトリの一番上で実行します。

```text
docker compose up -d
docker compose exec app php tools/init-db.php
```

ブラウザで次を開きます。

```text
http://localhost:8180/login.php
```

`docs/03_参考資料/world.md` の利用者名簿にあるアカウントでログインできれば起動成功です。

例:

```text
kiryu@example.com / password123
```

ポートを変えた場合は、自分の番号に読み替えます。

```text
http://localhost:8280/login.php
```

## 4. check コマンドを確認する

ターミナルで実行します。

```text
docker compose exec app php tools/check.php
```

課題一覧が出れば、チェックツールは動いています。

以後、文中の `check tutorial` は次の意味です。

```text
docker compose exec app php tools/check.php tutorial
```

## 5. T-000 を完了する

開くファイル:

```text
packs/php/tickets/00_はじめに/T-000_setup/ticket.md
```

やること:

1. `status: open` を `status: in_progress` にする
2. 起動確認を行う
3. `reports/T-000_setup_report.md` を作って、完了報告を書く
4. check を実行する

```text
docker compose exec app php tools/check.php T-000
```

`結果: PASS` になったら、`status` を `closed` にします。

ここまでで環境構築は完了です。

## 6. tutorial を始める

開くファイル:

```text
packs/php/tickets/01_チュートリアル/tutorial/ticket.md
```

この課題は、1語だけ直して「見る → 探す → 直す → check → 報告 → closed」を一周する練習です。

まずブラウザで次を開きます。

```text
http://localhost:8180/login.php?tutorial=1
```

画面の案内に従い、ログイン、案件一覧、案件詳細へ進みます。
明るく表示されている行の項目名を見て、一覧と違う言葉を見つけます。

VS Code では **Ctrl+Shift+F** を使い、画面に出ている誤った言葉を検索します。

検索結果は2箇所出ます。

| 種類 | 役割 |
|---|---|
| `public/projects/show.php` | 直す画面 |
| `tests/checks/tutorial.php` | 合格条件 |

直すのは画面の方です。

修正後、check を実行します。

```text
docker compose exec app php tools/check.php tutorial
```

PASS したら、`reports/tutorial_fix_report.md` を作り、3行で報告します。

```text
何が起きていたか:
どこをどう直したか:
check の結果:
```

最後に `ticket.md` の `status` を `closed` にします。

## 7. tutorial-2 を始める

開くファイル:

```text
packs/php/tickets/01_チュートリアル/tutorial-2/ticket.md
```

この課題は、「直す」ではなく「小さな部品を作る」練習です。

最初に読むファイル:

```text
packs/php/app/tests/checks/tutorial-2.php
```

この課題では、テストが仕様書の役割を持っています。
4つの `test(...)` を読み、何を入力すると何が返るべきかを確認します。

実装するファイル:

```text
packs/php/app/src/Support/TagSummary.php
```

TODO の中に、タグ別の件数を数える処理を書きます。

実装後、check を実行します。

```text
docker compose exec app php tools/check.php tutorial-2
```

PASS したら、`reports/tutorial-2_fix_report.md` を作り、3行で報告します。

```text
作ったもの:
工夫した点:
check の結果:
```

最後に `ticket.md` の `status` を `closed` にします。

## 8. 次に進めるか確認する

次のチェックがすべて埋まれば、導入とチュートリアルは完了です。

- [ ] アプリにログインできる
- [ ] `check T-000` が PASS
- [ ] `check tutorial` が PASS
- [ ] `check tutorial-2` が PASS
- [ ] `reports/` に3本の報告がある
- [ ] `T-000`、`tutorial`、`tutorial-2` の `status` が `closed`

次に開くファイル:

```text
packs/php/tickets/02_開発の基礎/T-001_job_validation/ticket.md
```

ここから本番の学習に入ります。T-001 は `docs/00_はじめに/first-ticket-walkthrough.md` を手元に置いて進めてください。

## 困ったとき

| 症状 | 見る場所 |
|---|---|
| ファイルが見つからない | VS Code でリポジトリの一番上を開いているか確認 |
| ポートが使えない | `PRACTICASE_PORT` を変える |
| DB を戻したい | `docker compose exec app php tools/init-db.php` |
| check が FAIL | FAIL のメッセージを読み、対象ファイルと条件を確認 |
| チュートリアル拡張が無い | 拡張なしでも ticket.md の手順で進められる |


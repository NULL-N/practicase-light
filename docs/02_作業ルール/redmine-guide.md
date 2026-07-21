# Redmine 運用ガイド(チケット駆動運用)

PractiCase Light では、**Redmine を仕事の入口と進捗管理の正本にする運用が標準**です。
最初の `tutorial` と `tutorial-2` には初回操作を課題内にも書いてあります。以降の
全33課題で共通する起動・投入・reset・fallback の手順は、このガイドを参照してください。

Redmine が使えない場合も学習を止めないため、`ticket.md` の front matter を使う
fallback を用意しています。これは通常運用ではなく、接続不能時の代替手段です。

## 使用バージョンについて

この教材は `docker compose --profile redmine up -d` を実行すると、固定済みの
Docker公式イメージを自動取得して起動します。Redmine や PostgreSQL をホストへ
個別にインストールする必要はありません。

現在は Redmine `6.1.3` と PostgreSQL `14.23` を使用します。Redmine 本体には
より新しい安定版 `7.0.0` がありますが、2026-07-19 時点では Redmine 7 の
Docker公式イメージがまだ公開されていません。今すぐ7系を使うには教材独自の
Dockerイメージを継続保守する必要があるため、検証済みのDocker公式最新版
`6.1.3`を採用しています。

PostgreSQL `14.23`はPostgreSQL全体の最新版ではなく、Redmineの公式互換性表に
明記された14系の最新minorです。PostgreSQL 14の公式サポートは2026-11-12に
終了予定なので、この構成を長期固定する意図はありません。Redmine 7のDocker公式
イメージ公開時、またはPostgreSQL 14のサポート終了前に、Redmineが公式に推奨する
サポート中のPostgreSQLとの組み合わせを再検証して更新します。

公式の確認先:

- [Redmine Docker Official Image](https://hub.docker.com/_/redmine/)
- [Redmine の対応環境](https://www.redmine.org/projects/redmine/wiki/RedmineInstall)
- [PostgreSQL のサポート期間](https://www.postgresql.org/support/versioning/)

## 1. 正本の役割分担

| 場所 | 役割 |
|---|---|
| `ticket.md`(このリポジトリ) | **課題内容の正本**。何をすべきか・完了条件は常にここ |
| `reports/`(このリポジトリ) | **提出物の正本**。check が見るのはここだけ |
| Redmine | **進捗管理**。New → In Progress → Resolved → Closed をここで操作する |

Redmine から `ticket.md` への自動の逆同期はありません。Redmine のチケット番号や
件名を課題の識別子として使わないでください — 課題の識別子は常に `ticket.md` の
front matter にある `id`(例: `T-018`)です。Redmine 側では、同じ値がカスタム
フィールド「PractiCase Ticket ID」に入っています。
Redmineの英語画面やREST APIでは、チケットを`issue`と表記します。

## 2. 起動

```text
docker compose --profile redmine up -d
```

`--profile redmine` を付けない通常の `docker compose up -d` では、Redmine は
起動しません(従来どおり app と pcp だけが立ち上がります)。両方を切り替えて
使えます。

初回起動は Redmine 側の DB マイグレーションが走るため、数十秒かかることがあります。
次のコマンドで準備完了を待てます:

```text
docker compose --profile redmine logs -f redmine
```

`Listening on` のような行が出て落ち着いたら準備完了です。

## 3. 初期化(bootstrap)

Redmine 側にプロジェクト・トラッカー・カスタムフィールド・学習者アカウントを
作ります。**何度実行しても安全**です(2回目以降は `bootstrap: no-op(すべて設定済み)` と表示されます)。

PowerShell:

```powershell
Get-Content -Raw tools/redmine/bootstrap.rb | docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -'
```

Git Bash:

```bash
docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -' < tools/redmine/bootstrap.rb
```

完了したら、ブラウザで開いてログインできます:

- URL: <http://127.0.0.1:8280>
- ログイン: `practicase` / `practicase123`(教材専用の固定値です)

## 4. 課題の投入(seed)

全33課題をまとめて Redmine へ投入します:

```text
docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all
```

- 対象は `packs/php/tickets/` から自動で読み取ります(ID を手で数える必要はありません)
- **既に作ったチケットには触れません**(再実行しても新規は作られず、内容も変わりません)
- 特定の課題だけ投入したい場合は `--ids=T-018,T-019` のように個別指定もできます

### 内容だけを最新化したいとき

課題の説明文が教材更新で変わった場合、件名・説明・教材ID**だけ**を最新化できます:

```text
docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all --update-content
```

**status・担当者・コメント(note)は変更しません**(あなたの作業記録は消えません)。

## 5. Redmine での進め方

1. <http://127.0.0.1:8280> にログインし、プロジェクト「PractiCase Light」を開く
2. 取り組む課題を選ぶ。カスタムフィールド「PractiCase Ticket ID」を見れば、
   `ticket.md` のどの課題に対応するかが分かります(例: `T-018`)
3. 自分を担当者に設定し、作業見積をコメント(note)に残す
4. チケットのステータスを **New → In Progress** に変更し、
   `feature/redmine-<チケット番号>-<課題ID>-<短い名前>` の形式でブランチを作る
5. `ticket.md` の指示に従ってコードを書き、`support/` の資料を読み、
   `docker compose exec app php tools/check.php <課題ID>` を実行する
6. check が PASS したら、結果と提出物名をコメントに残し、status を
   **In Progress → Resolved** に変更する
7. 提出(PR 作成・reports への記載など)とセルフレビューまで終えたら
   **Resolved → Closed** にして完了する

ステータスの操作は Redmine のチケット編集画面から行います(front matter の `status` を
書き換える必要はありません — Redmine を使う場合、進捗の正は Redmine 側です)。
迷ったことや気づいたことも、チケットのコメント(note)に残してください。Redmine と
`check.php` の自動同期はありません。PASS/FAIL は `check.php` の出力を確認し、結果を
自分で Redmine に記録します。

## 6. 停止

```text
docker compose --profile redmine stop redmine redmine-db
```

データは volume に残るので、次回 `docker compose --profile redmine up -d` で
続きから再開できます。app・pcp・PCP のデータには影響しません。

## 7. Redmine が使えないとき(fallback)

Redmine が起動しない・停止している・接続できないときも、**学習は止まりません**:

- `ticket.md` を直接開いて、いつもどおり作業・提出してください
- `check.php` は Redmine に一切依存しません(動作は変わりません)
- Redmine 側の進捗操作(status 変更)は、Redmine が使えるようになってから
  まとめて行えます。**自動では反映されません** — fallback 中に進めた分の
  status は、あなたが後で手動で合わせる必要があります(何をどこまで合わせるかは
  自由です。記録の正本は `reports/` にあるので失われません)

「画面が開けない」「接続できない」と感じたら、まず起動しているか確認してください:

```text
docker compose --profile redmine ps
```

## 8. 初期化(reset)

**⚠ 破壊的操作です。** Redmine 上の status・担当者・コメント(note)がすべて
消えます。実行前によく確認してください。

Redmine 専用のコンテナと volume だけを初期化します(`pcp_data` や app のデータ、
他の課題の進捗には触れません)。

**`docker compose down -v` は使わないでください。** `--profile` を付けても、
`down -v` はこの compose ファイルが定義する volume を**すべて**消します
(pcp_data も含まれます — 外部API入門の C-001〜C-003 の記録まで消えます)。

**単純な名前一致(`grep redmine` 等)でも消さないでください。** 別プロジェクトで
同じサービス名を使っていたり、過去の作業で同名の volume が残っていたりすると、
無関係な volume まで巻き込みます。下のスクリプトは、**今動いている app コンテナ
自身が持つ Docker Compose のラベル**(`com.docker.compose.project`)を起点に、
このプロジェクトの Redmine 専用 volume だけを**ラベルで**特定します。
対象が1つでも想定と違えば(0件・2件以上・見つからない)、**何も削除せずに
エラーで止まります**。

**PowerShell:**

```powershell
# 1. app コンテナから、このプロジェクトの識別ラベルを取得する
$appId = docker compose ps -q app
if (-not $appId) {
    Write-Error "app コンテナが見つかりません。先に docker compose up -d app を実行してください"
    exit 1
}
$project = docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' $appId
if (-not $project) {
    Write-Error "project ラベルを取得できませんでした"
    exit 1
}
Write-Host "対象プロジェクト: $project"

# 2. Redmine 専用の3つの論理 volume 名それぞれについて、
#    「このプロジェクト」かつ「その論理名」の volume が厳密に1件だけ存在するか確認する
$targets = @()
foreach ($vol in @("redmine_db_data", "redmine_files", "redmine_runtime")) {
    $found = @(docker volume ls --filter "label=com.docker.compose.project=$project" --filter "label=com.docker.compose.volume=$vol" --format "{{.Name}}" | Where-Object { $_ -ne "" })
    if ($found.Count -ne 1) {
        Write-Error "$vol の一致が $($found.Count) 件です(期待は1件)。削除を中止します"
        exit 1
    }
    $targets += $found[0]
}
if ($targets.Count -ne 3) {
    Write-Error "削除対象が $($targets.Count) 件です(期待は3件)。削除を中止します"
    exit 1
}
Write-Host "削除対象(3件): $($targets -join ', ')"

# 3. ここまで来て初めて削除操作に入る
docker compose stop app
docker compose --profile redmine stop redmine redmine-db
docker compose --profile redmine rm -f app redmine redmine-db
docker volume rm $targets
docker compose --profile redmine up -d
```

**bash(Git Bash 等):**

```bash
# 1. app コンテナから、このプロジェクトの識別ラベルを取得する
app_id=$(docker compose ps -q app)
if [ -z "$app_id" ]; then
  echo "エラー: app コンテナが見つかりません。先に docker compose up -d app を実行してください" >&2
  exit 1
fi
project=$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$app_id")
if [ -z "$project" ]; then
  echo "エラー: project ラベルを取得できませんでした" >&2
  exit 1
fi
echo "対象プロジェクト: $project"

# 2. Redmine 専用の3つの論理 volume 名それぞれについて、
#    「このプロジェクト」かつ「その論理名」の volume が厳密に1件だけ存在するか確認する
targets=()
for vol in redmine_db_data redmine_files redmine_runtime; do
  found=$(docker volume ls --filter "label=com.docker.compose.project=${project}" --filter "label=com.docker.compose.volume=${vol}" --format "{{.Name}}")
  count=$(printf '%s\n' "$found" | grep -c . || true)
  if [ "$count" -ne 1 ]; then
    echo "エラー: ${vol} の一致が ${count} 件です(期待は1件)。削除を中止します" >&2
    exit 1
  fi
  targets+=("$found")
done
if [ "${#targets[@]}" -ne 3 ]; then
  echo "エラー: 削除対象が ${#targets[@]} 件です(期待は3件)。削除を中止します" >&2
  exit 1
fi
echo "削除対象(3件): ${targets[*]}"

# 3. ここまで来て初めて削除操作に入る
docker compose stop app
docker compose --profile redmine stop redmine redmine-db
docker compose --profile redmine rm -f app redmine redmine-db
docker volume rm "${targets[@]}"
docker compose --profile redmine up -d
```

`redmine_runtime`(seed の認証情報)は app コンテナが読み取り専用でマウントして
いるため、app もいったん止めます(手順内で自動的に行われます)。最後の `up -d`
で app も一緒に立ち上がります(進行中の課題は消えていません — 削除したのは
Redmine 側のデータだけです)。`pcp_data` や他のプロジェクトの volume・過去の
孤立 volume がラベル不一致で対象から外れることは、この手順が正しく機能して
いる証拠です(削除する前に必ずエラーで止まります)。

起動後、初期化と全課題投入をやり直せば、同じ33の教材IDで作り直せます:

```text
docker compose --profile redmine exec -T redmine sh -c 'SECRET_KEY_BASE="$REDMINE_SECRET_KEY_BASE" bin/rails runner -' < tools/redmine/bootstrap.rb
docker compose exec -T app php tools/redmine-seed.php --ticket-root=packs/php/tickets --all
```

## 9. 変更履歴(journal)の見分け方

issue の詳細画面には、変更の履歴(journal)が時系列で並びます。ここには2種類が
混ざることがあります:

| 種類 | 内容 |
|---|---|
| **あなたが書いたコメント(note)** | 自分で入力した文章。**seed では消えません** |
| **Redmine が自動で記録する変更履歴** | 「件名を変更」「説明を変更」のような、
  誰が・いつ・何を変えたかの自動記録。`--update-content` で内容を更新したときに
  Redmine 自身がここへ記録することがあります |

自分のコメントかどうかは、投稿者名で見分けられます。「誰が・いつ・何を変えたか」を
確認したいときは、issue 詳細画面の履歴をそのまま上から読んでください(検索や
フィルタは不要な規模です)。

## 10. うまくいかないとき

- **`redmine-seed.php` が「runtime 認証情報が見つかりません」と言う**:
  bootstrap(3章)をまだ実行していません。先に実行してください
- **status を変更したのに反映が変(New のまま等)**: ごく稀に、issue の作成直後の
  status 遷移が失敗することがあります。この場合、seed の再実行では直りません
  (通常の seed は既存 issue の status に触れない設計のため)。
  「PractiCase Ticket ID」でその issue を特定し、Redmine の画面上で手動で
  status を直すか、8章の reset で作り直してください。
  `--update-content` は件名・説明の更新用で、status の修復には使えません
- **それでも解決しない**: 7章の fallback に切り替えて `ticket.md` で進めてください。
  学習の継続は常に保証されています

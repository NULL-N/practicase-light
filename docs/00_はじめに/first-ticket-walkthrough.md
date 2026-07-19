# 課題の進め方 — 最初の1本(T-001)完全ガイド

この文書は、最初の本番課題 T-001 を最後まで運ぶ手順の完全版です。
(肩慣らしのチュートリアル課題 `packs/php/tickets/01_チュートリアル/tutorial/` をまだやっていない人は先にそちらを。30分で一周できます)
**印刷するか、ブラウザの別ウィンドウで開いたままにして、VS Code はコードの作業に集中してください。**
印刷・PDF化する場合は、同じ内容の `docs/00_はじめに/first-ticket-walkthrough.html` をブラウザで開いて Ctrl+P(印刷 → PDF に保存)。

2本目以降の課題も、Redmine から始めて同じ流れで進みます
(最終章「以降の課題への適用」参照)。

> **パスの読み方**: この教材に出てくるファイルパスは、すべて**リポジトリの一番上(README.md がある階層)からの
> 相対表記**です。ファイルを探すときは、VS Code で **Ctrl+P** を押してファイル名(例: features.md)を
> 入力するのが最速です(実務でも標準の移動方法)。Ctrl+P で見つからないときは、VS Code で開いている
> フォルダが**リポジトリの一番上**になっているか確認してください(下位フォルダを開くと検索範囲外になります)。

## 全課題共通の流れ(まずこの表を頭に入れる)

| # | やること | 使うもの |
|---|---|---|
| 1 | Redmine で課題を選び、対応するチケットを読む | Redmine / `tickets/<課題>/ticket.md` |
| 2 | 自分を担当者にし、見積をコメントして In Progress にする | Redmine / retrospective テンプレの「見積」欄 |
| 3 | ブランチを切る(=着手の宣言) | `git switch -c feature/redmine-<issue番号>-<課題ID>-<短い名前>` |
| 4 | 仕様書を読む | `support/spec.md` → `docs/01_設計資料/` の該当節 |
| 5 | 症状を再現する | ブラウザ(アプリ) |
| 6 | コードを読み、直す | VS Code |
| 7 | チェックを実行する | `docker compose exec app php tools/check.php <課題ID>` |
| 8 | 提出する | Pull Request(本文: `docs/templates/fix_report.md`) |
| 9 | セルフレビュー | `support/rubric.md`(debrief がある課題は提出後に突き合わせ) |
| 10 | 振り返り → Closed | `docs/templates/retrospective.md` → `reports/` / Redmine |

> **fallback**: Redmine が起動しない・接続できない場合だけ、`ticket.md` の front matter を
> open → in_progress → resolved → closed と更新します。通常は front matter を変更しません。
> Redmine と `check.php` の自動同期はないため、check の結果は自分でコメントに残します。
> 起動・初期化・reset の詳細は [Redmine 運用ガイド](../02_作業ルール/redmine-guide.md)を参照してください。

## T-001 の歩き方

### 手順1: Redmine からチケットを読む(約5分)

- Redmine のプロジェクト「PractiCase Light」で、カスタムフィールド
  `PractiCase Ticket ID = T-001` の issue を開きます
- 対応するファイル: `packs/php/tickets/02_開発の基礎/T-001_job_validation/ticket.md`
- 次の4箇所に印を付けます: ①症状(3つ挙がっています) ②期待動作 ③調査の入口 ④完了条件
- チケットは「起きていること」と「あるべき姿」のペアで読みます。この2つの**差**を埋めるのが今回の作業です

### 手順2〜3: status 更新とブランチ

- Redmine で自分を担当者に設定し、自分の見積(何分か+内訳)をコメントに残して、
  status を **New → In Progress** にします
- `docs/templates/retrospective.md` を `reports/T-001_retrospective.md` にコピーし、
  **「見積」の欄だけ**先に書きます
  (自分の読みで何分か+内訳。estimated_minutes を見る前に書くのがおすすめ — 完了後に答え合わせします)
- ターミナルで、ブランチを切ります — これは Git 操作というより「**このチケットに着手した**」という宣言です:
  `git switch -c feature/redmine-<issue番号>-T-001-validation`

### 手順4: 仕様書を読む(約10分)

- `support/spec.md` が指している `docs/01_設計資料/features.md` の **F-02** を開きます
- 「入力項目と検証ルール」の表を、**手元にチェックリストとして書き写します**(項目 / ルール / エラーメッセージ)
- 仕様書は通読するものではなく、実装と突き合わせる道具として使います

### 手順5: 症状を再現する(約10分)

修正の前に、壊れていることを自分の環境で確認します。再現できていないバグは、直ったかどうかも判定できません。

1. `docker compose --profile redmine up -d`(未起動の場合)
2. ブラウザで `http://localhost:8180/login.php` → クイックログインで「田淵(クライアント)」
3. 「案件を登録する」を開き、チケットの症状の値(時間単価にマイナスなど)を入力して登録する
4. **登録が通ってしまう**ことを確認し、どの値が通ったかをメモします

### 手順6: コードを読み、直す(約30分)

1. 画面の URL を確認します: `/client/project_new.php` — この教材では **URL と public/ 配下のファイルが対応**します
2. `packs/php/app/public/client/project_new.php` を開きます(ここは入口で、判断はしていません)
3. その中で `use` / `new` されているクラスを辿ると、検証を担当するクラス(`src/Service/` 内)に着きます
4. 手順4で書き写した表と実装を**1項目ずつ照合**し、仕様にあって実装に無いチェックを特定します(症状の3つ以外の漏れも確認)
5. 修正は、同じクラス内で正しく検証できている項目の書き方に合わせます。日時を扱う場合は `docs/02_作業ルール/coding-rules.md` の **ARC-5** を先に読みます

- コードの構造が分からなくなったら: `docs/03_参考資料/code-tour.md`(層の地図と処理の流れ)
- 設計資料は必要な箇所だけ開きます。この課題で主に見るのは `docs/01_設計資料/features.md` の **F-02** です
  (テーブル定義は database.md、画面遷移は screens.md にありますが、T-001 では通常必要ありません)
- 15分考えて進めなくなったら: `tickets/02_開発の基礎/T-001_job_validation/support/hints.md` を**1段ずつ**開きます

### 手順7: チェックを実行する(修正の前後で2回)

```text
docker compose exec app php tools/check.php T-001
```

- 修正**前**に1回: 課題別テストが FAIL するのを確認(症状のテスト版)
- 修正**後**に1回: PASS になるのを確認
- あわせて `git diff` で変更の全量を見て、余計な変更が混ざっていないか確認します

### 手順8〜10: 提出・セルフレビュー・振り返り

1. コミット → Pull Request を作成。**具体的な手順は `docs/02_作業ルール/git-and-pr-guide.md`**
   (push・PR画面・セルフマージまで全手順)。PR 本文は `docs/templates/fix_report.md` の形式で
   **原因を自分の言葉で**。check の PASS と提出物名を Redmine にコメントし、
   status を **In Progress → Resolved** にします
2. `support/rubric.md` の観点で自分の提出を見直します
3. 振り返りを書きます — `reports` フォルダを右クリック → 「新しいファイル」→ `T-001_retrospective.md` を作り、
   `docs/templates/retrospective.md` の項目をコピーして埋めます。書き終えたら Redmine の
   status を **Resolved → Closed** にして完了です

## 以降の課題への適用

- 2本目以降も「全課題共通の流れ」の10ステップは同じです。変わるのは各課題フォルダの中身(症状・仕様の参照先・scope)だけです
- 詰まったときは各課題の `support/hints.md`(15分粘ってから1段ずつ)

---
id: D-025
title: 設計基礎4: NULLを決める
level: 1
track: design
type: design
priority: normal
estimated_minutes: 20
role: designer
dojo_foundation_stage: 4
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# D-025: 設計基礎4: NULLを決める

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 成果物はNULL/DEFAULTメモ
> `reports/D-025_null_default_note.md` の1枚です。

早瀬さんから:

> D-024の型、いいと思います。次は、それぞれのカラムについて**「空でもいいか」**を
> 考えてみてください。重複禁止やキーの話はまだ早いです。今回は「これが空だと業務が
> 回らない必須情報」と「最初は空で、後から埋まる情報」を区別することに集中してください。
> あと、状態のような情報には「新規登録時点で何が自然な初期値か」も一緒に考えてみてください。

これは**設計基礎編**の4段階目です。D-024で決めた型を、そのまま引き継いで使ってください。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`D-025`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-D-025-member-management-null-default
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-D-025-member-management-null-default`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. D-024の `reports/D-024_column_types_note.md` を見返し、自分が決めた型を確認する
2. `reports/D-025_null_default_note.md` を作り、カラムごとに「NOT NULL / NULL可」と、
   なぜそう判断したかを書く
3. 部署名・氏名・メール・状態・部署への参照は、**無いと業務が成立しない情報**です。
   NOT NULLにしてください
4. 状態のカラムには、新規登録時点で自然な初期値(例: 「在籍中」)をDEFAULTとして考えてください
5. 作成日時・更新日時のようなカラムを入れているなら、登録された瞬間の時刻を自動で
   入れるのが自然です。DEFAULTとして検討してください
6. もし「退職日」「利用停止日」のような、**在籍中は空で、辞めた時に初めて埋まる情報**を
   思いついたら、それはNULL可にして、なぜ空でよいのかを書いてください
   (無理に追加する必要はありませんが、追加した場合は理由が必要です)

## スコープ外(明記すること)

D-022〜D-024と同じく、以下は扱いません。

- 給与
- 勤怠
- 評価
- 個人番号(マイナンバー等)
- 住所
- 健康情報

## 完了条件

- `docker compose exec app php tools/check.php D-025` が PASS
- 部署名・氏名・メール・状態・部署への参照がNOT NULLとして扱われている
- 状態にDEFAULT(在籍中相当)の初期値が検討されている
- (退職日等を追加したなら)NULL可であることと、その理由が書かれている
- UNIQUE・重複禁止・キーの話が主題になっていない(それは D-026 以降の仕事)

## 完了したら(次の一歩)

次は D-026(UNIQUEを決める)— 重複を許さない列を考えます。

> 詰まったら `support/hints.md`。段階的に4つ用意しています。

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
   `reports/D-025_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/D-025-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

---
id: D-024
title: 設計基礎3: 型を選ぶ
level: 1
track: design
type: design
priority: normal
estimated_minutes: 20
role: designer
dojo_foundation_stage: 3
status: open
scope:
depends_on:
  - "T-000"
pack: php
---

# D-024: 設計基礎3: 型を選ぶ

**起票: 早瀬(開発リーダー)**

> **この課題ではコードを書きません。** 成果物は型選択メモ
> `reports/D-024_column_types_note.md` の1枚です。

早瀬さんから:

> D-023のカラム、いいですね。次は、それぞれのカラムに**どんな型がふさわしいか**を
> 考えてみてください。ただの「TEXT」「INTEGER」のような型名だけでなく、
> **「なぜその型にしたか」を一言添えて**ください。NULLを許すか、重複を禁止するか、
> キーにするか、はまだ考えなくて大丈夫です。「文字列なのか、数値なのか、日付なのか、
> 決まった種類の値(状態)なのか」だけを判断してください。

これは**設計基礎編**の3段階目です。D-023で決めたカラムを、そのまま引き継いで使ってください。

## 着手時の作業branch

課題内容を確認したら、ファイルを変更する前に次の順で着手します。

1. Redmineで`PractiCase Ticket ID`が`D-024`のチケットを開き、担当者を自分にして、
   見積をコメントし、ステータスを`New` → `In Progress`にする
2. VS Codeで教材フォルダを開いた状態のターミナルから、mainを更新して作業branchを作る

   ```text
   git switch main
   git pull --ff-only
   git status --short
   git switch -c feature/redmine-<チケット番号>-D-024-member-management-types
   ```

`<チケット番号>`はRedmineのURL末尾の数字です。URLが`/issues/3`なら、branch名は
`feature/redmine-3-D-024-member-management-types`です。`git status --short`に何か表示された場合は、branchを作る前に変更内容を確認します。
この後の課題固有手順に同じRedmine操作がある場合は、繰り返しません。

## やること

1. D-023の `reports/D-023_columns_note.md` を見返し、自分が決めたカラムを確認する
2. `reports/D-024_column_types_note.md` を作り、`support/spec.md` の型でカラムごとに
   「型 — 理由」を書く
3. 部署名・氏名・メールのような「言葉の情報」は文字列型にする
4. 状態(在籍中/退職済み等)のような「決まった種類の中から選ぶ情報」は、文字列型か、
   決まった値だけを取る型(ENUM相当)にする
5. 部署への参照は、部署テーブルの`id`を指し示す数値型にする
6. 作成日時・更新日時のようなカラムを入れているなら、日時型にする

## スコープ外(明記すること)

D-022・D-023と同じく、以下は扱いません。

- 給与
- 勤怠
- 評価
- 個人番号(マイナンバー等)
- 住所
- 健康情報

## 完了条件

- `docker compose exec app php tools/check.php D-024` が PASS
- 部署名・氏名・メールが文字列型として扱われている
- 状態が文字列またはENUM相当として扱われている
- 部署への参照が数値型として扱われている
- 各カラムの型に「理由」が添えられている(型名だけで終わっていない)
- NULL・重複禁止・キーのような話が主題になっていない(それは D-025 以降の仕事)

## 完了したら(次の一歩)

次は D-025(NULLを決める)— ここで決めた型それぞれに、必須か任意かを考えます。

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
   `reports/D-024_retrospective.md`を使用する
6. RedmineへPASS結果・Pull RequestのURL・振り返りのファイル名をコメントし、
   ステータスを`Resolved` → `Closed`にする
7. Redmineを`Closed`にした後、`support/rubric.md`の「提出後」を確認する

> **Redmineが使えないときだけ**: `ticket.md`のfront matterで進捗を管理し、
> branch名は`feature/D-024-<短い名前>`へ読み替えます。Redmineとの自動同期はありません。

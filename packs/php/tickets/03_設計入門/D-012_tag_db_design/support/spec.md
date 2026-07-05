# D-012 spec: DB設計の前提と要求

## 前提(F-20 で確定済み)

- タグは運営が用意した一覧(当面5つ)から選ぶ。自由入力はしない
- 1つの案件に**最大3つ**(0個でもよい)
- 1つのタグは**多くの案件**に付きうる
- 絞り込みは「タグを1つ選ぶ」方式(S-03)

## 設計してほしいこと

1. **関係の判定**: 案件とタグの関係は何対何か。理由つきで
2. **tags テーブル**: タグの一覧(名前の重複を防ぐこと)
3. **project_tags テーブル**: 案件とタグの結びつき(同じ組み合わせの二重登録を防ぐこと)
4. **ER 図の更新**: 2. 節の mermaid 図に、2テーブルと関係線を追加

## 守ってほしい流儀(database.md 冒頭の設計方針)

- D-7: 主キーは `id INTEGER PRIMARY KEY AUTOINCREMENT`
- D-1: 日時カラムは TEXT(`created_at` / `updated_at` を忘れずに)
- D-4: 入力値の検証はアプリ層。DB 制約は NOT NULL / UNIQUE / FK のみ
- D-5: 外部キーは必ず定義する

## check が見る必須要素(欠けると FAIL)

`tags`・`project_tags` それぞれについて、次を過不足なく書いてください。

| 要素 | 説明 |
|---|---|
| テーブル名・カラム名 | `tags`(id・name・created_at・updated_at)/ `project_tags`(id・project_id・tag_id・created_at・updated_at) |
| 型 | INTEGER / TEXT |
| PRIMARY KEY | `PRIMARY KEY`(または `PK`) |
| FOREIGN KEY | `project_tags` に `projects.id` / `tags.id` への参照(`FOREIGN KEY` / `FK` / `REFERENCES` のいずれの書き方でもよい) |
| NOT NULL | 必須カラムに明記 |
| UNIQUE | 二重付与を防ぐ複合ユニーク(列順は自由)。`tags.name` の重複防止も含む |
| created_at / updated_at | 両テーブルとも |

**書き方は自由**です。Markdown 表でも `CREATE TABLE` でも構いません。FK の表記スタイル・UNIQUE の列順・
DATE か TEXT かの選び方は、check では縛りません。

## 参考(見るとよい既存の型)

- 3.4 applications — 複合ユニーク `UNIQUE(project_id, engineer_id)` の書き方
- 4.1 skills / project_skills — **見てよいが、丸写しは不可**。あちらは将来構想(Phase 2)で、
  今回のタグとは要件が違います(is_required など今回不要な列がある)。違いを自分で判断すること

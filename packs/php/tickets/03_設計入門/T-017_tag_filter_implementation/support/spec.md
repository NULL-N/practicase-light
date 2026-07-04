# T-017 spec: 実装の仕様

## ProjectRepository::searchOpen の新しい形

```php
public function searchOpen(string $keyword, bool $remoteOnly, string $today, ?int $tagId = null): array
```

- 引数を1つ増やすだけ。既存の呼び出し元(`public/projects/index.php` 以外)は直さなくてよい
- `$tagId` が `null` のとき: 今までどおり絞り込まない(後方互換)
- `$tagId` が指定されたとき: そのタグが付いている案件だけを返す
- タグ・キーワード・リモート可のみは、すべて **AND** で組み合わさる(F-20)

SQL の考え方: タグが指定されたときだけ `project_tags` を JOIN する(未指定のときは既存の SQL のまま)。
SQL はこのクラスだけに書く(ARC-2)。

## public/projects/index.php に足すもの

- 検索フォームに `<select name="tag">` を追加(未選択=絞り込まない、を選べる選択肢を先頭に)
- 選択肢は `tags` テーブルの全件(取得方法は自由。今回は Service/専用 Repository を新設しないので、
  `ProjectRepository` に一覧取得メソッドを足すか、`public/` で直接読むかは自分で決めてよい。
  ただし SQL を書くなら `ProjectRepository` 経由にすること — ARC-2)
- 選ばれた `tag` を整数に変換して `searchOpen()` に渡す(未指定・数値でない値・存在しない id は
  `null` 扱いにする。存在しない id をエラー画面にしない — F-03 の空条件と同じ考え方)

## database/schema.sql に足すもの(D-012 の設計どおり)

```sql
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE project_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL REFERENCES projects(id),
    tag_id INTEGER NOT NULL REFERENCES tags(id),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE (project_id, tag_id)
);
```

## database/seeds.php に足すもの

`seeds.php` が返す配列に、次の2つのキーを追加する(名前は固定 — check がこの形で読みに行く):

- `'tags'`: `['name' => 'タグ名']` の配列(1件以上。例: PHP・SQL・短期・長期・未経験歓迎)
- `'project_tags'`: `['project' => N, 'tag' => M]` の配列(1件以上。`N`/`M` は
  `projects` / `tags` 配列の**何番目か**(1始まり)。既存の `applications` が `'project' => 1` の
  形で案件を指しているのと同じ考え方)

既存の `companies` / `users` / `projects` / `applications` の並びと件数は変更しない
(T-000〜T-014 の再現条件がそこに依存しているため)。

## tools/init-db.php への追加

`seeds.php` に `tags` / `project_tags` を足しただけでは DB に入りません。他のテーブルと同じ形で、
`tags` → `project_tags` の順に INSERT する処理を足してください。`project_tags` は
`project` / `tag` の**配列内の番号**を、実際に INSERT された id に変換する必要があります
(既存の `applications` 投入部分が、`$projectIds[$application['project']]` という形で
同じ変換をしています。これが手本になります)。

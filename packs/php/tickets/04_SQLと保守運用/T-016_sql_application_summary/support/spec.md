# T-016 仕様メモ

参照する設計資料:

- `docs/01_設計資料/database.md` の `projects` テーブル
- `docs/01_設計資料/database.md` の `applications` テーブル
- ER図の `projects ||--o{ applications`

必要な条件:

- 案件名は `projects.title`
- 応募は `applications`
- `applications.project_id = projects.id` でつながる
- 対象は `projects.status = 'open'`
- 応募が0件の open 案件も出す
- 承認済み応募は `applications.status = 'accepted'`

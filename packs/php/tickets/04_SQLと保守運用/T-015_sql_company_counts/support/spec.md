# T-015 仕様メモ

参照する設計資料:

- `docs/01_設計資料/database.md` の `companies` テーブル
- `docs/01_設計資料/database.md` の `projects` テーブル
- ER図の `companies ||--o{ projects`

必要な条件:

- 企業名は `companies.name`
- 案件は `projects`
- 企業と案件は `projects.company_id = companies.id` でつながる
- `projects.status = 'open'` の案件だけを数える
- 返す列名は `company_name`, `open_project_count`

# T-015 hints

## Hint 1

企業名は `projects` にはありません。
企業名を出すには `companies` とつなぐ必要があります。

## Hint 2

企業と案件はこの条件でつながります。

```sql
projects.company_id = companies.id
```

## Hint 3

企業ごとにまとめるには `GROUP BY`、件数を数えるには `COUNT(*)` を使います。
列名は `AS company_name`, `AS open_project_count` で揃えてください。

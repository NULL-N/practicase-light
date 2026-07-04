# D-012 rubric(提出前のセルフチェック)

- [ ] 多対多と判定した理由が、両方向(案件→タグ/タグ→案件)から書かれている
- [ ] `tags.name` に UNIQUE がある(タグ名の重複を防いでいる)
- [ ] `project_tags` に UNIQUE(project_id, tag_id) 相当がある(二重付与を防いでいる)
- [ ] 外部キー(project_id → projects.id / tag_id → tags.id)を定義した(D-5)
- [ ] created_at / updated_at を忘れていない(D-1)
- [ ] 「最大3つ」を DB 制約で無理に守ろうとしていない(D-4: アプリ層の仕事として次工程へ)
- [ ] テーブル定義の表と ER 図の**両方**を更新した

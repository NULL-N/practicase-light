# T-017 hints(1段ずつ開く。15分粘ってから)

## 段1: どこから手を付けるか

依存の順に進めると迷いません。

1. `database/schema.sql` に `tags` / `project_tags` を追加(D-012 の設計をそのまま使う)
2. `database/seeds.php` にタグデータと紐づけを追加(`support/spec.md` のキー名で)
3. `tools/init-db.php` に、その投入処理を追加(既存の `applications` の書き方が手本)
4. ここまでできたら `docker compose exec app php tools/init-db.php` を実行して、エラーが出ないこと・
   件数の出力にタグが含まれることを確認する
5. `ProjectRepository::searchOpen()` にタグ条件を追加
6. `public/projects/index.php` に選択肢を追加し、ブラウザで実際に絞り込んで確かめる

## 段2: SQL の組み立てで迷ったら

既存の `search()` は「WHERE 句を配列で積んで implode する」形です。タグは WHERE ではなく
**JOIN を条件つきで足す**ほうが素直です(`$tagId !== null` のときだけ `$sql` に JOIN 文を連結する)。
`project_tags` に `UNIQUE(project_id, tag_id)` があるので、1案件に同じタグが2回付くことはなく、
JOIN しても行が増える心配はありません。

## 段3: 選択肢(プルダウン)の取得先で迷ったら

「専用クラスを作らない」と決めたので、`ProjectRepository` に `findAllTags()` のような
1メソッドを足すのが最短です。SQL を `public/` に直接書くのは避けてください(ARC-2)。

## 段4: それでも詰まったら

この配布物に模範コードは含まれていません。詰まったら、D-013 で自分が書いた詳細設計
(`reports/D-013_detail_design.md`)を読み返してください。
「実装者が迷わない」設計になっていたか、答え合わせの機会でもあります。

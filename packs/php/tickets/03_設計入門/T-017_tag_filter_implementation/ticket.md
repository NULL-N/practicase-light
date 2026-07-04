---
id: T-017
title: 案件一覧をタグで絞り込めるようにする
level: 3
track: dev
type: feature
priority: normal
estimated_minutes: 150
role: developer
status: open
scope:
  - "packs/php/app/database/schema.sql"
  - "packs/php/app/database/seeds.php"
  - "packs/php/app/src/Repository/ProjectRepository.php"
  - "packs/php/app/public/projects/index.php"
  - "tools/init-db.php"
  - "packs/php/app/tests/**"
depends_on:
  - "D-013"
pack: php
---

# T-017: 案件一覧をタグで絞り込めるようにする

**起票: 早瀬(開発リーダー)**

D-010〜D-013 で積み上げてきた「案件タグ(F-20)」の設計に、実装を付けます。
**あなた自身が D-013 で書いた詳細設計を、今度は実装者として読む番です。**

早瀬さんから、今回のスコープについて申し送りがあります:

> 一気に全部作ると初回リリースが重くなるので、**最初は検索の絞り込みだけ**に絞ります。
> タグを案件に「付ける」画面(S-12 側)は、まだ用意しません。今回はシードデータで
> 案件にタグが付いている状態を作り、**絞り込みが正しく動くこと**を先に固めます。
> `TagRepository` も今回は新設せず、`ProjectRepository` に寄せてください
> (専用クラスを切り出すかどうかは、S-12 側を作るときにもう一度判断します)。

## 実装範囲

| 対象 | やること |
|---|---|
| `database/schema.sql` | `tags` / `project_tags` テーブルを追加(D-012 の設計どおり) |
| `database/seeds.php` + `tools/init-db.php` | タグを数件追加し、いくつかの案件に紐づける(**両方直さないと投入されません**) |
| `src/Repository/ProjectRepository.php` | `searchOpen()` にタグ絞り込みを追加 |
| `public/projects/index.php` | 検索フォームにタグの `<select name="tag">` を追加し、選ばれた値を Repository に渡すだけ(判断はしない) |

Service 層は増やしません。「何個まで付けられるか」「存在しない id」といった検証は、
タグを**付ける**画面(S-12)を作るときの話です。今回は検索だけなので、存在しない値・
数値でない値は F-03 の空条件と同じ扱い(絞り込みなし)で構いません。

詳しい仕様は `support/spec.md`。詰まったら `support/hints.md` を段階的に。

## 完了条件

- `check T-017` が PASS
- 既存の検索(キーワード・リモート可のみ)が壊れていない(共通テスト green)
- retrospective に、**自分が D-011〜D-013 で設計した内容と、実際の実装が食い違った点**を書く
  (Service 層を作らなかった・S-12 側は対象外にした、など。ズレを隠さず書くのが実務です)

<?php

declare(strict_types=1);

namespace App\Support;

// tutorial-2: タグ別の案件数を集計する部品。画面への接続は別チケットの予定で、まず部品だけ先に作る。
// 何ができたら完成かは tests/checks/tutorial-2.php に固定されている — テストが仕様書
final class TagSummary
{
    /**
     * 案件の配列から、タグごとの件数を数える。
     *
     * 例: [['title' => 'A', 'tags' => ['PHP', 'SQL']], ['title' => 'B', 'tags' => ['PHP']]]
     *     → ['PHP' => 2, 'SQL' => 1]
     *
     * @param array<int, array{title: string, tags: string[]}> $projects
     * @return array<string, int> タグ名 => 件数
     */
    public static function countByTag(array $projects): array
    {
        // TODO(tutorial-2): ここに実装する。
        // チケットの設計指定: foreach を2つ重ねる(外で案件を1件ずつ、内でその案件のタグを1つずつ)
        return [];
    }
}

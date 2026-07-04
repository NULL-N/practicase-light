<?php

declare(strict_types=1);

use App\Repository\ProjectRepository;

// T-017 の合格条件。schema.sql の tags / project_tags と、
// ProjectRepository::searchOpen(...) へのタグ絞り込み追加(support/spec.md)を検証する。
// スキーマは各自の schema.sql から freshDatabase() が読み込むため、
// tags / project_tags が定義されていなければ、このテスト自体が例外で FAIL する。

// この check だけで使うローカルな投入ヘルパ(共通の tests/bootstrap.php は変更しない)
function t017InsertTag(\PDO $pdo, string $name): int
{
    $stmt = $pdo->prepare('INSERT INTO tags (name, created_at, updated_at) VALUES (:name, :now, :now)');
    $stmt->execute(['name' => $name, 'now' => '2026-01-01 00:00:00']);

    return (int) $pdo->lastInsertId();
}

function t017AttachTag(\PDO $pdo, int $projectId, int $tagId): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO project_tags (project_id, tag_id, created_at, updated_at) VALUES (:project_id, :tag_id, :now, :now)'
    );
    $stmt->execute(['project_id' => $projectId, 'tag_id' => $tagId, 'now' => '2026-01-01 00:00:00']);
}

test('T-017: タグ指定で絞り込める(該当タグの案件だけになる)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $php = insertProject($pdo, $companyId, ['title' => 'PHP案件', 'deadline' => '2026-07-10']);
    insertProject($pdo, $companyId, ['title' => '他言語案件', 'deadline' => '2026-07-10']);
    $phpTagId = t017InsertTag($pdo, 'PHP');
    t017AttachTag($pdo, (int) $php['id'], $phpTagId);

    $repository = new ProjectRepository();
    $result = $repository->searchOpen('', false, '2026-07-01', $phpTagId);

    assertSame(1, count($result), 'タグ指定時は該当案件だけになるようにしてください');
    assertSame('PHP案件', $result[0]['title']);
});

test('T-017: タグ未指定なら従来どおり絞り込まない(後方互換)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    insertProject($pdo, $companyId, ['title' => 'A案件', 'deadline' => '2026-07-10']);
    insertProject($pdo, $companyId, ['title' => 'B案件', 'deadline' => '2026-07-10']);

    $repository = new ProjectRepository();
    assertSame(
        2,
        count($repository->searchOpen('', false, '2026-07-01')),
        'タグ引数を渡さずに呼べるようにしてください(既存の呼び出し元との後方互換)'
    );
    assertSame(2, count($repository->searchOpen('', false, '2026-07-01', null)), 'null 指定でも絞り込まないでください');
});

test('T-017: タグとリモート可のみは AND で組み合わさる(F-20)', function (): void {
    $pdo = freshDatabase();
    $companyId = insertCompany($pdo);
    $phpTagId = t017InsertTag($pdo, 'PHP');
    $remotePhp = insertProject($pdo, $companyId, ['title' => 'リモートPHP', 'is_remote' => 1, 'deadline' => '2026-07-10']);
    $officePhp = insertProject($pdo, $companyId, ['title' => '出社PHP', 'is_remote' => 0, 'deadline' => '2026-07-10']);
    insertProject($pdo, $companyId, ['title' => 'リモート他言語', 'is_remote' => 1, 'deadline' => '2026-07-10']);
    t017AttachTag($pdo, (int) $remotePhp['id'], $phpTagId);
    t017AttachTag($pdo, (int) $officePhp['id'], $phpTagId);

    $repository = new ProjectRepository();
    $result = $repository->searchOpen('', true, '2026-07-01', $phpTagId);

    assertSame(1, count($result), 'タグ AND リモート可のみ で、両方満たす案件だけにしてください');
    assertSame('リモートPHP', $result[0]['title']);
});

test('T-017: 案件一覧の検索フォームにタグの選択肢がある', function (): void {
    $source = (string) file_get_contents(__DIR__ . '/../../public/projects/index.php');
    assertTrue(
        preg_match('/<select[^>]*name=["\']tag["\']/u', $source) === 1,
        'public/projects/index.php にタグ選択(<select name="tag">)を追加してください'
    );
});

test('T-017: seeds にタグのデータが追加されている(ブラウザで確認できるように)', function (): void {
    $seeds = require __DIR__ . '/../../database/seeds.php';
    assertTrue(
        isset($seeds['tags']) && is_array($seeds['tags']) && count($seeds['tags']) >= 1,
        "database/seeds.php に 'tags' の配列を追加してください(support/spec.md のキー名で)"
    );
    assertTrue(
        isset($seeds['project_tags']) && is_array($seeds['project_tags']) && count($seeds['project_tags']) >= 1,
        "database/seeds.php に 'project_tags' の配列を追加してください(案件とタグの紐づけ)"
    );
});

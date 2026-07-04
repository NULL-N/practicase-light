<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

// ARC-2: projects テーブルへの SQL はこのクラスに集約する
final class ProjectRepository
{
    /**
     * F-03: engineer 向け検索。
     * 母集合は「open かつ締切が $today 以降(当日を含む = G-2)」。
     * $today は呼び出し側が Clock から渡す(ARC-5: 日時の取得元を1箇所に保つ)。
     */
    public function searchOpen(string $keyword, bool $remoteOnly, string $today): array
    {
        return $this->search($keyword, $remoteOnly, onlyOpenSince: $today);
    }

    // F-03: admin 向け検索(全件・全 status)
    public function searchAll(string $keyword, bool $remoteOnly): array
    {
        return $this->search($keyword, $remoteOnly, onlyOpenSince: null);
    }

    private function search(string $keyword, bool $remoteOnly, ?string $onlyOpenSince): array
    {
        $sql = 'SELECT p.*, c.name AS company_name
                FROM projects p
                JOIN companies c ON c.id = p.company_id';
        $where = [];
        $params = [];

        if ($onlyOpenSince !== null) {
            $where[] = "p.status = 'open' AND p.deadline >= :today";
            $params['today'] = $onlyOpenSince;
        }
        if ($keyword !== '') {
            // LIKE 特殊文字はエスケープしてリテラル扱いにする(F-03)
            $where[] = "p.title LIKE :kw ESCAPE '\\'";
            $like = '%' . self::escapeLike($keyword) . '%';
            $params['kw'] = $like;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // F-03: 締切が近い順、同日は id 降順。最大50件
        $sql .= ' ORDER BY p.deadline ASC, p.id DESC LIMIT 50';

        $stmt = Database::pdo()->prepare($sql); // SEC-1
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, c.name AS company_name
             FROM projects p JOIN companies c ON c.id = p.company_id
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $project = $stmt->fetch();

        return $project === false ? null : $project;
    }

    // S-11: 自社案件一覧(応募数・承認数付き)
    public function listByCompany(int $companyId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM applications a WHERE a.project_id = p.id) AS application_count,
                    (SELECT COUNT(*) FROM applications a WHERE a.project_id = p.id AND a.status = 'accepted') AS accepted_count
             FROM projects p
             WHERE p.company_id = :company_id
             ORDER BY p.id DESC"
        );
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll();
    }

    /** @param array $values ProjectValidator が正規化した値 */
    public function create(int $companyId, array $values, string $now): int
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO projects (company_id, title, description, hourly_rate, capacity,
                                   deadline, work_start_on, is_remote, status, created_at, updated_at)
             VALUES (:company_id, :title, :description, :hourly_rate, :capacity,
                     :deadline, :work_start_on, :is_remote, 'open', :now, :now2)"
        );
        $stmt->execute([
            'company_id' => $companyId,
            'title' => $values['title'],
            'description' => $values['description'],
            'hourly_rate' => $values['hourly_rate'],
            'capacity' => $values['capacity'],
            'deadline' => $values['deadline'],
            'work_start_on' => $values['work_start_on'],
            'is_remote' => $values['is_remote'],
            'now' => $now,
            'now2' => $now,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function close(int $id, string $now): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE projects SET status = 'closed', updated_at = :now WHERE id = :id"
        );
        $stmt->execute(['now' => $now, 'id' => $id]);
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}

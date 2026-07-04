<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

// ARC-2: applications テーブルへの SQL はこのクラスに集約する
final class ApplicationRepository
{
    public function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM applications WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $application = $stmt->fetch();

        return $application === false ? null : $application;
    }

    // A-4: withdrawn も「応募履歴」に含むため、status を問わず存在を見る
    public function hasAppliedEver(int $projectId, int $engineerId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) AS cnt FROM applications
             WHERE project_id = :project_id AND engineer_id = :engineer_id'
        );
        $stmt->execute(['project_id' => $projectId, 'engineer_id' => $engineerId]);

        return (int) $stmt->fetch()['cnt'] > 0;
    }

    // F-06: 全状態の応募を応募日時の新しい順で
    public function listByProject(int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, u.name AS engineer_name
             FROM applications a
             JOIN users u ON u.id = a.engineer_id
             WHERE a.project_id = :project_id
             ORDER BY a.applied_at DESC, a.id DESC'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll();
    }

    // S-06: マイ応募一覧
    public function listByEngineer(int $engineerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, p.title AS project_title, c.name AS company_name
             FROM applications a
             JOIN projects p ON p.id = a.project_id
             JOIN companies c ON c.id = p.company_id
             WHERE a.engineer_id = :engineer_id
             ORDER BY a.applied_at DESC, a.id DESC'
        );
        $stmt->execute(['engineer_id' => $engineerId]);

        return $stmt->fetchAll();
    }

    // D-3: 承認数は処理時点の DB で数える(F-07)
    public function countAccepted(int $projectId): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) AS cnt FROM applications
             WHERE project_id = :project_id AND status = 'accepted'"
        );
        $stmt->execute(['project_id' => $projectId]);

        return (int) $stmt->fetch()['cnt'];
    }

    public function create(int $projectId, int $engineerId, string $message, string $now): int
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO applications (project_id, engineer_id, message, status, applied_at, created_at, updated_at)
             VALUES (:project_id, :engineer_id, :message, 'applied', :applied_at, :now, :now2)"
        );
        $stmt->execute([
            'project_id' => $projectId,
            'engineer_id' => $engineerId,
            'message' => $message,
            'applied_at' => $now,
            'now' => $now,
            'now2' => $now,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    // applied → accepted / rejected / withdrawn の確定(decided_at を記録)
    public function decide(int $id, string $status, string $decidedAt): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE applications
             SET status = :status, decided_at = :decided_at, updated_at = :decided_at2
             WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'decided_at' => $decidedAt, 'decided_at2' => $decidedAt, 'id' => $id]);
    }
}

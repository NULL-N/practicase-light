<?php

declare(strict_types=1);

namespace App\Service;

/**
 * F-02: 案件登録の検証。ルールとエラー文言の正は docs/01_設計資料/features.md。
 * 文言はテストの期待値になるため、必ず定数を経由する(FLOW-3)。
 */
final class ProjectValidator
{
    public const MSG_TITLE_REQUIRED = '案件名を入力してください';
    public const MSG_TITLE_TOO_LONG = '案件名は100文字以内で入力してください';
    public const MSG_DESCRIPTION_REQUIRED = '案件内容を入力してください';
    public const MSG_DESCRIPTION_TOO_LONG = '案件内容は2000文字以内で入力してください';
    public const MSG_HOURLY_RATE_INVALID = '時間単価は1円以上10万円以下の整数で入力してください';
    public const MSG_CAPACITY_INVALID = '募集人数は1人以上100人以下の整数で入力してください';
    public const MSG_DEADLINE_INVALID = '応募締切日は本日以降の日付を指定してください';
    public const MSG_WORK_START_INVALID = '稼働開始日は応募締切日以降の日付を指定してください';

    /**
     * @return array{errors: array<string, string>, values: array<string, mixed>}
     *         errors が空なら values は登録に使える正規化済みの値
     */
    public function validate(array $input): array
    {
        $errors = [];
        $values = [];

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            $errors['title'] = self::MSG_TITLE_REQUIRED;
        } elseif (mb_strlen($title) > 100) {
            $errors['title'] = self::MSG_TITLE_TOO_LONG;
        }
        $values['title'] = $title;

        $description = trim((string) ($input['description'] ?? ''));
        if ($description === '') {
            $errors['description'] = self::MSG_DESCRIPTION_REQUIRED;
        } elseif (mb_strlen($description) > 2000) {
            $errors['description'] = self::MSG_DESCRIPTION_TOO_LONG;
        }
        $values['description'] = $description;

        $hourlyRate = (string) ($input['hourly_rate'] ?? '');
        if ($hourlyRate === '') {
            $errors['hourly_rate'] = self::MSG_HOURLY_RATE_INVALID;
        }
        $values['hourly_rate'] = (int) $hourlyRate;

        $capacity = (string) ($input['capacity'] ?? '');
        if ($capacity === '') {
            $errors['capacity'] = self::MSG_CAPACITY_INVALID;
        }
        $values['capacity'] = (int) $capacity;

        $deadline = self::parseDate((string) ($input['deadline'] ?? ''));
        if ($deadline === null) {
            $errors['deadline'] = self::MSG_DEADLINE_INVALID;
        }
        $values['deadline'] = $deadline;

        $workStartOn = self::parseDate((string) ($input['work_start_on'] ?? ''));
        if ($workStartOn === null || ($deadline !== null && $workStartOn < $deadline)) {
            $errors['work_start_on'] = self::MSG_WORK_START_INVALID;
        }
        $values['work_start_on'] = $workStartOn;

        $values['is_remote'] = (($input['is_remote'] ?? '') === '1') ? 1 : 0;

        return ['errors' => $errors, 'values' => $values];
    }

    // F-02: 「整数表現の文字列」のみ受理(空・小数・文字混じり・符号・先頭ゼロを拒否)
    private static function parseInt(string $value): ?int
    {
        if (preg_match('/\A(0|[1-9][0-9]*)\z/', $value) !== 1) {
            return null;
        }

        return (int) $value;
    }

    // F-02: YYYY-MM-DD 形式かつ実在する日付のみ受理
    private static function parseDate(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }
}

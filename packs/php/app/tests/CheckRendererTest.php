<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../tools/lib/CheckRenderer.php';

use PractiCase\Check\CheckEdition;
use PractiCase\Check\CheckRenderer;

// CheckRenderer は check.php の出力契約(文言・改行・順序)の正本。
// ここの期待文字列はゴールデンベースライン比較が読むマーカーそのものなので、
// 変更するときは必ずベースライン比較(verify-check-baseline)を通すこと。

/** レンダラーの標準出力を文字列として捕まえる */
function captureRenderer(callable $render): string
{
    ob_start();
    $render();

    return (string) ob_get_clean();
}

/**
 * @return list<string>
 */
function ticketWorkflowViolations(string $relative, string $content, bool $hasDebrief): array
{
    $violations = [];
    if (preg_match('/support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z])/iu', $content) !== 1) {
        $violations[] = $relative . ': rubric参照がありません';
    }
    if (preg_match('/Pull Request|(?<![A-Za-z])PR(?![A-Za-z])/u', $content) !== 1) {
        $violations[] = $relative . ': Pull Request参照がありません';
    }
    if (stripos($content, 'Redmine') === false) {
        $violations[] = $relative . ': Redmine参照がありません';
    }
    if ($hasDebrief && stripos($content, 'debrief') === false) {
        $violations[] = $relative . ': debrief参照がありません';
    }

    $insideProcedure = false;
    $preRubricPosition = null;
    $submissionPosition = null;
    $pushPosition = null;
    $pullRequestCreationPosition = null;
    $debriefPosition = null;
    $pullRequestMergePosition = null;
    $retrospectivePosition = null;
    $closedPosition = null;
    $postRubricPosition = null;
    $commonProcedurePosition = null;

    foreach (preg_split('/\R/u', $content) ?: [] as $lineIndex => $line) {
        $position = $lineIndex + 1;
        if (preg_match('/^##\s/u', $line) === 1) {
            if (trim($line) === '## 提出と完了(共通手順)') {
                $insideProcedure = true;
                $commonProcedurePosition ??= $position;
            } else {
                $insideProcedure = false;
            }
            continue;
        }
        if (preg_match('/^\d+\.\s/u', $line) === 1) {
            $insideProcedure = true;
        }
        if (!$insideProcedure) {
            continue;
        }
        if (
            $preRubricPosition === null
            && preg_match(
                '/(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z])).{0,80}(?:提出前|PR作成前)/iu',
                $line
            ) === 1
        ) {
            $preRubricPosition = $position;
        }
        if (
            $postRubricPosition === null
            && preg_match(
                '/(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z])).{0,80}(?:提出後|実施後|マージ後)/iu',
                $line
            ) === 1
        ) {
            $postRubricPosition = $position;
        }
        if (
            $submissionPosition === null
            && preg_match(
                '/(?<![A-Za-z])(?:commit|push)(?![A-Za-z])\s*(?:する(?!\s*前)|して|し(?=[、,・]))|コミット\s*(?:する(?!\s*前)|して|し(?=[、,・]))|(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))\s*を\s*(?:作る|作成する|開く)/iu',
                $line
            ) === 1
        ) {
            $submissionPosition = $position;
        }
        if (
            $pushPosition === null
            && preg_match(
                '/git\s+push|(?<![A-Za-z])push(?![A-Za-z])\s*(?:する(?!\s*前)|して|し(?=[、,・]))/iu',
                $line
            ) === 1
        ) {
            $pushPosition = $position;
        }
        if (
            $pullRequestCreationPosition === null
            && preg_match(
                '/(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))\s*を\s*(?:作る|作成する|開く)/iu',
                $line
            ) === 1
        ) {
            $pullRequestCreationPosition = $position;
        }
        if ($debriefPosition === null && preg_match('/^\d+\..*support\/debrief/iu', $line) === 1) {
            $debriefPosition = $position;
        }
        if (
            $retrospectivePosition === null
            && preg_match('/(?:振り返り|retrospective).{0,60}(?:書く|書き|作る)|(?:書く|書き).{0,30}(?:振り返り|retrospective)/iu', $line) === 1
        ) {
            $retrospectivePosition = $position;
        }
        if (
            $closedPosition === null
            && stripos($line, 'Closed') !== false
            && preg_match('/Closed.{0,20}(?:には|に)?(?:まだ)?しない/iu', $line) !== 1
            && preg_match('/Redmine|ステータス|status/iu', $line) === 1
        ) {
            $closedPosition = $position;
        }
        if (
            $pullRequestMergePosition === null
            && preg_match('/(?:まだ\s*)?(?:merge|マージ)しない/iu', $line) !== 1
            && preg_match('/(?:merge|マージ)前/iu', $line) !== 1
            && preg_match(
                '/(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z])).{0,80}(?:merge|マージ)|Merge pull request/iu',
                $line
            ) === 1
        ) {
            $pullRequestMergePosition = $position;
        }
    }

    if ($preRubricPosition !== null && $submissionPosition !== null && $submissionPosition < $preRubricPosition) {
        $violations[] = $relative . ': commit・push・Pull Requestがrubricより先です';
    }
    if (
        $preRubricPosition === null
        && preg_match(
            '/(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z]))[\s\S]{0,120}(?:提出前|PR作成前)/iu',
            $content
        ) !== 1
    ) {
        $violations[] = $relative . ': rubricの提出前またはPR作成前を確認する手順がありません';
    }
    if (
        $debriefPosition !== null
        && $pullRequestMergePosition !== null
        && $debriefPosition < $pullRequestMergePosition
    ) {
        $violations[] = $relative . ': debriefがPull Requestのmergeより先です';
    }
    if (preg_match('/git\s+switch\s+-c\s+feature\//iu', $content) !== 1) {
        $violations[] = $relative . ': 作業branchを作るgit switch -cの手順がありません';
    }
    if (preg_match('/^id:\s*([^\r\n]+)/mu', $content, $idMatch) === 1) {
        $rawTicketId = trim($idMatch[1]);
        $ticketId = preg_quote($rawTicketId, '/');
        if (preg_match('/docker\s+compose\s+exec\s+app\s+php\s+tools\/check\.php\s+' . $ticketId . '(?=[`\s]|$)/imu', $content) !== 1) {
            $violations[] = $relative . ': 課題IDを指定した完全なcheckコマンドがありません';
        }
        preg_match_all(
            '/feature\/redmine-(?:<チケット番号>|\d+)-' . $ticketId . '-([a-z0-9-]+)/iu',
            $content,
            $branchMatches
        );
        if (count(array_unique($branchMatches[1] ?? [])) > 1) {
            $violations[] = $relative . ': Redmine用branch名の短い名前が本文内で一致しません';
        }
        if (preg_match('/feature\/redmine-\d+-' . $ticketId . '-[a-z0-9-]+/iu', $content) !== 1) {
            $violations[] = $relative . ': 数字を入れたRedmine用branch名の具体例がありません';
        }
    }
    if (
        $pullRequestCreationPosition !== null
        && ($pushPosition === null || $pushPosition > $pullRequestCreationPosition)
    ) {
        $violations[] = $relative . ': Pull Request作成前のpush手順がありません';
    }
    if ($pullRequestMergePosition === null) {
        $violations[] = $relative . ': 提出Pull Requestのmerge手順がありません';
    }
    if (
        $retrospectivePosition !== null
        && $pullRequestMergePosition !== null
        && $retrospectivePosition < $pullRequestMergePosition
    ) {
        $violations[] = $relative . ': 振り返りがPull Requestのmergeより先です';
    }
    if (
        $closedPosition !== null
        && $postRubricPosition !== null
        && $postRubricPosition < $closedPosition
    ) {
        $violations[] = $relative . ': 提出後rubricがRedmineのClosedより先です';
    }
    if ($postRubricPosition === null) {
        $violations[] = $relative . ': Closed後の提出後rubric確認がありません';
    }
    if (
        $commonProcedurePosition !== null
        && $closedPosition !== null
        && $closedPosition < $commonProcedurePosition
    ) {
        $violations[] = $relative . ': RedmineのClosed後に共通提出手順が再開します';
    }

    if (
        preg_match(
            '/Redmineへ[\s\S]{0,300}(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))のURL[\s\S]{0,300}(?:振り返り|retrospective)/iu',
            $content
        ) !== 1
    ) {
        $violations[] = $relative . ': RedmineへPull RequestのURLと振り返りを報告する手順がありません';
    }
    if (
        preg_match(
            '/(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))[^\r\n]{0,100}(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z]))/iu',
            $content
        ) === 1
    ) {
        $violations[] = $relative . ': Pull Requestとrubricの順序が同じ行で逆転しています';
    }
    if (
        preg_match(
            '/(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z]))[^\r\n]{0,100}(?:Resolved|Closed)/iu',
            $content
        ) === 1
    ) {
        $violations[] = $relative . ': rubric確認と完了操作が同じ行です';
    }

    return $violations;
}

/**
 * @return list<string>
 */
function rubricWorkflowViolations(string $relative, string $content, bool $hasDebrief): array
{
    $violations = [];
    preg_match('/^##\s+.*(?:提出前|commit前|PR作成前)/imu', $content, $beforeMatch, PREG_OFFSET_CAPTURE);
    preg_match('/^##\s+.*(?:提出後|実施後|マージ後)/imu', $content, $afterMatch, PREG_OFFSET_CAPTURE);

    $beforePosition = $beforeMatch[0][1] ?? null;
    $afterPosition = $afterMatch[0][1] ?? null;
    if ($beforePosition === null) {
        $violations[] = $relative . ': rubricに提出前の確認段階がありません';
    }
    if ($afterPosition === null) {
        $violations[] = $relative . ': rubricに提出後の確認段階がありません';
    }
    if ($beforePosition !== null && $afterPosition !== null && $afterPosition < $beforePosition) {
        $violations[] = $relative . ': rubricの提出後が提出前より先です';
    }
    if ($beforePosition !== null) {
        $beforeStart = $beforePosition + strlen((string) $beforeMatch[0][0]);
        $beforeTail = substr($content, $beforeStart);
        preg_match('/^##\s+/mu', $beforeTail, $nextHeading, PREG_OFFSET_CAPTURE);
        $beforeBody = $nextHeading === []
            ? $beforeTail
            : substr($beforeTail, 0, $nextHeading[0][1]);
        if (preg_match('/^- \[ \]/mu', $beforeBody) !== 1) {
            $violations[] = $relative . ': rubricの提出前見出し直下に確認項目がありません';
        }
        $beforeRegionLength = $afterPosition === null
            ? null
            : $afterPosition - $beforePosition;
        $beforeRegion = substr($content, $beforePosition, $beforeRegionLength);
        if (
            preg_match('/(?<![A-Za-z])check(?![A-Za-z])/iu', $beforeRegion) !== 1
            || stripos($beforeRegion, 'PASS') === false
        ) {
            $violations[] = $relative . ': rubricの提出前段階にcheckのPASS確認がありません';
        }
    }
    if ($afterPosition !== null) {
        $afterRegion = substr($content, $afterPosition);
        if (preg_match('/^- \[ \]/mu', $afterRegion) !== 1) {
            $violations[] = $relative . ': rubricの提出後見出し以降に確認項目がありません';
        }
        if (stripos($afterRegion, 'Closed') === false) {
            $violations[] = $relative . ': rubricの提出後段階にRedmineのClosed確認がありません';
        }
        if (
            preg_match(
                '/(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))[\s\S]{0,120}(?:merge|マージ)|(?:merge|マージ)[\s\S]{0,120}(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))/iu',
                $afterRegion
            ) !== 1
        ) {
            $violations[] = $relative . ': rubricの提出後段階にPull Requestのmerge確認がありません';
        }
        if (preg_match('/振り返り|retrospective/iu', $afterRegion) !== 1) {
            $violations[] = $relative . ': rubricの提出後段階に振り返りの確認がありません';
        }
        if ($hasDebrief && stripos($afterRegion, 'debrief') === false) {
            $violations[] = $relative . ': rubricの提出後段階にdebriefの確認がありません';
        }
        if (preg_match(
            '/PASS[\s\S]{0,300}(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))のURL[\s\S]{0,300}(?:振り返り|retrospective)[\s\S]{0,300}Closed/iu',
            $afterRegion
        ) !== 1
        ) {
            $violations[] = $relative . ': rubricの提出後にRedmineへのPASS・PR URL・振り返り・Closed確認がありません';
        }
    }

    return $violations;
}

/** @return list<string> */
function unavailableDebriefReferenceViolations(
    string $relative,
    string $content,
    bool $hasDebrief
): array
{
    if ($hasDebrief) {
        return [];
    }

    $violations = [];
    foreach (preg_split('/\R/u', $content) ?: [] as $lineIndex => $line) {
        if (stripos($line, 'debrief') === false) {
            continue;
        }
        if (preg_match('/debrief[^\r\n]{0,40}(?:がある|が存在する)(?:課題|場合)/iu', $line) === 1) {
            continue;
        }

        $violations[] = $relative . ':' . ($lineIndex + 1) . ': 実在しないdebriefを前提にしています';
    }

    return $violations;
}

/**
 * @param list<array{string, string}> $steps
 * @return list<string>
 */
function orderedGuideWorkflowViolations(string $relative, string $content, array $steps): array
{
    $violations = [];
    $offset = 0;

    foreach ($steps as [$label, $pattern]) {
        if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE, $offset) !== 1) {
            $violations[] = $relative . ': ' . $label . 'がないか、標準提出順序と一致しません';
            break;
        }
        $offset = $match[0][1] + strlen($match[0][0]);
    }

    return $violations;
}

/** @return array{int, int}|null */
function guideMarkerPosition(string $content, string $pattern, int $offset): ?array
{
    if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE, $offset) !== 1) {
        return null;
    }

    return [$match[0][1], $match[0][1] + strlen($match[0][0])];
}

/** @return list<string> */
function guideCompletionWorkflowViolations(string $relative, string $content): array
{
    $merge = guideMarkerPosition(
        $content,
        '/(?:merge|マージ)(?:する|します|した|しました|済み|後)(?!\s*前)/iu',
        0
    );
    if ($merge === null) {
        return [$relative . ': Pull Requestのmerge手順がありません'];
    }

    $retrospective = guideMarkerPosition(
        $content,
        '/(?:振り返り|retrospective)[^\r\n]{0,100}(?:書く|書き|記録する|記録し)|(?:書く|書き|記録する|記録し)[^\r\n]{0,100}(?:振り返り|retrospective)/iu',
        $merge[1]
    );
    if ($retrospective === null) {
        return [$relative . ': Pull Requestをmergeした後に振り返りを書く手順がありません'];
    }

    $reportPatterns = [
        'Redmine' => '/Redmine/iu',
        'PASS結果' => '/PASS/iu',
        'Pull RequestのURL' => '/(?:Pull Request|(?<![A-Za-z])PR(?![A-Za-z]))[^\r\n]{0,100}URL/iu',
        '振り返り' => '/振り返り|retrospective/iu',
    ];
    $reportEnd = $retrospective[1];
    $violations = [];
    foreach ($reportPatterns as $label => $pattern) {
        $marker = guideMarkerPosition($content, $pattern, $retrospective[1]);
        if ($marker === null) {
            $violations[] = $relative . ': merge後のRedmine報告に' . $label . 'がありません';
            continue;
        }
        $reportEnd = max($reportEnd, $marker[1]);
    }
    if ($violations !== []) {
        return $violations;
    }

    $closed = guideMarkerPosition($content, '/Closed/iu', $reportEnd);
    if ($closed === null) {
        return [$relative . ': Redmine報告後のClosed手順がありません'];
    }
    $postRubric = guideMarkerPosition(
        $content,
        '/(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z]))[\s\S]{0,160}提出後|提出後[\s\S]{0,160}(?:support\/rubric\.md|(?<![A-Za-z])rubric(?![A-Za-z]))/iu',
        $closed[1]
    );
    if ($postRubric === null) {
        return [$relative . ': Closed後のrubric提出後確認がありません'];
    }

    return [];
}

function guideWorkflowSection(string $content, string $start, string $end): string
{
    $startPosition = strpos($content, $start);
    $endPosition = $startPosition === false ? false : strpos($content, $end, $startPosition + strlen($start));
    if ($startPosition === false || $endPosition === false) {
        return '';
    }

    return substr($content, $startPosition, $endPosition - $startPosition);
}

test('header は課題IDとチケット概要を出力する(欠損時は既定値)', function (): void {
    $r = new CheckRenderer();
    $out = captureRenderer(fn () => $r->header('T-000', ['title' => '環境構築', 'level' => 1, 'type' => 'setup']));
    assertSame("=== PractiCase check: T-000 ===\nチケット: 環境構築(Level 1 / setup)\n", $out);

    $out = captureRenderer(fn () => $r->header('T-000', []));
    assertSame("=== PractiCase check: T-000 ===\nチケット: (無題)(Level ? / ?)\n", $out);
});

test('フェーズ見出し3種は先頭に空行を持つ', function (): void {
    $r = new CheckRenderer();
    assertSame("\n[1/3] 共通テスト(既存機能を壊していないか)\n", captureRenderer(fn () => $r->commonTestHeading()));
    assertSame("\n[2/3] 課題別テスト(T-001 の合格条件)\n", captureRenderer(fn () => $r->taskTestHeading('T-001')));
    assertSame("\n[3/3] scope 検査(変更がチケットの範囲内か)\n", captureRenderer(fn () => $r->scopeHeading()));
});

test('phaseLines はフェーズが返した行(改行込みの完成形)をそのままの順で書く', function (): void {
    $r = new CheckRenderer();
    assertSame('', captureRenderer(fn () => $r->phaseLines([])));
    assertSame(
        "  1行目\n  2行目\n",
        captureRenderer(fn () => $r->phaseLines(["  1行目\n", "  2行目\n"]))
    );
});

test('scope エラーは中断表示と結果: FAIL を同時に出す', function (): void {
    $r = new CheckRenderer();
    assertSame(
        "  エラー: ここは git リポジトリではありません。\n\n結果: FAIL\n",
        captureRenderer(fn () => $r->scopeError('ここは git リポジトリではありません。'))
    );
});

test('resultPass: PASS後に提出前レビューと提出後作業を分けて案内する', function (): void {
    $r = new CheckRenderer();
    assertSame(
        "\n結果: PASS — ticket.md の手順へ戻ります\n"
        . "次の操作: support/rubric.md の「提出前」を確認し、この課題のticket.mdに従ってください\n"
        . "注意: commit・push・Pull Request・debrief・Redmine完了のタイミングは課題ごとに異なります\n",
        captureRenderer(fn () => $r->resultPass())
    );
});

$workflowEditionForTests = CheckEdition::load(dirname(__DIR__, 4) . '/tools/check-edition.php');
if ($workflowEditionForTests->listMode === 'grouped') {
test('ticket導線検査はPR・commit・pushがrubricより先なら検出する', function (): void {
    $cases = [
        "1. PRを作る\n2. support/rubric.mdの提出前を確認する\n3. RedmineをClosedにする\n",
        "1. commitする\n2. support/rubric.mdの提出前を確認する\n3. Pull Requestを作る\n4. RedmineをClosedにする\n",
        "1. pushする\n2. support/rubric.mdの提出前を確認する\n3. Pull Requestを作る\n4. RedmineをClosedにする\n",
    ];

    foreach ($cases as $content) {
        $violations = ticketWorkflowViolations('negative-control/ticket.md', $content, false);
        assertTrue(
            in_array('negative-control/ticket.md: commit・push・Pull Requestがrubricより先です', $violations, true),
            "順序違反を検出できませんでした:\n{$content}"
        );
    }
});

test('ticket導線検査は通常rubric参照後でも提出前確認より先のcommitを検出する', function (): void {
    $content = "1. support/rubric.mdを参照する\n"
        . "2. commitする\n"
        . "3. support/rubric.mdの提出前を確認する\n"
        . "4. pushしてPull Requestを作る\n"
        . "5. Pull Requestをmergeする\n"
        . "6. RedmineをClosedにする\n";

    $violations = ticketWorkflowViolations('negative-control/ticket.md', $content, false);
    assertTrue(
        in_array('negative-control/ticket.md: commit・push・Pull Requestがrubricより先です', $violations, true),
        "明示的な提出前確認より先のcommitを検出できませんでした:\n{$content}"
    );
});

test('ticket導線検査はdebriefがPull Requestのmergeより先なら検出する', function (): void {
    $content = "1. support/rubric.mdを確認する\n"
        . "2. Pull Requestを作る\n"
        . "3. support/debrief/を開く\n"
        . "4. Pull Requestをmergeする\n"
        . "5. RedmineをClosedにする\n";

    $violations = ticketWorkflowViolations('negative-control/ticket.md', $content, true);
    assertTrue(
        in_array('negative-control/ticket.md: debriefがPull Requestのmergeより先です', $violations, true),
        "順序違反を検出できませんでした:\n{$content}"
    );
});

test('ticket導線検査はmerge欠落・二重完了・提出後rubric欠落を検出する', function (): void {
    $content = "1. support/rubric.mdの提出前を確認する\n"
        . "2. commit・pushし、Pull Requestを作る\n"
        . "3. 振り返りを書く\n"
        . "4. RedmineへPASSとPull RequestのURLと振り返りを報告し、Closedにする\n"
        . "\n## 提出と完了(共通手順)\n"
        . "1. support/rubric.mdの提出前を確認する\n";

    $violations = ticketWorkflowViolations('negative-control/T-999/ticket.md', $content, false);
    foreach ([
        'negative-control/T-999/ticket.md: 提出Pull Requestのmerge手順がありません',
        'negative-control/T-999/ticket.md: Closed後の提出後rubric確認がありません',
        'negative-control/T-999/ticket.md: RedmineのClosed後に共通提出手順が再開します',
    ] as $expected) {
        assertTrue(in_array($expected, $violations, true), "意味的な順序違反を検出できませんでした: {$expected}");
    }
});

test('rubric導線検査は提出後の先行とClosed確認欠落を検出する', function (): void {
    $content = "## 提出後\n\n- [ ] 振り返りを書いた\n\n"
        . "## 提出前の共通確認\n\n- [ ] checkがPASS\n";
    $violations = rubricWorkflowViolations('negative-control/support/rubric.md', $content, false);

    assertTrue(
        in_array('negative-control/support/rubric.md: rubricの提出後が提出前より先です', $violations, true),
        'rubric見出しの逆転を検出できませんでした'
    );
    assertTrue(
        in_array('negative-control/support/rubric.md: rubricの提出後段階にRedmineのClosed確認がありません', $violations, true),
        '提出後rubricのClosed確認欠落を検出できませんでした'
    );
});

test('rubric導線検査は空の提出前とRedmine報告項目の欠落を検出する', function (): void {
    $content = "## 提出前\n\n## 作業内容\n\n- [ ] checkがPASS\n\n"
        . "## 提出後\n\n- [ ] RedmineをClosedにした\n";
    $violations = rubricWorkflowViolations('T-999_example', $content, false);

    assertTrue(
        in_array('T-999_example: rubricの提出前見出し直下に確認項目がありません', $violations, true),
        '空の提出前セクションを検出できませんでした'
    );
    assertTrue(
        in_array('T-999_example: rubricの提出後にRedmineへのPASS・PR URL・振り返り・Closed確認がありません', $violations, true),
        'Redmine報告項目の欠落を検出できませんでした'
    );
});

test('rubric導線検査はcheck・merge・debriefの必須確認欠落を検出する', function (): void {
    $content = "## 提出前\n\n- [ ] checkを実行した\n\n"
        . "## 提出後\n\n- [ ] RedmineへPASS結果・Pull RequestのURL・振り返りをコメントし、Closedにした\n";
    $violations = rubricWorkflowViolations('T-999_example', $content, true);

    foreach ([
        'T-999_example: rubricの提出前段階にcheckのPASS確認がありません',
        'T-999_example: rubricの提出後段階にPull Requestのmerge確認がありません',
        'T-999_example: rubricの提出後段階にdebriefの確認がありません',
    ] as $expected) {
        assertTrue(in_array($expected, $violations, true), "rubricの必須確認欠落を検出できませんでした: {$expected}");
    }
});

test('debrief参照検査は実体がない課題の無条件参照を検出する', function (): void {
    assertSame(
        ['negative-control/support/hints.md:1: 実在しないdebriefを前提にしています'],
        unavailableDebriefReferenceViolations(
            'negative-control/support/hints.md',
            "提出後にdebrief/expected-review-points.mdを開く\n",
            false
        )
    );
    assertSame(
        [],
        unavailableDebriefReferenceViolations(
            'negative-control/ticket.md',
            "support/debrief/がある課題は、merge後に開く\n",
            false
        )
    );
});

test('共通ガイドはmerge後に振り返りとRedmine報告を行う標準順序を持つ', function (): void {
    $root = dirname(__DIR__, 4);
    $cases = [
        ['README.md', '## 1枚のチケットを完了する流れ', '## 次に読む文書', false],
        ['docs/02_作業ルール/workflow.md', '## 全体の流れ', '## GitHub Issuesとの連携(追加体験・任意)', false],
        ['docs/02_作業ルール/git-and-pr-guide.md', '## マージ後にチケットを完了する(方式A / B 共通)', '## コンフリクト(衝突)が起きたら', false],
        ['docs/00_はじめに/first-ticket-walkthrough.md', '### 手順8〜14: 提出・セルフレビュー・振り返り', '## 以降の課題への適用', false],
        ['docs/00_はじめに/first-ticket-walkthrough.html', '<h3>手順8〜14: 提出・セルフレビュー・振り返り</h3>', '<h2>以降の課題への適用</h2>', true],
        ['docs/00_はじめに/setup-guide.md', '## 4. check を確認し、T-000 を完了する', '## 学習前に選べる設定', false],
        ['docs/00_はじめに/start-to-tutorial-guide.md', '## 5. T-000 を完了する', '## 6. tutorial を始める', false],
        ['docs/00_はじめに/start-to-tutorial-guide.md', '## 7. tutorial-2 を始める', '## 8. 次に進めるか確認する', false],
        ['docs/00_はじめに/start-to-tutorial-guide.html', '<h2>5. T-000 を完了する</h2>', '<h2>6. tutorial を始める</h2>', true],
        ['docs/00_はじめに/start-to-tutorial-guide.html', '<h2>7. tutorial-2 を始める</h2>', '<h2>8. 次に進めるか確認する</h2>', true],
        ['reports/README.md', '## 報告書の使い方(T-000の例)', '## 最初の対応表(迷ったらまずここ)', false],
    ];
    $violations = [];

    foreach ($cases as [$relative, $start, $end, $isHtml]) {
        $content = (string) file_get_contents($root . '/' . $relative);
        $section = guideWorkflowSection($content, $start, $end);
        if ($section === '') {
            $violations[] = $relative . ': 標準提出手順の検査範囲を特定できません';
            continue;
        }
        if ($isHtml) {
            $section = html_entity_decode(strip_tags($section), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        array_push($violations, ...guideCompletionWorkflowViolations($relative, $section));
    }

    assertSame([], $violations);
});

test('共通ガイド導線検査はmerge後の振り返り欠落を検出する', function (): void {
    $content = "Pull Requestをmergeする\n"
        . "RedmineへPASS結果とPull RequestのURLをコメントしてClosedにする\n"
        . "support/rubric.mdの提出後を確認する\n";

    assertSame(
        ['negative-control/guide.md: Pull Requestをmergeした後に振り返りを書く手順がありません'],
        guideCompletionWorkflowViolations('negative-control/guide.md', $content)
    );
});

test('Masterの全ticket本文はrubric・PR・Redmine・debriefと提出順序を満たす', function (): void {
    $edition = CheckEdition::load(dirname(__DIR__, 4) . '/tools/check-edition.php');
    if ($edition->listMode !== 'grouped') {
        return;
    }

    $ticketRoot = dirname(__DIR__, 4) . '/packs/php/tickets';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ticketRoot));
    $violations = [];

    foreach ($files as $file) {
        if (!$file->isFile() || $file->getFilename() !== 'ticket.md') {
            continue;
        }

        $content = (string) file_get_contents($file->getPathname());
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($ticketRoot) + 1));
        $hasDebrief = is_dir($file->getPath() . '/support/debrief');
        array_push($violations, ...ticketWorkflowViolations($relative, $content, $hasDebrief));
    }

    assertSame([], $violations);
});

test('Masterの全rubricは提出前・提出後・Redmineの確認段階を持つ', function (): void {
    $edition = CheckEdition::load(dirname(__DIR__, 4) . '/tools/check-edition.php');
    if ($edition->listMode !== 'grouped') {
        return;
    }

    $ticketRoot = dirname(__DIR__, 4) . '/packs/php/tickets';
    $ticketFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ticketRoot));
    $violations = [];

    foreach ($ticketFiles as $ticketFile) {
        if (!$ticketFile->isFile() || $ticketFile->getFilename() !== 'ticket.md') {
            continue;
        }

        $rubric = $ticketFile->getPath() . '/support/rubric.md';
        $relative = str_replace('\\', '/', substr($ticketFile->getPath(), strlen($ticketRoot) + 1));
        if (!is_file($rubric)) {
            $violations[] = $relative . ': support/rubric.mdがありません';
            continue;
        }

        $content = (string) file_get_contents($rubric);
        $hasDebrief = is_dir($ticketFile->getPath() . '/support/debrief');
        array_push($violations, ...rubricWorkflowViolations($relative, $content, $hasDebrief));
    }

    assertSame([], $violations);
});

test('Masterの全debrief参照は実在するか条件付きである', function (): void {
    $edition = CheckEdition::load(dirname(__DIR__, 4) . '/tools/check-edition.php');
    if ($edition->listMode !== 'grouped') {
        return;
    }

    $ticketRoot = dirname(__DIR__, 4) . '/packs/php/tickets';
    $ticketFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ticketRoot));
    $violations = [];

    foreach ($ticketFiles as $ticketFile) {
        if (!$ticketFile->isFile() || $ticketFile->getFilename() !== 'ticket.md') {
            continue;
        }

        $ticketDirectory = $ticketFile->getPath();
        $hasDebrief = is_dir($ticketDirectory . '/support/debrief');
        $markdownFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ticketDirectory));
        foreach ($markdownFiles as $markdownFile) {
            if (!$markdownFile->isFile() || $markdownFile->getExtension() !== 'md') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($markdownFile->getPathname(), strlen($ticketRoot) + 1));
            $content = (string) file_get_contents($markdownFile->getPathname());
            array_push(
                $violations,
                ...unavailableDebriefReferenceViolations($relative, $content, $hasDebrief)
            );
        }
    }

    assertSame([], $violations);
});
}

test('resultFail: 空行 → 結果行 → 1始まりの番号付き一覧(順序保存)', function (): void {
    $r = new CheckRenderer();
    assertSame(
        "\n結果: FAIL\n  1. 一つ目の問題\n  2. 二つ目の問題\n",
        captureRenderer(fn () => $r->resultFail(['一つ目の問題', '二つ目の問題']))
    );
});

test('warning は注入したストリームへ「警告: 」プレフィックスで書く', function (): void {
    $stream = fopen('php://memory', 'r+');
    $r = new CheckRenderer($stream);
    $r->warning('front matter が不正です');
    rewind($stream);
    assertSame("警告: front matter が不正です\n", (string) stream_get_contents($stream));
    fclose($stream);
});

test('fatal は注入したストリームへ「エラー: 」プレフィックスで書く', function (): void {
    $stream = fopen('php://memory', 'r+');
    $r = new CheckRenderer($stream);
    $r->fatal('設定が読めません');
    rewind($stream);
    assertSame("エラー: 設定が読めません\n", (string) stream_get_contents($stream));
    fclose($stream);
});

test('usage(grouped): 不明ID表示 → 使い方 → track 見出しごとの一覧', function (): void {
    $edition = CheckEdition::fromArray([
        'list_mode' => 'grouped',
        'track_labels' => ['dev' => '手を動かす課題'],
    ]);
    $tickets = [
        'T-000' => ['track' => 'dev', 'level' => 1, 'title' => '環境構築'],
        'D-010' => ['track' => 'design', 'level' => 2, 'title' => '要望整理'],
    ];
    $r = new CheckRenderer();
    $out = captureRenderer(fn () => $r->usage('T-9XX', $tickets, $edition));
    assertSame(
        "課題 'T-9XX' が見つかりません。\n\n"
        . "使い方: docker compose exec app php tools/check.php <課題ID>\n\n"
        . "利用可能な課題:\n"
        . "\n[手を動かす課題]\n"
        . "  T-000  Level 1  環境構築\n"
        . "\n[design]\n"
        . "  D-010  Level 2  要望整理\n",
        $out
    );
});

test('usage(ordered): 指定順の1本リスト・未知IDはスキップ・IDなしなら不明ID行は出ない', function (): void {
    $edition = CheckEdition::fromArray([
        'list_mode' => 'ordered',
        'list_title' => 'テスト用の課題一覧',
        'ticket_order' => ['tutorial', 'T-900', 'T-000'],
    ]);
    $tickets = [
        'T-000' => ['level' => 1, 'title' => '環境構築'],
        'tutorial' => ['level' => 1, 'title' => '肩慣らし'],
    ];
    $r = new CheckRenderer();
    $out = captureRenderer(fn () => $r->usage('', $tickets, $edition));
    assertSame(
        "使い方: docker compose exec app php tools/check.php <課題ID>\n\n"
        . "利用可能な課題:\n"
        . "\n[テスト用の課題一覧]\n"
        . "  tutorial  Level 1  肩慣らし\n"
        . "  T-000  Level 1  環境構築\n",
        $out
    );
});

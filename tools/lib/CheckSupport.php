<?php

declare(strict_types=1);

namespace PractiCase\Check;

/**
 * check.php の部品(PHP 標準機能のみ)。
 * 単体でテストできるよう、純粋な関数として切り出してある。
 */

// チケットの scope に書かれていなくても常に変更を許可するパス
// (ticket.md の status 書き換えと、reports/ への報告書作成はチケット運用そのもののため。
//  *.db は init-db が作る生成物 — .gitignore が効いていれば現れないが、
//  欠けた環境でも「意味の分からない scope 違反」で学習者を落とさない)
const IMPLICIT_SCOPE = ['packs/*/tickets/**', 'reports/**', 'packs/*/app/database/*.db'];

/**
 * Markdown 先頭の front matter を読む簡易パーサ。
 * 対応範囲は docs/02_作業ルール/ticket-frontmatter.md の仕様(1階層のスカラーと文字列配列)のみ。
 */
function parseFrontMatter(string $markdown): ?array
{
    if (preg_match('/\A---\r?\n(.*?)\r?\n---(\r?\n|\z)/s', $markdown, $matches) !== 1) {
        return null;
    }

    $data = [];
    $listKey = null;
    foreach (preg_split('/\r?\n/', $matches[1]) as $line) {
        if ($listKey !== null && preg_match('/\A\s*-\s+(.+)\z/', $line, $m) === 1) {
            $data[$listKey][] = trim($m[1], " \t\"'");
            continue;
        }
        if (preg_match('/\A([A-Za-z_][A-Za-z0-9_]*):\s*(.*)\z/', $line, $m) === 1) {
            $value = trim($m[2]);
            if ($value === '') {
                $data[$m[1]] = [];
                $listKey = $m[1];
            } else {
                $data[$m[1]] = trim($value, "\"'");
                $listKey = null;
            }
        }
    }

    return $data;
}

/** tickets ディレクトリを深さを問わず走査し、ticket.md のパス一覧を返す(Light: 章立てで2階層になるため) */
function findTicketFiles(string $dir): array
{
    $found = [];
    foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $sub) {
        if (is_file($sub . '/ticket.md')) {
            $found[] = $sub . '/ticket.md';
            continue;
        }
        $found = array_merge($found, findTicketFiles($sub));
    }

    return $found;
}

/**
 * tickets ディレクトリを走査して課題を集める。
 *
 * @return array{tickets: array<string, array>, warnings: string[]}
 */
function discoverTickets(string $ticketsDir): array
{
    $tickets = [];
    $warnings = [];
    foreach (findTicketFiles($ticketsDir) as $path) {
        $meta = parseFrontMatter((string) file_get_contents($path));
        $id = is_array($meta) ? (string) ($meta['id'] ?? '') : '';
        // id は T-/R-/D-+3桁(T=作業 / R=レビュー / D=設計)。特例は導入課題 "tutorial"・"tutorial-2" 等の連番のみ(docs/02_作業ルール/ticket-frontmatter.md)
        if (preg_match('/\A([TRD]-[0-9]{3}|tutorial(-[0-9]+)?)\z/', $id) !== 1) {
            $warnings[] = "front matter が不正です(id を確認): {$path}";
            continue;
        }
        if (isset($tickets[$id])) {
            $warnings[] = "課題IDが重複しています: {$id}({$path})";
            continue;
        }
        $meta['_dir'] = dirname($path);
        $tickets[$id] = $meta;
    }
    ksort($tickets);

    return ['tickets' => $tickets, 'warnings' => $warnings];
}

/** git を実行し、成功なら出力行の配列、失敗なら null を返す */
function gitCapture(array $args): ?array
{
    $command = 'git ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>/dev/null';
    exec($command, $output, $exitCode);

    return $exitCode === 0 ? $output : null;
}

/**
 * ベースライン(main / master)からの変更ファイルを集める。
 * 追跡済みの変更は git diff、新規ファイルは未追跡一覧から拾う(新人は新しいファイルも作るため)。
 *
 * @return array{files: array<string, string>, base: string}|string
 *         成功: files = [パス => 状態(M/A/D/?)]。失敗: 学習者向けエラーメッセージ
 */
function collectChangedFiles(): array|string
{
    if (gitCapture(['rev-parse', '--git-dir']) === null) {
        return 'ここは git リポジトリではありません。'
            . 'T-000 の手順に従って git init と最初のコミットを済ませてから実行してください。';
    }

    $base = null;
    foreach (['main', 'master'] as $branch) {
        if (gitCapture(['rev-parse', '--verify', '--quiet', $branch]) !== null) {
            $base = $branch;
            break;
        }
    }
    if ($base === null) {
        return 'ベースラインになるコミットが見つかりません(main / master ブランチが空です)。'
            . 'T-000 の手順に従って最初のコミットを作成してから実行してください。';
    }

    // core.quotepath=false: 既定では非ASCIIパス(日本語の章フォルダ等)が "\350..." の8進エスケープ+
    // 引用符で出力され、scope パターンと一致しない(scope 外の誤判定になる)。UTF-8 のまま出させる
    $files = [];
    foreach (gitCapture(['-c', 'core.quotepath=false', 'diff', '--name-status', $base]) ?? [] as $line) {
        // 形式: "M\tpath"(リネームは "R100\told\tnew")
        $parts = explode("\t", $line);
        if (count($parts) >= 2) {
            $status = substr($parts[0], 0, 1);
            $files[end($parts)] = $status;
        }
    }
    foreach (gitCapture(['-c', 'core.quotepath=false', 'ls-files', '--others', '--exclude-standard']) ?? [] as $path) {
        $files[$path] = '?';
    }
    ksort($files);

    return ['files' => $files, 'base' => $base];
}

/** パスが scope パターン(glob。** は任意階層)のどれかに一致するか */
function matchesScope(string $path, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        // PHP の fnmatch は FNM_PATHNAME なしなら * が / もまたぐため、** は * に正規化すれば足りる
        if (fnmatch(str_replace('**', '*', (string) $pattern), $path)) {
            return true;
        }
    }

    return false;
}

/**
 * review/report 系チケットの提出物(reports/<課題ID>*.md)が存在するか。
 * git 差分ではなくファイルシステムで見る — 提出物を main へマージした後に
 * check を再実行しても、完了済みが FAIL に戻らないようにするため。
 */
function hasReportSubmission(string $ticketId, string $reportsDir = 'reports'): bool
{
    foreach (glob($reportsDir . '/*.md') ?: [] as $path) {
        $name = basename($path);
        if (strcasecmp($name, 'README.md') !== 0 && stripos($name, $ticketId) === 0) {
            return true;
        }
    }

    return false;
}

<?php

declare(strict_types=1);

// 画面テンプレートから使う小さな共通関数。ロジックはここに書かない(ARC-1)

// SEC-2: HTML への動的出力は必ずこの関数を通す
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

// G-4: ロール違反・他社/他人リソース・存在しない id はすべて 404 で統一する
function abort404(): never
{
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>404</title></head>'
        . '<body><h1>404 Not Found</h1><p>ページが見つかりません。</p>'
        . '<p><a href="/index.php">トップへ戻る</a></p></body></html>';
    exit;
}

// ── 以下は課題 tutorial 専用の教材演出(アプリの機能ではない) ──────────────

// ?tutorial=1 で開始・?tutorial=0 で終了。以降の画面遷移はセッションが記憶する
function tutorialMode(): bool
{
    if (isset($_GET['tutorial'])) {
        $_SESSION['tutorial_mode'] = ($_GET['tutorial'] === '1');
    }

    return (bool) ($_SESSION['tutorial_mode'] ?? false);
}

// 現在の URL のまま tutorial=0 にした「表示を終了する」リンク先
function tutorialExitUrl(): string
{
    $params = $_GET;
    $params['tutorial'] = '0';
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');

    return $path . '?' . http_build_query($params);
}

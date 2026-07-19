<?php

declare(strict_types=1);

// T-031(障害対応: null 防御の復元)の check。
//
// 修正対象はコントローラ(public/client/application_decide.php)のため、
// ユニットテストではなくソースの構造検査で判定する: コメントを取り除いた
// 実コードに対して「null 防御が正しい形・正しい位置で復元されているか」を
// 確認する(コメントや文章に書いただけでは合格しない)。
// Service / Repository / Logger への変更は check.php 本体の scope 検査が防ぐ。

const T031_TARGET = 'packs/php/app/public/client/application_decide.php';
// 修正完了状態(正規形)の完全性 digest。改行コードだけを LF へ正規化した全文の SHA-256。
// この課題の要求は「同じ3行を、同じ位置へ、同じ内容で復元する」なので、修正後の
// ファイル全体が正規形と一致することを合格条件にする(LF/CRLF 差だけは同一視)。
// 文字列リテラル・nowdoc・if (false)・未使用クロージャ・到達不能化・無関係な変更の
// 混入は、下の構造検査(学習者向け診断)をすり抜けてもここで必ず止まる。
// 再計算(正規形を正規に更新したときだけ): 正規形 = 修正完了状態の T031_TARGET 全文
// (= null 防御を復元した fix コミットの同ファイル)に対して
//   hash('sha256', str_replace(["\r\n", "\r"], "\n", file_get_contents(<正規形>)))
const T031_EXPECTED_SHA256 = '5c4849b12262f5cf2ab4f5f45397a8aff52f397f03b8b5fbbf298c0375af3223';

/** 修正対象のソースからコメントを除去し、空白を1個に正規化した実コードを返す */
function t031CodeWithoutComments(): string
{
    assertTrue(is_file(T031_TARGET), '修正対象が見つかりません: ' . T031_TARGET);
    $out = '';
    foreach (token_get_all((string) file_get_contents(T031_TARGET)) as $token) {
        if (is_array($token)) {
            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }
            $out .= $token[1];
        } else {
            $out .= $token;
        }
    }

    return trim((string) preg_replace('/\s+/', ' ', $out));
}

test('T-031: 結果が null の場合の防御(abort404)が正しい位置に復元されている', function (): void {
    $code = t031CodeWithoutComments();

    $found = preg_match(
        '/if \((\$result === null|null === \$result)\) \{ abort404\(\); \}/',
        $code,
        $m,
        PREG_OFFSET_CAPTURE
    );
    assertTrue(
        $found === 1,
        '「結果が null なら abort404() で 404 を返す」防御が実コードとして見つかりません。'
        . 'T-029 の調査報告(direct_cause)が指した欠陥位置に、null の場合の分岐を復元してください'
        . '(support/spec.md の「復元する防御の仕様」参照)'
    );

    $guardPos = (int) $m[0][1];
    $matchPos = strpos($code, 'match ($decision)');
    $successPos = strpos($code, 'if ($result === true)');
    $flashErrorPos = strpos($code, 'Flash::error(');
    assertTrue($matchPos !== false, '承認/却下の match 式が見つかりません(元の処理を壊していませんか)');
    assertTrue($successPos !== false, '成功判定 if ($result === true) が見つかりません(元の処理を壊していませんか)');
    assertTrue($flashErrorPos !== false, 'エラー表示 Flash::error(...) が見つかりません(元の処理を壊していませんか)');
    assertTrue($guardPos > $matchPos, 'null 防御は、match(...) で $result を受け取った後に置いてください');
    assertTrue(
        $guardPos < $successPos && $guardPos < $flashErrorPos,
        'null 防御の位置が遅すぎます。成功判定 if ($result === true) と Flash::error(...) に'
        . '到達する前に 404 で打ち切るのがこの画面の仕様です(後ろに置いても例外は防げません)'
    );
});

test('T-031: 危険な代替実装になっていない', function (): void {
    $code = t031CodeWithoutComments();

    assertTrue(
        !str_contains($code, '=== false') && !str_contains($code, 'false === $result'),
        '$result の判定に false 比較が混ざっています。「該当なし」は null です — '
        . 'null と厳密比較(===)してください(false と null は別物)'
    );
    assertTrue(
        preg_match('/\bempty ?\(/', $code) !== 1,
        'empty() での判定は使わないでください。エラーメッセージ(文字列)の分岐まで巻き込む可能性があります'
    );
    assertTrue(
        preg_match('/\bthrow\b/', $code) !== 1,
        'throw での代替は不可です。この画面の仕様は「該当なしは 404 応答」(D-1 / G-4)— '
        . '例外を投げるのではなく abort404() で応答してください'
    );
});

test('T-031: 修正後のファイル全体が、要求どおりの復元になっている(完全性)', function (): void {
    $raw = (string) file_get_contents(T031_TARGET);
    $digest = hash('sha256', str_replace(["\r\n", "\r"], "\n", $raw));
    assertSame(
        T031_EXPECTED_SHA256,
        $digest,
        'null防御以外の変更、または実行されない位置への記述があります。'
        . 'この課題の要求は「欠けている3行を、元の位置へ、元の内容のまま復元する」です — '
        . '他の箇所への変更や余分な記述を取り除き、指定された3行だけを指定位置へ復元してください'
        . '(support/spec.md の「復元する防御の仕様」参照)'
    );
});

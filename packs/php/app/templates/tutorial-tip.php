<?php

/**
 * 課題 tutorial 専用の演出部品(アプリの機能ではない)。
 * 使い方: $tutorialTip に「この画面での次の1歩」の文言(HTML可)を入れてから require する。
 * 光らせたい要素には class="tutorial-spotlight" を付ける(この画面の呼び出し側で)。
 * 答え(正しいラベル・修正するファイル名)はここにも各画面にも書かない。
 */
?>
<div class="tutorial-overlay"></div>
<div class="tutorial-bubble">
    <strong>チュートリアル</strong><br>
    <?= $tutorialTip ?? '' ?><br>
    <a class="tutorial-exit" href="<?= e(tutorialExitUrl()) ?>">チュートリアル表示を終了する</a>
</div>
<style>
    .tutorial-overlay { position: fixed; inset: 0; background: rgba(15, 22, 40, .55); z-index: 40; }

    /* 単体要素(ボタン・リンク等)のスポットライト */
    .tutorial-spotlight { position: relative; z-index: 41; }
    a.tutorial-spotlight, button.tutorial-spotlight {
        background: #fff; outline: 3px solid #1f6feb; box-shadow: 0 0 24px rgba(31, 111, 235, .55);
    }

    /* テーブル行のスポットライト: tr へのスタイルはブラウザ差が出るため、セル側に枠と背景を張る */
    tr.tutorial-spotlight > th, tr.tutorial-spotlight > td {
        position: relative; z-index: 41; background: #fff;
        border-top: 3px solid #1f6feb; border-bottom: 3px solid #1f6feb;
        box-shadow: 0 0 18px rgba(31, 111, 235, .45);
    }
    tr.tutorial-spotlight > th:first-child, tr.tutorial-spotlight > td:first-child { border-left: 3px solid #1f6feb; }
    tr.tutorial-spotlight > th:last-child, tr.tutorial-spotlight > td:last-child { border-right: 3px solid #1f6feb; }

    .tutorial-bubble {
        position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%);
        max-width: 560px; width: calc(100% - 48px); background: #1f2c4d; color: #fff;
        padding: 12px 18px; border-radius: 10px; z-index: 42; line-height: 1.7;
        box-shadow: 0 6px 24px rgba(0, 0, 0, .35);
    }
    .tutorial-bubble strong { color: #cadcfc; }
    .tutorial-exit { display: inline-block; margin-top: 6px; color: #9db8e8; font-size: .85em; }
</style>

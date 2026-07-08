// PractiCase チューター — VS Code 内チュートリアル誘導(教材専用)
//
// ── セキュリティ憲法(このファイルの全コードが従う原則) ──────────────────────
// 1. ネットワーク通信をしない(http/https/fetch/net を一切使わない)
// 2. 外部プロセスを起動しない(child_process を使わない。ターミナルへは文字を「置く」だけで実行しない)
// 3. 実行できる操作は下の ACTIONS 許可リストだけ。steps.json は「データ」であり「命令」ではない —
//    改竄されても、ここに定義された固定の操作以外は何も起きない
// 4. ファイルの読み書きはワークスペース内のみ(絶対パス・「..」を拒否)。書き込みは新規作成のみで上書きしない
// 5. eval / new Function / 動的 require を使わない
// 6. Webview は CSP(nonce)で固定し、受け付けるメッセージも許可リストで検証する
// 7. Workspace Trust が無い環境では起動しない(package.json で宣言)
// ────────────────────────────────────────────────────────────────────────

'use strict';

const vscode = require('vscode');
const path = require('path');
const fs = require('fs');

const STEPS_FILE = '.tutorial/steps.json';

// ターミナルに「置く」コマンドの一覧(固定値。steps.json が選べるのは tourId だけで、文字列は変更できない)
const CHECK_COMMANDS = {
    'tutorial': 'docker compose exec app php tools/check.php tutorial',
    'tutorial-2': 'docker compose exec app php tools/check.php tutorial-2',
};

// 開いてよい URL の形(ローカルの学習アプリのみ)
const LOCAL_URL_PATTERN = /^http:\/\/localhost:\d{2,5}(\/[\w\-./?&=%]*)?$/;

// 新規作成できる報告ファイルとその雛形(固定値。steps.json からは変更できない)
const REPORT_FILES = {
    'tutorial': {
        path: 'reports/tutorial_fix_report.md',
        template: [
            '# tutorial 修正報告',
            '',
            '- 起きていたこと: ',
            '- どこをどう直したか: ',
            '- 確認(check の結果): ',
            '',
        ].join('\n'),
    },
    'tutorial-2': {
        path: 'reports/tutorial-2_fix_report.md',
        template: [
            '# tutorial-2 実装報告',
            '',
            '- 作ったもの: ',
            '- 工夫した点(初めてのタグの扱いなど): ',
            '- 確認(check の結果): ',
            '',
        ].join('\n'),
    },
};

let state = {
    tours: [], // steps.json の全ツアー
    currentTour: null,
    steps: [],
    tourTitle: 'チュートリアル',
    tourSubtitle: '',
    index: -1, // -1 = 未開始(ロビー表示)
    tutorRoot: null, // 教材ルート(.tutorial/steps.json がある場所)。開いたフォルダの配下から探す
    panel: null, // TutorPanel(Explorer サイドバーに常駐するクエストログ)
    fileDecorations: null,
    highlightType: null,
    edgeType: null, // 対象行の上下に走る細いライン
    pulseTypes: [], // 多段フェード用(強→弱)。タイマーで差し替えて滑らかな明滅を作る
    dimType: null,
    pulseTimers: [],
    pulseInterval: null, // 永続呼吸のタイマー
    statusBar: null, // 画面下: 現在地(クリックでログ表示)
    nextButton: null, // 画面下: 「できた、次へ」(ログが閉じていても進める)
    navGeneration: 0, // 連打対策: gotoStep/startTour/stopTour を呼ぶたびに増やし、
                       // 自分より新しい世代に追い越されたら古い方は黙って中断する
    checkTerminal: null, // check コマンド用ターミナル(連打で量産しないよう使い回す)
    checkTerminalCommand: null, // 直前に checkTerminal へ置いたコマンド文字列(同じなら送り直さない)
};

// ── ユーティリティ ─────────────────────────────────────────────────────────

function workspaceRoot() {
    const folders = vscode.workspace.workspaceFolders;
    return folders && folders.length > 0 ? folders[0].uri.fsPath : null;
}

// 教材ルートを探す: 開いているフォルダの配下から .tutorial/steps.json を検索する。
// (dev/ai のような親フォルダを開いていても教材を見つけられるようにする)
// findFiles はワークスペース内しか返さないため、原則4(外に出ない)は保たれる。
// dist/(配布スナップショットのコピー)と node_modules は除外し、複数あれば一番浅いものを選ぶ。
async function findTutorRoot() {
    const hits = await vscode.workspace.findFiles('**/.tutorial/steps.json', '**/{node_modules,dist}/**', 10);
    if (hits.length === 0) {
        return null;
    }
    hits.sort((a, b) => a.fsPath.length - b.fsPath.length);
    return path.dirname(path.dirname(hits[0].fsPath));
}

// 教材ルートからの相対パスだけを許可する(原則4)
function safeWorkspacePath(rel) {
    if (typeof rel !== 'string' || rel === '' || path.isAbsolute(rel) || rel.split(/[\\/]/).includes('..')) {
        return null;
    }
    const root = state.tutorRoot !== null ? state.tutorRoot : workspaceRoot();
    return root === null ? null : path.join(root, rel);
}

function loadTours() {
    const stepsPath = safeWorkspacePath(STEPS_FILE);
    if (stepsPath === null || !fs.existsSync(stepsPath)) {
        state.tours = [];
        return;
    }
    let parsed;
    try {
        parsed = JSON.parse(fs.readFileSync(stepsPath, 'utf8')); // JSON.parse のみ(原則5)
    } catch {
        vscode.window.showWarningMessage('PractiCase チューター: .tutorial/steps.json を読めませんでした(JSON を確認)');
        state.tours = [];
        return;
    }
    const rawTours = Array.isArray(parsed && parsed.tours) ? parsed.tours : [];

    // データ検証: 想定した型・想定した値だけを通す(原則3)
    state.tours = rawTours
        .filter((t) => t && typeof t === 'object' && typeof t.tourId === 'string' && Array.isArray(t.steps))
        .map((t) => ({
            tourId: t.tourId,
            title: typeof t.title === 'string' ? t.title : 'チュートリアル',
            subtitle: typeof t.subtitle === 'string' ? t.subtitle : '',
            teaser: typeof t.teaser === 'string' ? t.teaser : '',
            outro: typeof t.outro === 'string' ? t.outro : '',
            next: typeof t.next === 'string' ? t.next : null,
            steps: t.steps
                .filter((s) => s && typeof s === 'object' && typeof s.title === 'string')
                .map((s) => ({
                    title: s.title,
                    why: typeof s.why === 'string' ? s.why : '',
                    do: typeof s.do === 'string' ? s.do : '',
                    file: typeof s.file === 'string' ? s.file : null,
                    anchor: typeof s.anchor === 'string' ? s.anchor : null,
                    dim: s.dim === true,
                    url: typeof s.url === 'string' && LOCAL_URL_PATTERN.test(s.url) ? s.url : null,
                    searchQuery: typeof s.searchQuery === 'string' ? s.searchQuery : '',
                    actions: Array.isArray(s.actions)
                        ? s.actions.filter((a) => typeof a === 'string' && a in ACTIONS)
                        : [],
                })),
        }));
}

// ── アクション許可リスト(原則3の本体) ──────────────────────────────────────
// steps.json が指定できるのは、この表の「名前」だけ。中身はすべてここに固定。

const ACTIONS = {
    // 全ファイル検索パネルを開く。手順データに searchQuery があれば自動入力する
    // (tutorial では意図的に空 — 「画面で見た言葉を自分で検索する」が学習の核のため)
    openSearch: {
        label: '検索を開く(Ctrl+Shift+F)',
        run: async (step) => {
            await vscode.commands.executeCommand('workbench.action.findInFiles', {
                query: step && step.searchQuery ? step.searchQuery : '',
                triggerSearch: false,
            });
        },
    },
    // check コマンドをターミナルに「置く」(Enter は学習者が押す。自動実行しない — 原則2)
    placeCheckCommand: {
        label: 'check コマンドをターミナルに置く',
        run: async () => {
            const command = CHECK_COMMANDS[state.currentTour ? state.currentTour.tourId : ''];
            if (!command) {
                return; // 固定リストに無いツアーではコマンドを置かない(原則3)
            }
            // 連打・再クリックのたびに新規ターミナルを増やさない。閉じられていれば作り直す
            if (state.checkTerminal === null || state.checkTerminal.exitStatus !== undefined) {
                // cwd を教材ルートにする(親フォルダを開いていても check が正しい場所で動く)
                state.checkTerminal = vscode.window.createTerminal({
                    name: 'PractiCase check',
                    cwd: state.tutorRoot !== null ? state.tutorRoot : undefined,
                });
                state.checkTerminalCommand = null; // 新しいターミナルなので前回コマンドの記憶を捨てる
            }
            state.checkTerminal.show();
            // 直前と同じコマンドなら送り直さない(送り直すと未実行の入力欄で文字列が連結されてしまう)
            if (state.checkTerminalCommand !== command) {
                state.checkTerminal.sendText(command, false); // addNewLine=false: 実行しない
                state.checkTerminalCommand = command;
            }
            vscode.window.setStatusBarMessage('コマンドを置きました。内容を確認して Enter で実行してください', 8000);
        },
    },
    // 学習アプリ(ローカル)をブラウザで開く
    openTutorialUrl: {
        label: 'ブラウザで症状を見る',
        run: async (step) => {
            if (step.url === null) {
                vscode.window.showWarningMessage('この手順にはローカル URL が設定されていません');
                return;
            }
            await vscode.env.openExternal(vscode.Uri.parse(step.url));
        },
    },
    // 報告ファイルの雛形を作って開く(存在するときは開くだけ。上書きしない — 原則4)
    createFixReport: {
        label: '報告ファイルを作る',
        run: async () => {
            const spec = REPORT_FILES[state.currentTour ? state.currentTour.tourId : ''];
            if (!spec) {
                return; // 固定リストに無いツアーでは作らない(原則3)
            }
            const abs = safeWorkspacePath(spec.path);
            if (abs === null) {
                return;
            }
            if (!fs.existsSync(abs)) {
                fs.mkdirSync(path.dirname(abs), { recursive: true });
                fs.writeFileSync(abs, spec.template, { flag: 'wx' }); // wx: 既存なら失敗=上書き不能
            }
            await openWorkspaceFile(spec.path);
        },
    },
};

// ── ファイルを開く+ツリーで見せる ─────────────────────────────────────────

async function openWorkspaceFile(rel) {
    const abs = safeWorkspacePath(rel);
    if (abs === null || !fs.existsSync(abs)) {
        vscode.window.showWarningMessage(`PractiCase チューター: ${rel} が見つかりません`);
        return null;
    }
    const uri = vscode.Uri.file(abs);
    // 注: revealInExplorer は使わない — サイドバーが切り替わって案内が隠れるため(ツリーの場所は 👉 バッジが示す)。
    // 作業ファイルは常にエディタの第1グループに開く(タブが増減してもクエストログの表示位置と無関係)
    const doc = await vscode.workspace.openTextDocument(uri);
    return vscode.window.showTextDocument(doc, { preview: false, viewColumn: vscode.ViewColumn.One });
}

// ── エディタ内ライトアップ(減光+ハイライト) ────────────────────────────────

function createDecorationTypes() {
    // 行番号の左に置くグラデーションの菱形(データURI の SVG — 通信ゼロのまま)
    const diamondSvg = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">'
        + '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        + '<stop offset="0" stop-color="#6366f1"/><stop offset=".55" stop-color="#8b5cf6"/>'
        + '<stop offset="1" stop-color="#06b6d4"/></linearGradient></defs>'
        + '<path d="M8 2 L13.5 8 L8 14 L2.5 8 Z" fill="url(#g)"/></svg>'
    );

    // 対象行: 半透明のバイオレット背景 + 左のグラデバー相当(3px) + ガターの菱形 + スクロールバー印
    state.highlightType = vscode.window.createTextEditorDecorationType({
        isWholeLine: true,
        backgroundColor: 'rgba(124, 92, 246, 0.14)',
        borderWidth: '0 0 0 3px',
        borderStyle: 'solid',
        borderColor: '#8b5cf6',
        overviewRulerColor: '#8b5cf6',
        overviewRulerLane: vscode.OverviewRulerLane.Full,
        gutterIconPath: vscode.Uri.parse(diamondSvg),
        gutterIconSize: '65%',
        before: {
            contentText: '',
            // 行の左端に貼り付く「光る」バー(公知の CSS 注入手法)。効かない環境でも border が下地になる
            textDecoration: 'none; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;'
                + ' background: linear-gradient(180deg, #6366f1, #8b5cf6, #06b6d4);'
                + ' box-shadow: 0 0 12px 2px rgba(139, 92, 246, .85); border-radius: 2px;',
        },
        after: {
            contentText: ' ◀ いまここ ',
            color: '#ffffff',
            backgroundColor: '#7c3aed', // グラデが効かない環境向けの下地
            margin: '0 0 0 1.6em',
            // textDecoration には追加の CSS を書ける(公知の装飾手法) — グラデのピルバッジに
            textDecoration: 'none; background: linear-gradient(120deg, #6366f1, #8b5cf6 60%, #06b6d4);'
                + ' border-radius: 999px; padding: 2px 11px 2px 9px; font-size: .8em; font-weight: 700;'
                + ' letter-spacing: .06em; box-shadow: 0 2px 10px rgba(139, 92, 246, .55);',
        },
    });
    // 対象行の上下に走る細いライン(スキャンライン風の締め)
    state.edgeType = vscode.window.createTextEditorDecorationType({
        isWholeLine: true,
        borderWidth: '1px 0',
        borderStyle: 'solid',
        borderColor: 'rgba(139, 92, 246, 0.38)',
    });
    // 多段フェード(強→弱)。差し替えで「じわっと光る」を作る
    state.pulseTypes = [0.42, 0.32, 0.24, 0.18].map((alpha) =>
        vscode.window.createTextEditorDecorationType({
            isWholeLine: true,
            backgroundColor: `rgba(139, 92, 246, ${alpha})`,
        })
    );
    // 対象以外: 沈めて視線を対象へ
    state.dimType = vscode.window.createTextEditorDecorationType({
        opacity: '0.3',
    });
}

// 登場時: 強く光ってじわっと定着(ease-out) → 以降はゆっくり永続的に呼吸(生きている光)
function startPulse(editor, ranges) {
    clearPulse();
    const setLevel = (level) => {
        try {
            state.pulseTypes.forEach((type, j) => {
                editor.setDecorations(type, j === level ? ranges : []);
            });
        } catch {
            // エディタが閉じられていたら何もしない
        }
    };
    const flash = [[0, 0], [90, 1], [190, 2], [300, 3], [430, -1]];
    for (const [at, level] of flash) {
        state.pulseTimers.push(setTimeout(() => setLevel(level), at));
    }
    // 3.2秒周期の柔らかい呼吸(吸って..吐いて..)。次のステップへ移ると clearPulse で止まる
    let phase = 0;
    state.pulseInterval = setInterval(() => {
        const wave = [3, 2, 3, -1];
        setLevel(wave[phase % wave.length]);
        phase++;
    }, 800);
}

function clearPulse() {
    for (const timer of state.pulseTimers) {
        clearTimeout(timer);
    }
    state.pulseTimers = [];
    if (state.pulseInterval) {
        clearInterval(state.pulseInterval);
        state.pulseInterval = null;
    }
    for (const editor of vscode.window.visibleTextEditors) {
        for (const type of state.pulseTypes) {
            editor.setDecorations(type, []);
        }
    }
}

function applyEditorDecorations(editor, step) {
    if (!editor || !step || !step.anchor) {
        return;
    }
    if (step.anchor.length > 200) {
        return; // 想定外に長い anchor は破局的バックトラック対策として無視する
    }
    let anchorRegex;
    try {
        anchorRegex = new RegExp(step.anchor);
    } catch {
        return; // 不正な正規表現は何もしない
    }
    const doc = editor.document;
    const hitLines = [];
    const otherLines = [];
    for (let i = 0; i < doc.lineCount; i++) {
        const range = doc.lineAt(i).range;
        if (anchorRegex.test(doc.lineAt(i).text)) {
            hitLines.push(range);
        } else {
            otherLines.push(range);
        }
    }
    if (hitLines.length === 0) {
        return; // アンカー不一致(コードが変わった等)なら演出しない — 誤誘導より無誘導
    }
    editor.setDecorations(state.highlightType, hitLines);
    editor.setDecorations(state.edgeType, hitLines);
    editor.setDecorations(state.dimType, step.dim ? otherLines : []);
    editor.revealRange(hitLines[0], vscode.TextEditorRevealType.InCenter);
    startPulse(editor, hitLines);
}

function clearEditorDecorations() {
    clearPulse();
    for (const editor of vscode.window.visibleTextEditors) {
        editor.setDecorations(state.highlightType, []);
        editor.setDecorations(state.edgeType, []);
        editor.setDecorations(state.dimType, []);
    }
}

// ── ファイルツリーの誘導(バッジ 👉) ───────────────────────────────────────

class TutorFileDecorations {
    constructor() {
        this._emitter = new vscode.EventEmitter();
        this.onDidChangeFileDecorations = this._emitter.event;
        this._targets = new Map(); // fsPath(小文字) => 'target' | 'parent'
    }

    // 対象ファイルと、その親フォルダの連鎖に印を付ける
    setTarget(rel) {
        this._targets.clear();
        const abs = rel === null ? null : safeWorkspacePath(rel);
        const root = workspaceRoot();
        if (abs !== null && root !== null) {
            this._targets.set(abs.toLowerCase(), 'target');
            let dir = path.dirname(abs);
            while (dir.toLowerCase().startsWith(root.toLowerCase()) && dir.length > root.length) {
                this._targets.set(dir.toLowerCase(), 'parent');
                dir = path.dirname(dir);
            }
        }
        this._emitter.fire(undefined); // 全ツリー再評価
    }

    provideFileDecoration(uri) {
        const kind = this._targets.get(uri.fsPath.toLowerCase());
        if (kind === undefined) {
            return undefined;
        }
        return {
            badge: '👉',
            color: new vscode.ThemeColor('charts.blue'),
            tooltip: kind === 'target' ? 'チュートリアル: いまここを見ます' : 'チュートリアル: この中に目的地があります',
        };
    }
}

// ── チューターパネル(Webview) ─────────────────────────────────────────────

class TutorPanel {
    constructor(extensionUri) {
        this._extensionUri = extensionUri;
        this._view = null; // WebviewView(resolveWebviewView で渡される。resolve されるまでは null)
    }

    // Explorer サイドバーの「クエストログ」セクションが(初めて)開かれたときに VS Code から呼ばれる。
    // 既存の explorer コンテナに相乗りする形で登録している(package.json 側)。専用の
    // activitybar コンテナは使わない — 過去にそれを試して Explorer と排他になり、
    // 片方を見ると片方が消える不具合を踏んだため。
    // 以降はこの WebviewView をそのまま使い回す(dispose されない限り作り直さない)。
    resolveWebviewView(webviewView /*, context, token */) {
        this._view = webviewView;
        webviewView.webview.options = {
            enableScripts: true,
            localResourceRoots: [this._extensionUri], // 外部リソースを読ませない(原則6)
        };
        webviewView.onDidDispose(() => {
            this._view = null;
        });
        // 受け付けるメッセージも許可リスト(原則6)
        webviewView.webview.onDidReceiveMessage(async (msg) => {
            if (!msg || typeof msg !== 'object') {
                return;
            }
            if (msg.type === 'start') {
                await startTour(typeof msg.tour === 'string' ? msg.tour : null);
            } else if (msg.type === 'next') {
                await gotoStep(state.index + 1);
            } else if (msg.type === 'prev') {
                await gotoStep(state.index - 1);
            } else if (msg.type === 'stop') {
                stopTour();
            } else if (msg.type === 'goto') {
                // クリア済みのミッションにだけ戻れる(先のミッションへのスキップは不可)
                const target = Number(msg.i);
                if (Number.isInteger(target) && target >= 0 && target < state.index) {
                    await gotoStep(target);
                }
            } else if (msg.type === 'action' && typeof msg.id === 'string' && msg.id in ACTIONS) {
                const step = state.steps[state.index];
                if (step && step.actions.includes(msg.id)) { // そのステップが宣言したアクションだけ
                    await ACTIONS[msg.id].run(step);
                }
            }
        });
        this.render();
    }

    // WebviewView には createWebviewPanel の reveal() に相当する API が無いため、VS Code が
    // ビューの登録から自動生成するコマンド `<viewId>.focus` で表示する(未 resolve でも解決される —
    // Explorer が閉じていれば開き、セクションが畳まれていれば展開し、初回なら
    // resolveWebviewView を呼んでから表示する)
    show() {
        vscode.commands.executeCommand('practicaseTutorQuestLog.focus');
    }

    render() {
        if (this._view === null) {
            return; // まだ resolve されていない(クエストログのセクションが一度も開かれていない)
        }
        const nonce = String(Date.now()) + Math.random().toString(36).slice(2);
        let body;
        if (state.tours.length === 0) {
            body = `<p>開いているフォルダの配下に教材の手順データ(.tutorial/steps.json)が見つかりません。</p>
                <p class="why">PractiCase の教材フォルダ(またはそれを含む親フォルダ)を開いてから、もう一度お試しください。</p>
                <button class="primary" data-msg="start">再試行する</button>`;
        } else if (state.index < 0) {
            body = this._lobby();
        } else {
            body = this._questLog(state.index, state.steps.length);
        }
        this._view.webview.html = `<!DOCTYPE html>
<html lang="ja"><head><meta charset="UTF-8">
<meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'nonce-${nonce}'; script-src 'nonce-${nonce}';">
<style nonce="${nonce}">
    /* テーマ追従の土台 × オーロラグラデ(インディゴ→バイオレット→シアン) × 道(コネクタ線)。
       派手さはモーション(shimmer/breathe/slideIn/aurora)と光で出す */
    body { font-family: var(--vscode-font-family); color: var(--vscode-foreground);
           padding: 14px 14px 18px; line-height: 1.65; font-size: 13px;
           --g1: #6366f1; --g2: #8b5cf6; --g3: #06b6d4; --ok: #10b981; }

    /* エンブレム帯: 中央の宝石から左右へ光の線が伸びる(画面の額縁) */
    .emblem { display: flex; align-items: center; gap: 10px; margin: 2px 0 12px; }
    .emblem::before, .emblem::after { content: ''; flex: 1; height: 1px; }
    .emblem::before { background: linear-gradient(90deg, transparent, rgba(139, 92, 246, .65)); }
    .emblem::after { background: linear-gradient(90deg, rgba(139, 92, 246, .65), transparent); }
    .emblem .gem { width: 8px; height: 8px; transform: rotate(45deg); border-radius: 2px;
                   background: linear-gradient(135deg, var(--g1), var(--g3));
                   box-shadow: 0 0 9px rgba(139, 92, 246, .9); }
    .quest-title-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
    .quest-sub { font-size: .7em; letter-spacing: .24em; opacity: .6; margin: 0 0 10px 2px; }
    .quest-badge { font-size: .66em; font-weight: 700; letter-spacing: .14em; padding: 3px 10px;
                   border-radius: 999px; color: #fff;
                   background: linear-gradient(135deg, var(--g1), var(--g2) 55%, var(--g3));
                   box-shadow: 0 1px 8px rgba(139, 92, 246, .4); }
    .quest-name { font-weight: 700; font-size: 1.05em; }
    .progress-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .bar { flex: 1; height: 6px; border-radius: 999px;
           background: color-mix(in srgb, var(--vscode-foreground) 12%, transparent); }
    .fill { height: 100%; border-radius: 999px; position: relative; overflow: hidden;
            background: linear-gradient(90deg, var(--g1) 0%, var(--g2) 55%, var(--g3) 100%);
            box-shadow: 0 0 10px rgba(139, 92, 246, .55);
            transition: width .5s cubic-bezier(.22, 1, .36, 1); }
    .fill::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%);
                   background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,.6) 50%, transparent 70%);
                   animation: shimmer 2.6s infinite; }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 55%, 100% { transform: translateX(100%); } }
    .count { font-size: .82em; font-weight: 600; font-variant-numeric: tabular-nums; opacity: .8; }

    ol.quest { list-style: none; padding: 0; margin: 12px 0; }
    ol.quest li { display: flex; align-items: flex-start; gap: 9px; padding: 6px 8px;
                  border-radius: 10px; margin: 0; position: relative; }
    /* 道: 各行が「自分の上」に短い接続線を持つ(展開カードの中を線が通らない)。
       踏破済みへの接続は光る実線、未踏への接続は点線 */
    ol.quest li:not(:first-child)::after { content: ''; position: absolute; left: 17px;
                  top: -7px; height: 13px; width: 2px; border-radius: 2px; }
    li.done:not(:first-child)::after,
    li.current:not(:first-child)::after { background: linear-gradient(180deg, var(--ok), var(--g3));
                  box-shadow: 0 0 6px rgba(16, 185, 129, .45); opacity: .8; }
    li.todo:not(:first-child)::after { width: 0; height: 12px;
                  border-left: 2px dashed color-mix(in srgb, var(--vscode-foreground) 25%, transparent); }
    li.current:not(:first-child)::after { top: -6px; height: 15px; }
    .chip { flex: 0 0 auto; width: 20px; height: 20px; border-radius: 7px; margin-top: 1px;
            display: flex; align-items: center; justify-content: center;
            font-size: .74em; font-weight: 700; position: relative; z-index: 1; }
    li .t { min-width: 0; }
    li.todo { opacity: .45; }
    li.todo .chip { border: 1px solid color-mix(in srgb, var(--vscode-foreground) 35%, transparent);
                    background: var(--vscode-editor-background, transparent); }
    li.done { opacity: .7; cursor: pointer; transition: opacity .15s, background .15s; }
    li.done:hover { opacity: 1; background: color-mix(in srgb, var(--vscode-foreground) 7%, transparent); }
    li.done:hover .chip { transform: scale(1.12); }
    li.done .chip { background: linear-gradient(135deg, var(--ok), var(--g3)); color: #fff;
                    box-shadow: 0 1px 6px rgba(16, 185, 129, .4); transition: transform .15s; }
    li.current { flex-direction: column; gap: 7px; padding: 10px 11px 12px; margin: 4px 0;
                 background: linear-gradient(120deg, rgba(99, 102, 241, .16), rgba(139, 92, 246, .08) 55%, rgba(6, 182, 212, .06));
                 box-shadow: 0 4px 18px rgba(99, 102, 241, .16),
                             inset 0 0 0 1px rgba(139, 92, 246, .35),
                             inset 0 1px 0 rgba(165, 180, 252, .35),
                             inset 0 -1px 0 rgba(6, 182, 212, .25); }
    li.current::before { content: ''; position: absolute; left: 0; top: 8px; bottom: 8px; width: 3px;
                         border-radius: 2px; background: linear-gradient(180deg, var(--g1), var(--g2), var(--g3));
                         box-shadow: 0 0 8px rgba(139, 92, 246, .6); }
    .current-row { display: flex; align-items: center; gap: 9px; width: 100%; }
    li.current .chip { background: linear-gradient(135deg, var(--g1), var(--g2)); color: #fff;
                       transform: rotate(45deg); border-radius: 5px; margin: 2px 3px 2px 2px;
                       animation: breathe 2.2s infinite; }
    li.current .chip i { font-style: normal; display: block; transform: rotate(-45deg); }
    @keyframes breathe { 0%, 100% { box-shadow: 0 0 0 0 rgba(139, 92, 246, .6); }
                         50% { box-shadow: 0 0 0 6px rgba(139, 92, 246, 0); } }
    li.current .t { font-weight: 700; font-size: 1.02em; }

    .card { width: 100%; animation: slideIn .28s cubic-bezier(.22, 1, .36, 1); }
    @keyframes slideIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    .why { font-size: .88em; opacity: .88; margin: 0 0 8px; padding: 6px 9px;
           background: color-mix(in srgb, var(--vscode-foreground) 5%, transparent);
           border-left: 3px solid var(--g3); border-radius: 0 6px 6px 0; }
    .do { margin: 0 0 8px; }

    button { display: block; width: 100%; margin: 6px 0; padding: 7px 12px; cursor: pointer;
             border-radius: 7px; font-family: inherit; font-size: .95em;
             border: 1px solid var(--vscode-button-border, color-mix(in srgb, var(--vscode-foreground) 22%, transparent));
             background: var(--vscode-button-secondaryBackground); color: var(--vscode-button-secondaryForeground);
             transition: transform .12s, box-shadow .12s, filter .12s; }
    button:hover { filter: brightness(1.08); transform: translateY(-1px); }
    button.primary { border: none; font-weight: 700; color: #fff;
                     background: linear-gradient(120deg, var(--g1) 0%, var(--g2) 55%, var(--g3) 130%);
                     box-shadow: 0 2px 12px rgba(99, 102, 241, .45); }
    button.primary:hover { box-shadow: 0 4px 18px rgba(139, 92, 246, .55); }
    button.big { padding: 11px; font-size: 1.03em; }
    button.action { display: flex; align-items: center; justify-content: center; gap: 8px; }
    button.action::before { content: ''; flex: 0 0 auto; width: 7px; height: 7px;
                            transform: rotate(45deg); border-radius: 2px;
                            background: linear-gradient(135deg, var(--g1), var(--g3));
                            box-shadow: 0 0 6px rgba(139, 92, 246, .75); }
    button.quiet { background: none; border: none; opacity: .55; font-size: .85em;
                   text-decoration: underline; box-shadow: none; }
    button.quiet:hover { transform: none; filter: none; opacity: .85; }
    button:disabled { opacity: .35; cursor: default; }
    .nav { display: flex; gap: 7px; } .nav button { flex: 1; margin: 4px 0 0; }
    .nav button:first-child { flex: 0 0 26%; }

    .clear { text-align: center; padding: 18px 12px; margin: 12px 0; border-radius: 12px;
             border: 1px solid rgba(139, 92, 246, .4); animation: slideIn .3s;
             background:
               radial-gradient(circle at 50% 0%, rgba(139, 92, 246, .22), transparent 65%),
               linear-gradient(135deg, rgba(99, 102, 241, .14), rgba(6, 182, 212, .12)); }
    .clear-title { font-size: 1.35em; font-weight: 800; letter-spacing: .05em;
                   background: linear-gradient(90deg, var(--g1), var(--g2), var(--g3), var(--g2), var(--g1));
                   background-size: 300% 100%; animation: aurora 3.2s linear infinite;
                   -webkit-background-clip: text; background-clip: text; color: transparent; }
    @keyframes aurora { to { background-position: 300% 0; } }
    .clear .sub { display: block; margin-top: 8px; font-weight: 400; font-size: .88em; opacity: .88; }

    .hint { font-size: .84em; opacity: .82; margin-top: 12px; padding: 8px 10px; border-radius: 8px;
            background: color-mix(in srgb, var(--vscode-foreground) 5%, transparent); }

    .tour-card { border-radius: 12px; padding: 12px 13px; margin: 10px 0;
                 background: linear-gradient(135deg, rgba(99, 102, 241, .10), rgba(6, 182, 212, .05));
                 box-shadow: inset 0 0 0 1px rgba(139, 92, 246, .3); }
    .tour-name { font-weight: 700; font-size: 1.02em; }
    .tour-sub { font-size: .7em; letter-spacing: .2em; opacity: .6; margin-top: 2px; }
    .tour-teaser { font-size: .88em; opacity: .85; margin: 7px 0 2px; }
    .teaser { font-size: .9em; opacity: .9; text-align: center; margin: 10px 0 2px; }
</style></head>
<body>${body}
<script nonce="${nonce}">
    const vscodeApi = acquireVsCodeApi();
    document.querySelectorAll('[data-msg]').forEach((el) => {
        el.addEventListener('click', () => vscodeApi.postMessage({ type: el.dataset.msg, id: el.dataset.id, i: el.dataset.i, tour: el.dataset.tour }));
    });
</script></body></html>`;
    }

    // ロビー: ツアーの一覧から選んで始める(未開始のとき)
    _lobby() {
        const cards = state.tours.map((tour, idx) => `
            <div class="tour-card">
                <div class="tour-name">${idx + 1}. ${escapeHtml(tour.title)}</div>
                ${tour.subtitle ? `<div class="tour-sub">${escapeHtml(tour.subtitle)}</div>` : ''}
                ${tour.teaser ? `<div class="tour-teaser">${escapeHtml(tour.teaser)}</div>` : ''}
                <button class="primary" data-msg="start" data-tour="${escapeHtml(tour.tourId)}">▶ 始める</button>
            </div>`).join('');
        const head = `<div class="emblem"><span class="gem"></span></div>
            <div class="quest-title-row"><span class="quest-badge">QUEST LOG</span>
            <span class="quest-name">チュートリアル</span></div>`;
        const hint = `<p class="hint">💡 このログはエクスプローラーに居座ります(境界をドラッグで高さ調整)。
            他のファイルを開いてもここは隠れません。誤って閉じても、画面下の 🎓 からいつでも呼び戻せます。
            「できた、次へ」は画面下にもあるので、ログを見ていなくても進めます。</p>`;

        return head + cards + hint;
    }

    // ゲームのミッションリスト形式: 済み(✔)・いまここ(▶ 展開カード)・これから(•)を1画面に
    _questLog(i, total) {
        const started = i >= 0;
        const finished = started && i >= total;
        const cleared = started ? Math.min(i, total) : 0;
        const pct = total === 0 ? 0 : Math.round((cleared / total) * 100);

        const items = state.steps.map((step, k) => {
            const num = k + 1;
            if (started && k === i) {
                const actionButtons = step.actions
                    .map((id) => `<button class="action" data-msg="action" data-id="${id}">${escapeHtml(ACTIONS[id].label)}</button>`)
                    .join('');
                return `<li class="current">
                    <div class="current-row"><span class="chip"><i>${num}</i></span><span class="t">${escapeHtml(step.title)}</span></div>
                    <div class="card">
                        ${step.why ? `<p class="why">${escapeHtml(step.why)}</p>` : ''}
                        ${step.do ? `<p class="do">${escapeHtml(step.do)}</p>` : ''}
                        ${actionButtons}
                        <div class="nav">
                            <button data-msg="prev" ${k === 0 ? 'disabled' : ''}>←</button>
                            <button class="primary" data-msg="next">${k === total - 1 ? 'クリアで完了 ✓' : 'できた、次へ →'}</button>
                        </div>
                    </div>
                </li>`;
            }
            if (started && k < i) {
                return `<li class="done" data-msg="goto" data-i="${k}">` +
                    `<span class="chip">✓</span><span class="t">${escapeHtml(step.title)}</span></li>`;
            }
            return `<li class="todo"><span class="chip">${num}</span><span class="t">${escapeHtml(step.title)}</span></li>`;
        }).join('');

        const head = `<div class="emblem"><span class="gem"></span></div>
            <div class="quest-title-row"><span class="quest-badge">QUEST LOG</span>
            <span class="quest-name">${escapeHtml(state.tourTitle)}</span></div>
            ${state.tourSubtitle ? `<div class="quest-sub">${escapeHtml(state.tourSubtitle)}</div>` : ''}
            <div class="progress-row"><div class="bar"><div class="fill" style="width:${pct}%"></div></div>
            <span class="count">${finished ? 'ALL CLEAR' : `${cleared} / ${total}`}</span></div>`;

        let footer;
        if (finished) {
            const nextTour = state.currentTour && state.currentTour.next
                ? state.tours.find((t) => t.tourId === state.currentTour.next)
                : null;
            const outro = state.currentTour && state.currentTour.outro
                ? state.currentTour.outro
                : 'クリアしました。';
            footer = `<div class="clear">🎉 <span class="clear-title">⟡ ALL CLEAR ⟡</span>
                <span class="sub">${escapeHtml(outro)}</span></div>`
                + (nextTour
                    ? `${nextTour.teaser ? `<p class="teaser">${escapeHtml(nextTour.teaser)}</p>` : ''}
                       <button class="primary big" data-msg="start" data-tour="${escapeHtml(nextTour.tourId)}">▶ 次へ: ${escapeHtml(nextTour.title)}</button>`
                    : '')
                + `<button class="quiet" data-msg="stop">表示を終了する</button>`;
        } else {
            footer = `<button class="quiet" data-msg="stop">チュートリアル表示を終了する</button>`;
        }

        return head + `<ol class="quest">${items}</ol>` + footer;
    }
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (ch) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]
    ));
}

// ── ツアー進行 ─────────────────────────────────────────────────────────────

async function startTour(tourId) {
    if (state.tutorRoot === null) {
        state.tutorRoot = await findTutorRoot();
    }
    loadTours();
    if (state.tours.length === 0) {
        vscode.window.showWarningMessage(
            'PractiCase チューター: 開いているフォルダの配下に教材(.tutorial/steps.json)が見つかりません'
        );
        if (state.panel) {
            state.panel.render();
        }
        return;
    }
    const tour = state.tours.find((t) => t.tourId === tourId) || state.tours[0];
    state.currentTour = tour;
    state.steps = tour.steps;
    state.tourTitle = tour.title;
    state.tourSubtitle = tour.subtitle;
    if (state.panel) {
        state.panel.show();
    }
    await gotoStep(0);
}

// ツリーの 👉 バッジ: 対象ファイルを「見ていない間だけ」光らせる(戻り先の案内)。
// 開いている間は消す — 役目を終えた印が残ると視線のノイズになるため
function refreshTreeBadge() {
    const step = state.steps[state.index];
    const target = step && step.file ? step.file : null;
    if (target === null) {
        state.fileDecorations.setTarget(null);
        return;
    }
    const abs = safeWorkspacePath(target);
    const active = vscode.window.activeTextEditor;
    const isViewing = active && abs !== null
        && active.document.uri.fsPath.toLowerCase() === abs.toLowerCase();
    state.fileDecorations.setTarget(isViewing ? null : target);
}

async function gotoStep(i) {
    if (i < 0) {
        return;
    }
    // 連打対策: この呼び出しの世代を刻む。await の間に次の操作(連打・stop等)が
    // state.navGeneration をさらに進めていたら、自分は古い呼び出しなので黙って中断する
    // (state.index 自体はここで即座に確定させるので、連打してもボタンは常に反応する。
    // 遅れて解決した古い呼び出しが、新しいステップの上に古い装飾を上書きするのを防ぐだけ)
    const myGeneration = ++state.navGeneration;
    state.index = i;
    clearEditorDecorations();
    const step = state.steps[i]; // i >= length のときは undefined = 完走画面
    updateStatusBar();
    if (step && step.file) {
        const editor = await openWorkspaceFile(step.file);
        if (myGeneration !== state.navGeneration) {
            return; // 待っている間に追い越された。反映は追い越した側に任せる
        }
        if (editor && step.anchor) {
            applyEditorDecorations(editor, step);
            // 開いた直後はレンダリングが追いつかないことがあるため、少し後にもう一度当てる
            setTimeout(() => {
                if (myGeneration !== state.navGeneration) {
                    return;
                }
                const active = vscode.window.activeTextEditor;
                if (active && active.document === editor.document) {
                    applyEditorDecorations(active, step);
                }
            }, 350);
        }
    }
    refreshTreeBadge();
    if (state.panel) {
        state.panel.render();
    }
}

function updateStatusBar() {
    if (!state.statusBar) {
        return;
    }
    const total = state.steps.length;
    const i = state.index;
    if (state.tours.length === 0) {
        state.statusBar.hide();
        state.nextButton.hide();
    } else if (i < 0 || total === 0) {
        state.statusBar.text = '🎓 チュートリアル';
        state.statusBar.tooltip = 'クエストログを開く';
        state.statusBar.show();
        state.nextButton.hide();
    } else if (i >= total) {
        state.statusBar.text = '🎓 ALL CLEAR';
        state.statusBar.tooltip = 'クエストログを開く';
        state.statusBar.show();
        state.nextButton.hide();
    } else {
        state.statusBar.text = `🎓 ${i + 1}/${total}  ${state.steps[i].title}`;
        state.statusBar.tooltip = 'クエストログを開く';
        state.statusBar.show();
        state.nextButton.show();
    }
}

function stopTour() {
    state.navGeneration++; // 進行中の gotoStep があれば中断させる
    state.index = -1;
    clearEditorDecorations();
    state.fileDecorations.setTarget(null);
    updateStatusBar();
    if (state.panel) {
        state.panel.render();
    }
}

// ── エントリポイント ───────────────────────────────────────────────────────

async function activate(context) {
    createDecorationTypes();

    state.fileDecorations = new TutorFileDecorations();
    context.subscriptions.push(vscode.window.registerFileDecorationProvider(state.fileDecorations));

    state.panel = new TutorPanel(context.extensionUri);
    // retainContextWhenHidden は付けない: render() は毎回 state から HTML を作り直すため
    // 内容保持の利点が無く、非表示中もCSSアニメーション(shimmer/aurora/breathe)が裏で
    // 回り続けて重くなるだけ。非表示になれば破棄され、再表示時は resolveWebviewView が
    // 呼ばれて現在の state から正しく再構築される
    context.subscriptions.push(
        vscode.window.registerWebviewViewProvider('practicaseTutorQuestLog', state.panel)
    );

    state.statusBar = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Left, 100);
    state.statusBar.command = 'practicaseTutor.show';
    context.subscriptions.push(state.statusBar);

    state.nextButton = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Left, 99);
    state.nextButton.text = '$(arrow-right) できた、次へ';
    state.nextButton.tooltip = 'チュートリアルの次のミッションへ(ログが閉じていても進めます)';
    state.nextButton.command = 'practicaseTutor.next';
    context.subscriptions.push(state.nextButton);

    context.subscriptions.push(
        vscode.commands.registerCommand('practicaseTutor.start', startTour),
        vscode.commands.registerCommand('practicaseTutor.stop', stopTour),
        vscode.commands.registerCommand('practicaseTutor.show', () => state.panel.show()),
        vscode.commands.registerCommand('practicaseTutor.next', () => gotoStep(state.index + 1))
    );

    // ファイルを切り替えたとき: 対象ファイルなら演出を再適用し、ツリーバッジの表示/非表示も切り替える
    context.subscriptions.push(
        vscode.window.onDidChangeActiveTextEditor((editor) => {
            refreshTreeBadge();
            const step = state.steps[state.index];
            if (!editor || !step || !step.file) {
                return;
            }
            const abs = safeWorkspacePath(step.file);
            if (abs !== null && editor.document.uri.fsPath.toLowerCase() === abs.toLowerCase()) {
                applyEditorDecorations(editor, step);
            }
        })
    );

    // 教材を先読みして、開始前からロビーとステータスバーを出せる状態にする
    state.tutorRoot = await findTutorRoot();
    if (state.tutorRoot !== null) {
        loadTours();
        updateStatusBar();
        // 最初の1回だけ、開いた瞬間にクエストログを自動表示する(ゲームの導入と同じ体験)
        if (state.tours.length > 0 && context.globalState.get('practicaseTutor.shownOnce') !== true) {
            state.panel.show();
            await context.globalState.update('practicaseTutor.shownOnce', true);
        }
    }
}

function deactivate() {
    clearEditorDecorations();
}

module.exports = { activate, deactivate };

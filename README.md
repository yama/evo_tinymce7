# TinyMCE 7 プラグイン for Evolution CMS

TinyMCE 7 を Evolution CMS に統合するためのプラグインです。イベント `OnRichTextEditorRegister` / `OnRichTextEditorInit` / `OnInterfaceSettingsRender` を通じてコアと連携し、マネージャー・フロントエンドの双方で最新の TinyMCE を利用できる軽量なセットアップを提供します。

## 特長
- **TinyMCE 7 のバンドルと CDN フォールバック**: `assets/plugins/tinymce7/tinymce/js/tinymce/tinymce.min.js` が存在すればローカル版を、それ以外は CDN (jsDelivr) を自動的に読み込みます。
- **柔軟なツールバー・メニュー設定**: システム設定値やプリセット (`simple`/`basic`/`legacy`/`full`) に応じてコンフィグを上書きし、マネージャー UI から簡単にエディターの外観を切り替えられます。
- **言語の自動検出と翻訳**: マネージャーの言語設定を基に TinyMCE の UI 言語とプラグイン設定画面のラベルを自動選択します。
- **MCPuk ファイルブラウザー対応**: Evolution CMS 標準の MCPuk ブラウザーと連携し、`tinymce7_file_browser` パラメーターで切り替え可能です。

## インストール
1. このリポジトリを `assets/plugins/tinymce7` に配置します。
2. Evolution CMS マネージャーでプラグインを作成し、ソースに `plugin.tinymce7.php` を指定します。
3. プラグインに以下のイベントを関連付けます。
   - `OnRichTextEditorRegister`
   - `OnRichTextEditorInit`
   - `OnInterfaceSettingsRender`
4. システム設定の「デフォルトのリッチテキストエディター」を **TinyMCE7** に変更します。

## 基本設定
TinyMCE の初期設定は `assets/plugins/tinymce7/config/` 配下の JSON ファイルで管理します。

| ファイル | 用途 |
| --- | --- |
| `manager.json` | マネージャー画面で読み込む設定。高さ・プラグイン・ツールバーなどを定義します。|
| `frontend.json` | `OnRichTextEditorInit` の `forfrontend` パラメーターが真の場合に使用します。|
| `toolbar-presets.json` | システム設定から選択できるツールバー定義のプリセット集です。|

JSON のキーは TinyMCE 公式ドキュメントに準拠しており、追加・削除も自由です。設定ファイルを編集した場合は Evolution CMS のキャッシュをクリアしてください。

## システム設定パラメーター
プラグインは以下のシステム設定を認識します。

| キー | 説明 |
| --- | --- |
| `tinymce7_toolbar_preset` | `simple` / `basic` / `legacy` / `full` のプリセットを選択します (未設定時は `legacy`)。 |
| `tinymce7_menubar` | メニューバーの表示 (`1`) / 非表示 (`0`) / TinyMCE 既定値 (空)。 |
| `tinymce7_entermode` | Enter キーで段落 (`p`) か改行 (`br`) を挿入するかを決定します。 |
| 互換キー (`tinymce_toolbar_preset`, `tinymce_menubar`, `tinymce4_entermode` など) | 旧 TinyMCE プラグインとの互換性のために自動的に解釈されます。 |

## イベントパラメーター
`OnRichTextEditorInit` イベントに渡されたパラメーターは自動的に TinyMCE の設定に反映されます。

| パラメーター | 役割 |
| --- | --- |
| `elements` | 対象要素の ID。配列またはカンマ区切り文字列を指定できます。 |
| `height` / `width` | エディター領域のサイズ。TinyMCE 設定の `height` / `width` を上書きします。 |
| `forfrontend` | 真の場合は `frontend.json` を読み込みます。 |
| `tinymce7_file_browser` / `file_browser` | `mcpuk` (既定) または `none` を指定します。 |

## ファイルブラウザーのカスタマイズ
MCPuk ブラウザーを有効にすると、`js/mcpuk-picker.js` が TinyMCE の `file_picker_callback` と連携し、選択したファイルの URL を CMS のルートに合わせて正規化します。`tinymce7_file_browser` に `none` を指定することでカスタム実装へ差し替えることも可能です。

## 開発者向けメモ
- オートローダーは `TinyMCE7\` 名前空間を `src/TinyMCE7/` にマッピングします。
- プラグイン本体は `plugin.tinymce7.php` をエントリーポイントとして `TinyMCE7\Plugin::handle()` を実行します。
- Evolution CMS のグローバル関数 `evo()` は `src/bootstrap.php` で提供されます。

## ライセンス
このプロジェクトは [MIT License](LICENSE) の下で公開されています。

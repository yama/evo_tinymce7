# TinyMCE 7 プラグイン 技術メモ

このドキュメントはプラグインのメンテナンスやカスタマイズを担当する技術者向けの情報です。README では触れていない設定方法や内部構造について記載します。

## 同梱ファイルと配置
- プラグインのエントリーポイント: `plugin.tinymce7.php`
- 設定ファイル: `config/manager.json`, `config/frontend.json`, `config/toolbar-presets.json`
- MCPuk 連携スクリプト: `js/mcpuk-picker.js`
- TinyMCE バンドル: `tinymce/js/tinymce/`
- オートローダー: `src/bootstrap.php`

Evolution CMS では `assets/plugins/tinymce7/` 配下に設置し、Composer などの追加ライブラリは不要です。

## コアとの連携イベント
プラグインは以下のイベントでコアと連携します。

| イベント名 | 役割 |
| --- | --- |
| `OnRichTextEditorRegister` | プラグインを利用可能なエディターとして登録します。|
| `OnRichTextEditorInit` | TinyMCE の初期化スクリプトを出力します。|
| `OnInterfaceSettingsRender` | システム設定画面に TinyMCE7 用の設定入力を追加します。|

## 設定ファイル
`config/` 配下の JSON ファイルは TinyMCE の設定オブジェクトに対応しています。キーや値は TinyMCE 公式ドキュメントの項目と同じ形式です。

| ファイル | 説明 |
| --- | --- |
| `manager.json` | 管理画面での TinyMCE 設定。高さ、プラグイン、ツールバーなどを定義。|
| `frontend.json` | `OnRichTextEditorInit` で `forfrontend` パラメーターが真の場合に読み込まれます。|
| `toolbar-presets.json` | 管理画面のプリセット選択肢（simple/basic/legacy/full など）を定義します。|

更新後は Evolution CMS のキャッシュをクリアして反映させます。

## システム設定キー
プラグインが参照する主なシステム設定キーです。旧 TinyMCE プラグインのキーも互換処理しています。

| キー | 内容 |
| --- | --- |
| `tinymce7_toolbar_preset` | プリセットを `simple` / `basic` / `legacy` / `full` から選択します。|
| `tinymce7_menubar` | メニューバーの表示制御（`1` = 表示、`0` = 非表示、空 = TinyMCE 既定値）。|
| `tinymce7_entermode` | Enter キーで挿入する要素を `p` または `br` から選択します。|
| 互換キー (`tinymce_toolbar_preset`, `tinymce_menubar`, `tinymce4_entermode` など) | 旧プラグインとの互換のために自動的に読み替えます。|

## イベントパラメーター
`OnRichTextEditorInit` に渡されたパラメーターは TinyMCE の設定にマージされます。

| パラメーター | 役割 |
| --- | --- |
| `elements` | 対象要素の ID。配列またはカンマ区切りの文字列が利用できます。|
| `height` / `width` | エディター領域のサイズ。TinyMCE 設定の `height` / `width` を上書きします。|
| `forfrontend` | 真の場合は `frontend.json` を読み込んで初期化します。|
| `tinymce7_file_browser` / `file_browser` | `mcpuk`（既定）または `none` を指定できます。|

## MCPuk ファイルブラウザー連携
`tinymce7_file_browser` が `mcpuk` の場合、`js/mcpuk-picker.js` が `file_picker_callback` を上書きし、選択したファイルの URL を CMS のルートに合わせて正規化します。無効化すると TinyMCE の標準ダイアログが利用されます。

## コード構成
- `TinyMCE7\Plugin` クラスがエントリーポイントで、イベントごとの処理を担当します。
- `TinyMCE7\Services\Config` が JSON 設定ファイルの読み込みとマージを行います。
- `TinyMCE7\Services\ToolbarPreset` がプリセットの解決を担当します。
- グローバル関数 `evo()` は `src/bootstrap.php` で定義され、Evolution CMS のサービスロケーターを取得します。

## 開発時のヒント
- TinyMCE のバージョンを更新する場合は `tinymce/` ディレクトリを新しいビルドで置き換えてください。
- 設定のデバッグにはブラウザーのコンソールで `tinyMCE.activeEditor.settings` を確認すると便利です。
- 既存テーマやプラグインとの互換性確認のため、キャッシュをクリアした状態でリッチテキストエディターを複数リソースで試験することを推奨します。

## ライセンス
このプロジェクトは [MIT License](LICENSE) の下で公開されています。

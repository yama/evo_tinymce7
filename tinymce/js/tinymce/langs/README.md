TinyMCE 公式の `langs/*.js` を設置してください（CDN 配布はありません）。
================================
TinyMCE 7 の UI をローカライズする場合は、公式配布パッケージの `langs/` ディレクトリにあるファイルをこのフォルダー（`assets/plugins/tinymce7/tinymce/js/tinymce/langs/`）へそのままコピーしてください。

実装は次の順序でローカル言語ファイルを探します。

1. `assets/plugins/tinymce7/tinymce/js/tinymce/langs/<lang>.js`（公式パッケージをそのまま配置した場合。ファイル名は小文字でも読み込まれます）

ファイルが無い場合は `language_url` を指定せず、TinyMCE 既定の英語 UI になります（CDN フォールバックはありません）。

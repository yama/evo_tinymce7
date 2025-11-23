TinyMCE language packs
======================

日本語の UI 言語ファイル `ja.js` を同梱しています。TinyMCE 公式の [言語パッケージ](https://www.tiny.cloud/get-tiny/language-packages/) から取得した `langs6/<lang>.js` をこのフォルダー（`assets/plugins/tinymce7/tinymce/js/tinymce/langs/`）に追加すると、他言語の UI もローカルで利用できます。

必要な言語ファイルだけを置きたい場合は、`assets/plugins/tinymce7/langs/` に `<lang>.js` を配置しても読み込まれます。

ローカルにファイルが無い場合は、TinyMCE 7 用 CDN パッケージ `@tinymce/tinymce-i18n@7` の `langs6/` から自動取得します。

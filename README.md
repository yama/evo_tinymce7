# TinyMCE 7 プラグインのご案内

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![TinyMCE 7](https://img.shields.io/badge/TinyMCE-7.x-blue.svg)](https://www.tiny.cloud/tinymce/)
[![MODX Evolution](https://img.shields.io/badge/MODX%20Evolution-1.0%2B-0f7fb3.svg)](https://modx.jp/)

MODX Evolution で最新の TinyMCE エディターを利用できるようにする追加プラグインです。

## 特長

* 文字装飾や画像挿入など、管理画面でのリッチテキスト編集をより快適にします。
* 管理画面・フロントエンドの両方で同じエディター体験を実現します。
* MODX Evolution 標準の MCPuk ファイルブラウザーと連携し、画像やファイルを簡単に選択できます。

## 導入手順

1. プラグイン一式をダウンロードします。
2. FTP で MODX Evolution の設置ディレクトリに接続し、`assets/plugins/tinymce7/` フォルダーを作成します。
3. ダウンロードしたファイルをすべて `assets/plugins/tinymce7/` にアップロードします。
4. 管理画面にログインし、**エレメント → プラグイン** を開きます。
5. 「新規プラグイン作成」をクリックし、ソースコード欄に `tinymce.install_base.tpl` の内容を貼り付けるか、該当ファイルを指定します。
6. プラグイン名を「TinyMCE7」など任意に設定し、以下のイベントを関連付けます。

   * `OnRichTextEditorRegister`
   * `OnRichTextEditorInit`
   * `OnInterfaceSettingsRender`
7. 保存後、リソース編集画面でエディターが有効になっていることを確認します。

## カスタマイズ

* **ツールバー設定**: グローバル設定またはユーザー設定の「TinyMCE7 ツールバー プリセット」から、シンプル／ベーシック／レガシー／フルの各プリセットを選択できます。
* **メニューバー表示**: 必要に応じてメニューバーを表示・非表示に切り替えられます。
* **ファイルブラウザー連携**: MCPuk を使用しない場合は設定で無効化し、別のファイル選択方法に差し替えることも可能です。

詳細な設定や開発者向け情報は [TECHNICAL.md](TECHNICAL.md) を参照してください。

## トラブルシューティング

* JavaScript エラーが発生した場合は、ブラウザーの開発者ツールでコンソールを確認し、エラー内容を技術担当者に共有してください。

## ライセンス

本プロジェクトは [MIT License](LICENSE) のもとで公開されています。

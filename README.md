# TinyMCE 7 プラグインのご案内

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![TinyMCE 7](https://img.shields.io/badge/TinyMCE-7.x-blue.svg)](https://www.tiny.cloud/tinymce/)
[![Evolution CMS](https://img.shields.io/badge/Evolution%20CMS-1.4%2B-0f7fb3.svg)](https://evo.im/)

Evolution CMS で最新の TinyMCE エディターを使うための追加プラグインです。旧バージョンの TinyMCE プラグインからの移行や、既存サイトでの入れ替えにも対応しています。

## このプラグインでできること
- 文字装飾や画像挿入など、CMS 管理画面でのリッチテキスト編集をスムーズにします。
- 管理画面とフロントエンドの両方に同じエディター体験を提供できます。
- Evolution CMS 標準の MCPuk ファイルブラウザーと連携して画像やファイルを選択できます。

## 導入手順（FTP 作業あり）
1. プラグイン一式をローカルにダウンロードします。
2. FTP で Evolution CMS の設置先に接続し、`assets/plugins/tinymce7/` フォルダーを作成します。
3. このリポジトリの内容をすべて `assets/plugins/tinymce7/` にアップロードします。
4. Evolution CMS マネージャーにログインし、**エレメント → プラグイン** を開きます。
5. 「新規プラグイン作成」を押し、ソースコードに `plugin.tinymce7.php` の内容を貼り付けるか、該当ファイルを選択します。
6. プラグイン名を「TinyMCE7」など任意の名前にし、以下のイベントを関連付けます。
   - `OnRichTextEditorRegister`
   - `OnRichTextEditorInit`
   - `OnInterfaceSettingsRender`
7. システム設定の「デフォルトのリッチテキストエディター」で **TinyMCE7** を選択します。
8. 変更を保存したら、リソースの編集画面でエディターが有効になっているか確認します。

## よく使うカスタマイズ
- **ツールバーの切り替え**: システム設定にある「TinyMCE7 ツールバー プリセット」から、シンプル／ベーシック／レガシー／フルを選べます。
- **メニューバーの表示**: 必要に応じてメニューバーを表示・非表示に切り替えられます。
- **ファイルブラウザー**: MCPuk が不要な場合は設定から無効化し、別のファイル選択方法に差し替えることもできます。

より詳しい設定や開発者向けの情報は、[TECHNICAL.md](TECHNICAL.md) を参照してください。

## README に追加できるバッジ
バッジは README の冒頭などに掲載できる小さなラベルです。サービスの状態やサポート範囲を視覚的に伝えられるので、必要に応じて以下の例を活用してください。

| 用途 | 表示例 | Markdown コード |
| --- | --- | --- |
| ライセンス表記 | ![License: MIT](https://img.shields.io/badge/license-MIT-green.svg) | ``[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)`` |
| 対応 TinyMCE バージョン | ![TinyMCE 7](https://img.shields.io/badge/TinyMCE-7.x-blue.svg) | ``[![TinyMCE 7](https://img.shields.io/badge/TinyMCE-7.x-blue.svg)](https://www.tiny.cloud/tinymce/)`` |
| Evolution CMS 対応バージョン | ![Evolution CMS](https://img.shields.io/badge/Evolution%20CMS-1.4%2B-0f7fb3.svg) | ``[![Evolution CMS](https://img.shields.io/badge/Evolution%20CMS-1.4%2B-0f7fb3.svg)](https://evo.im/)`` |

その他のバッジは [Shields.io](https://shields.io/) や Qiita 記事（例: <https://qiita.com/ma91n/items/6c572c5887a50223c2b1>）を参考に追加できます。必要に応じて、配布方法やバージョン管理ツールに合わせたバッジを組み合わせてください。

## トラブルシューティング
- エディターが表示されない場合は、キャッシュの削除とブラウザーの再読み込みを試してください。
- JavaScript エラーが出ている場合は、ブラウザーの開発者ツールでコンソールを確認し、必要に応じて技術担当者に共有してください。
- 旧 TinyMCE プラグインと併用している場合は、イベントや設定が重複しないようにご注意ください。

## ライセンス
このプロジェクトは [MIT License](LICENSE) の下で公開されています。

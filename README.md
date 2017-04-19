主に単語で答えるゲームにおける汎用的な辞書形式に関するAPI
=====================================================
次のゲームの辞書を相互に変換できるようにする Web API を提供します。

* [主に単語で答えるゲームにおける汎用的な辞書形式] \(以下、汎用辞書形式)
* [キャッチフィーリング]、[Drawing Catch] \(*.cfq)
* [きゃっちま] \(*.dat) ※暗号化後のファイルは扱えません
* [Inteligenceω] \(*.txt, *.zip) ※暗号化後のファイルは扱えません
* [ピクトセンス]

[主に単語で答えるゲームにおける汎用的な辞書形式]: https://github.com/esperecyan/dictionary/blob/master/dictionary.md
[キャッチフィーリング]: http://forest.watch.impress.co.jp/library/software/catchfeeling/
[Drawing Catch]: http://drafly.nazo.cc/games/olds/DC
[きゃっちま]: http://vodka-catchm.seesaa.net/article/115922159.html
[ピクトセンス]: http://pictsense.com/
[Inteligenceω]: http://loxee.web.fc2.com/inteli.html

動作デモ
--------
https://esperecyan.github.io/dictionary-api/demo/

使い方
------
https://game.pokemori.jp/dictionary-api/v0/converter

上記URLに対し、以下のパラメータをmultipart/form-data形式でPOSTします。

| キー  | 値                                                                          |
|-------|-----------------------------------------------------------------------------|
| input | 辞書ファイル。                                                              |
| title | 辞書のタイトル。指定されていなければ、inputキーのファイル名から判断します。汎用辞書で `@title` フィールドが存在する場合、この指定は無視されます。 |
| from  | 変換元の辞書形式。`キャッチフィーリング` `きゃっちま` `Inteligenceω クイズ` `Inteligenceω しりとり` `ピクトセンス` `汎用辞書` のいずれか。指定されていないか間違った値が指定されていれば、inputキーのファイル名から判断します。Inteligenceωについては、コメント行、空行を除く最初の行が `Q,` で始まるか否かで、クイズとしりとりを判別します。 |
| to    | 変換先の辞書形式。`キャッチフィーリング` `きゃっちま` `Inteligenceω クイズ` `Inteligenceω しりとり` `ピクトセンス` `汎用辞書` のいずれか。指定されていないか間違った値が指定されていれば、`汎用辞書` になります。 |

### レスポンス

以下のパラメータがmultipart/form-data形式で返ります。

| キー            | 値                                                                                         |
|-----------------|--------------------------------------------------------------------------------------------|
| output          | 辞書ファイル。`content-disposition` ヘッダの `filename*` パラメータにファイル名を含みます。|
| parser-logs     | 構文解析時のログ。ログが存在しない場合はこのキーも存在しない。                             |
| serializer-logs | 直列化時のログ。ログが存在しない場合はこのキーも存在しない。                               |

ログはいずれも[application/problem+json]形式で問題点の一覧を返します。
拡張メンバの構造については https://github.com/esperecyan/dictionary-api/blob/master/logs.md をご覧ください。

### エラー
4xxクラス、または5xxクラスのHTTPステータスコード、および[application/problem+json]形式でエラーの説明を返します。

| type                                                                         | title                 | status | 原因                                                                         |
|------------------------------------------------------------------------------|-----------------------|--------|------------------------------------------------------------------------------|
| https://github.com/esperecyan/dictionary-api/blob/master/malformed-syntax.md | Malformed Syntax      |[400]   | 指定された形式を想定した構文解析に失敗したことを表します。                   |
| https://github.com/esperecyan/dictionary-api/blob/master/serialize-error.md  | Serialize Error       |[400]   | 指定された形式へ直列化できる辞書ではなかったことを表します。                 |
| about:blank                                                                  | Bad Request           |[400]   | inputキーで辞書ファイルが与えられなかった場合。                              |
| about:blank                                                                  | Method Not Allowed    |[405]   | POST以外のメソッドでリクエストした場合。                                     |
| about:blank                                                                  | Not Implemented       |[501]   | 〃                                                                           |
| about:blank                                                                  | Payload Too Large     |[413]   | POSTしたファイル、またはPOSTデータ全体のファイルが大き過ぎることを表します。 |
| about:blank                                                                  | Internal Server Error |[500]   | サーバー側の設定ミスなどに起因するエラー。                                   |

[application/problem+json]: https://tools.ietf.org/html/rfc7807 "Problem Details for HTTP APIs"
[400]: https://triple-underscore.github.io/RFC7231-ja.html#status.400
[405]: https://triple-underscore.github.io/RFC7231-ja.html#status.405
[501]: https://triple-underscore.github.io/RFC7231-ja.html#status.501
[413]: https://triple-underscore.github.io/RFC7231-ja.html#status.413
[500]: https://triple-underscore.github.io/RFC7231-ja.html#status.500

Contribution
------------
Pull Request、または Issue よりお願いいたします。

ライセンス
----------
当スクリプトのライセンスは [Mozilla Public License Version 2.0] \(MPL-2.0) です。

ただし、[tests/resources/inteligenceo/quiz-input.txt] および [tests/resources/inteligenceo/shiritori-input.txt] は
MPL-2.0 ではないフリーのファイルであり、著作権は[ろくしー様]にあります。

[Mozilla Public License Version 2.0]: https://www.mozilla.org/MPL/2.0/
[tests/resources/inteligenceo/quiz-input.txt]: tests/resources/inteligenceo/quiz-input.txt
[tests/resources/inteligenceo/shiritori-input.txt]: tests/resources/inteligenceo/shiritori-input.txt
[ろくしー様]: https://twitter.com/loxeee

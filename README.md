主に単語で答えるゲームにおける汎用的な辞書形式に関するAPI
=====================================================
次のゲームの辞書を[主に単語で答えるゲームにおける汎用的な辞書形式] \(以下、汎用辞書形式)に変換する Web API を提供します。
また、汎用辞書形式のファイルをPOSTした場合、校正可能なら校正して返します。

* [キャッチフィーリング]、[Drawing Catch] \(*.cfq)
* [きゃっちま] \(*.dat) ※暗号化後のファイルは扱えません
* [Inteligenceω] \(*.txt) ※暗号化後のファイルは扱えません
* [ピクトセンス]

Inteligenceωの辞書で画像・音声ファイルへのパスが含まれる場合、単にファイル名を抽出します。
汎用辞書形式のZIPファイルをPOSTした場合を除き、当APIはCSVファイルのみを返し、ZIPファイル化などは行いません。

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

### エラー
4xxクラス、または5xxクラスのHTTPステータスコード、およびJSONスキーマ[error-schema.json]で示される形式でエラーの説明を返します。

| ステータスコード | エラーコード        | 説明                                                                         |
|------------------|---------------------|------------------------------------------------------------------------------|
| [400]            | MalformedRequest    | inputキーで辞書ファイルが与えられなかった場合。                              |
| [400]            | MalformedSyntax     | 指定された形式を想定した構文解析に失敗したことを表します。                   |
| [405]            | MethodNotAllowed    | POST以外のメソッドでリクエストした場合。                                     |
| [501]            | NotImplemented      | 〃                                                                           |
| [413]            | PayloadTooLarge     | POSTしたファイル、またはPOSTデータ全体のファイルが大き過ぎることを表します。 |
| [500]            | InternalServerError | サーバー側の設定ミスなどに起因するエラー。                                   |

[error-schema.json]: error-schema.json
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

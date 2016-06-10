Logs
====
[PSR-3: Logger Interface]のログの一覧を表します。

[PSR-3: Logger Interface]: http://guttally.net/psr/psr-3/ "この文書では，ロギングライブラリのための共通インタフェースについて記述します。"

例
---

```json
{
    "type": "https://github.com/esperecyan/dictionary-api/blob/master/logs.md",
    "title": "Logs",
    "details": "構文解析時に1つ以上のロギングが発生しました。",
    "logs": [
        {
            "level": "error",
            "message": "「0.500」は実数の規則に合致しません。"
        },
        {
            "level": "warning",
            "message": "日本語話者向けの辞書であれば、解答はひらがなかカタカナにすべきです: 𩸽"
        }
    ]
}
```

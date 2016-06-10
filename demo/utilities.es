'use strict';

/**
 * HTML、XML、DOMに関するメソッド等。
 */
window.MarkupUtils = {
	/**
	 * Atom名前空間。
	 * @constant {string}
	 */
	ATOM_NAMESPACE: 'http://www.w3.org/2005/Atom',
	
	/**
	 * XMLの特殊文字と文字参照の変換テーブル。
	 * @constant {Object.<string>}
	 */
	CHARACTER_REFERENCES_TRANSLATION_TABLE: {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&apos;',
	},
	
	/**
	 * XMLの特殊文字を文字参照に置換します。
	 * @see [html - HtmlSpecialChars equivalent in Javascript? - Stack Overflow]{@link http://stackoverflow.com/a/4835406}
	 * @param {string} str - プレーンな文字列。
	 * @returns {string} HTMLとして扱われる文字列。
	 */
	convertSpecialCharactersToCharacterReferences(str) {
		return String(str).replace(
			/[&<>"']/g,
			specialCharcter => this.CHARACTER_REFERENCES_TRANSLATION_TABLE[specialCharcter]
		);
	},
	
	/**
	 * テンプレート文字列のタグとして用いることで、式内にあるXMLの特殊文字を文字参照に置換します。
	 * @param {string[]} htmlTexts
	 * @param {...string} plainText
	 * @returns {string} HTMLとして扱われる文字列。
	 */
	escapeTemplateStrings(htmlTexts, ...plainTexts) {
		return String.raw(
			htmlTexts,
			...plainTexts.map(plainText => this.convertSpecialCharactersToCharacterReferences(plainText))
		);
	},

	/**
	 * 指定したURLからファイルをダウンロードします。
	 * @param {string} url
	 * @param {string} filename
	 */
	download(url, filename) {
		let body = document.body;
		body.insertAdjacentHTML('beforeend', h`<a href="${url}" download="${filename}" hidden=""></a>`);
		let anchor = body.lastElementChild;
		anchor.click();
		anchor.remove();
	}
};

/**
 * {@link MarkupUtils.escapeTemplateStrings}、または {@link MarkupUtils.convertSpecialCharactersToCharacterReferences} の短縮表記。
 * @example
 * // returns "<code>&lt;a href=&quot;https://example.com/&quot;link text&lt;/a&gt;</code>"
 * h`<code>${'<a href="https://example.com/">link text</a>'}</code>`;
 * @example
 * // returns "&lt;a href=&quot;https://example.com/&quot;link text&lt;/a&gt;"
 * h('<a href="https://example.com/">link text</a>');
 */
window.h = function () {
	return Array.isArray(arguments[0])
		? MarkupUtils.escapeTemplateStrings(...arguments)
		: MarkupUtils.convertSpecialCharactersToCharacterReferences(arguments[0]);
};

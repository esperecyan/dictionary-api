(function () {
'use strict';

// Polyfill for Firefox, Opera, and Google Chrome
if (!('prepend' in document)) {
	/**
	 * 複数のインターフェースに[Unscopable]拡張属性を伴うメンバーを実装します。
	 * @param {Function[]} interfaces。
	 * @param {Object.<Function>} members
	 */
	let implementUnscopableMembers = function (interfaces, members) {
		for (let i_f of interfaces) {
			Object.assign(i_f.prototype, members);
			if (Symbol.unscopables) {
				let object = {};
				for (let memberName of Object.keys(members)){
					object[memberName] = true;
				}
				if (i_f.prototype[Symbol.unscopables]) {
					Object.assign(i_f.prototype[Symbol.unscopables], object);
				} else {
					i_f.prototype[Symbol.unscopables] = object;
				}
			}
		}
	};
	
	/**
	 * @see [DOM Standard]{@link https://dom.spec.whatwg.org/#converting-nodes-into-a-node}
	 * @param {(Node|string)[]} nodes
	 * @returns {(Node|DocumentFragment)}
	 */
	let convertNodesIntoNode = function (nodes) {
		for (let i = 0, l = nodes.length; i < l; i++) {
			if (!(nodes[i] instanceof Node)) {
				nodes[i] = new Text(nodes[i]);
			}
		}
		if (nodes.length === 1) {
			return nodes[0];
		}
		let fragment = new DocumentFragment();
		for (let node of nodes) {
			fragment.appendChild(node);
		}
		return fragment;
	};
	
	// https://dom.spec.whatwg.org/#interface-parentnode
	implementUnscopableMembers([Document, DocumentFragment, Element], {
		/**
		 * Inserts nodes before the first child of node, while replacing strings in nodes with equivalent Text nodes.
		 * @see [DOM Standard]{@link https://dom.spec.whatwg.org/#dom-parentnode-prepend}
		 * @param {...(Node|string)} nodes
		 * @throws {DOMException} Throws a HierarchyRequestError if the constraints of the node tree are violated. 
		 */
		prepend(...nodes) {
			this.insertBefore(convertNodesIntoNode(nodes), this.firstChild);
		},
		/**
		 * Inserts nodes after the last child of node, while replacing strings in nodes with equivalent Text nodes.
		 * @see [DOM Standard]{@link https://dom.spec.whatwg.org/#dom-parentnode-append}
		 * @param {...(Node|string)} nodes
		 * @throws {DOMException} Throws a HierarchyRequestError if the constraints of the node tree are violated. 
		 */
		append(...nodes) {
			this.appendChild(convertNodesIntoNode(nodes));
		},
	});
	
	// https://dom.spec.whatwg.org/#interface-childnode
	implementUnscopableMembers([DocumentType, Element, CharacterData], {
		/**
		 * Inserts nodes just before node, while replacing strings in nodes with equivalent Text nodes.
		 * @see [DOM Standard]{@link https://dom.spec.whatwg.org/#dom-childnode-before}
		 * @param {...(Node|string)} nodes
		 * @throws {DOMException} Throws a HierarchyRequestError if the constraints of the node tree are violated. 
		 */
		before(...nodes) {
			let parent = this.parentNode;
			if (!parent) {
				return;
			}
			let viablePreviousSibling;
			while ((viablePreviousSibling = this.previousSibling) && nodes.includes(viablePreviousSibling)) {
			}
			parent.insertBefore(
				convertNodesIntoNode(nodes),
				viablePreviousSibling ? viablePreviousSibling.nextSibling : parent.firstChild
			);
		},
		/**
		 * Inserts nodes just after node, while replacing strings in nodes with equivalent Text nodes.
		 * @see [DOM Standard]{@link https://dom.spec.whatwg.org/#dom-childnode-after}
		 * @param {...(Node|string)} nodes
		 * @throws {DOMException} Throws a HierarchyRequestError if the constraints of the node tree are violated. 
		 */
		after(...nodes) {
			let parent = this.parentNode;
			if (!parent) {
				return;
			}
			let viableNextSibling;
			while ((viableNextSibling  = this.nextSibling) && nodes.includes(viableNextSibling)) {
			}
			parent.insertBefore(convertNodesIntoNode(nodes), viableNextSibling);
		},
		/**
		 * Replaces node with nodes, while replacing strings in nodes with equivalent Text nodes.
		 * @see [DOM Standard]{@link https://dom.spec.whatwg.org/#dom-childnode-replacewith}
		 * @param {...(Node|string)} nodes
		 * @throws {DOMException} Throws a HierarchyRequestError if the constraints of the node tree are violated. 
		 */
		replaceWith(...nodes) {
			let parent = this.parentNode;
			if (!parent) {
				return;
			}
			let viableNextSibling;
			while ((viableNextSibling  = this.nextSibling) && nodes.includes(viableNextSibling)) {
			}
			let node = convertNodesIntoNode(nodes);
			if (this.parentNode === parent) {
				parent.replaceChild(node, this);
			} else {
				parent.insertBefore(node, viableNextSibling);
			}
		},
	});
}

// Polyfill for Opera and Google Chrome
if (!('formData' in Response.prototype)) {
	/**
	 * HTTPヘッダフィールド値で許容するパラメータの最大数。
	 * @constant {number}
	 */
	const MAX_PARAMETERS = 100;
	
	let Body = {
		/**
		 * HTTPヘッダフィールド値の正規表現。
		 * @see [Regex for quoted string with escaping quotes — Stack Overflow]{@link https://stackoverflow.com/questions/249791/regex-for-quoted-string-with-escaping-quotes/249937#249937}
		 * @constant {string}
		 */
		HEADER_FIELD_VALUE_PATTERN: new RegExp(
			'^[ \\t]*([!-~]+)' + '(?:[ \\t]*;[ \\t]*([-0-9a-z]+)=([^\\x00- ";]*|"(?:[^"\\\\]|\\\\.)*")*[ \\t]*)?'.repeat(MAX_PARAMETERS) + '$',
			'i'
		),

		/**
		 * 構文解析に失敗したときのFirefoxの例外メッセージ。
		 * @constant {string}
		 */
		EXCEPTION_MESSAGE: 'Could not parse content as FormData.',
		
		/**
		 * 正規表現のメタ文字をエスケープします。
		 * @see [@link JavaScript の正規表現のメタ文字をエスケープ — 冬通りに消え行く制服ガールは✖夢物語にリアルを求めない。 — subtech]{@link http://subtech.g.hatena.ne.jp/cho45/20090513/1242199703}
		 * @param {string} str
		 * @returns {string}
		 */
		escapeForRegExp: function (str)
		{
			return str.replace(/[\s\S]/g, function (_) {
				return '\\u' + (0x10000 + _.charCodeAt(0)).toString(16).slice(1);
			});
		},
		
		/**
		 * mutipart/form-data パート内のHTTPヘッダフィールドを解析します。
		 * @param {string} headerFields - 末尾に改行を含まない。
		 * @returns {Object.<string>} ヘッダ名は小文字に正規化。
		 */
		parseHeaderFields: function (headerFields)
		{
			let headers = {};
			for (let namesAndValues = headerFields.split('\r\n'), i = 0, l = namesAndValues.length; i < l; i++) {
				let index = namesAndValues[i].indexOf(':');
				let name = namesAndValues[i].substring(0, index).toLowerCase();
				let value = namesAndValues[i].substring(index + 1);
				if (name in headers) {
					headers[name] += ',' + value;
				} else {
					headers[name] = value;
				}
			}
			return headers;
		},
		
		/**
		 * HTTPヘッダフィールド値の構文解析を行います。
		 * @param {string} value
		 * @throws {TypeError} 構文解析に失敗した場合。
		 * @returns {(string|Object.<string>)[]} 1番目の要素にパラメータの前の部分、2番目の要素にパラメータ名をキーに持つオブジェクトを含む配列。
		 */
		parseHeaderFieldValue: function (value)
		{
			let result = this.HEADER_FIELD_VALUE_PATTERN.exec(value);
			if (!result) {
				throw new TypeError(Body.EXCEPTION_MESSAGE);
			}
			result.shift();
			
			let typeAndParameters = [result.shift(), {}];
			
			let name = null;
			for (let i = 0, l = result.length; i < l; i++) {
				if (result[i] === undefined) {
					break;
				}
				if (name === null) {
					name = result[i];
				} else {
					typeAndParameters[1][name] = result[i].startsWith('"')
						? result[i].substring(1, result[i].length - 1).replace(/\\(.)/g, '$1')
						: result[i];
					name = null;
				}
			}
			
			return typeAndParameters;
		},
		
		/**
		 * multipart/form-data 形式のバイト列を構文解析します。
		 * @param {string} bytes
		 * @param {string} boundary
		 * @throws {TypeError} content-dispositionヘッダが存在しない、disposition型が「form-data」でない、またはnameパラメータが存在しないパートがある場合。
		 * @returns {FormData}
		 */
		parseBytes: function (bytes, boundary)
		{
			let escapedBoundary = Body.escapeForRegExp(boundary);
			let bodyParts = bytes
				.replace(new RegExp('^(.*?\\r\\n)?--' + escapedBoundary + '[ \\t]*\\r\\n|\\r\\n--' + escapedBoundary + '--[ \\t]*(\\r\\n.*?)?$', 'g'), '')
				.split(new RegExp('\\r\\n--' + escapedBoundary + '[ \\t]*\\r\\n'));
			
			let formData = new FormData();
			for (let i = 0, l = bodyParts.length; i < l; i++) {
				let index = bodyParts[i].indexOf('\r\n\r\n');
				
				let headers = Body.parseHeaderFields(bodyParts[i].substring(0, index));
				if (!('content-disposition' in headers)) {
					throw new TypeError(Body.EXCEPTION_MESSAGE);
				}
				let contentDisposition = Body.parseHeaderFieldValue(headers['content-disposition']);
				if (contentDisposition[0] !== 'form-data' || !('name' in contentDisposition[1])) {
					throw new TypeError(Body.EXCEPTION_MESSAGE);
				}
			
				let body = bodyParts[i].substring(index + 4);
				formData.append(
					contentDisposition[1].name,
					'filename' in contentDisposition[1] ? new File(
						[body],
						contentDisposition[1].filename,
						{type: headers['content-type'] || 'text/plain'}
					) : body
				);
			}
			return formData;
		},
		
		/**
		 * application/x-www-form-urlencoded 形式のバイト列を FormData インスタンスに変換します。
		 * @param {string} bytes
		 * @returns {FormData}
		 */
		convertToFormData: function (bytes)
		{
			let formData = new FormData();
			new URLSearchParams(bytes).forEach(function (name, value) {
				formData.append(name, value);
			});
			return formData;
		},
		
		/**
		 * The formData() method of the Body mixin takes a Response stream and reads it to completion.
		 * It returns a promise that resolves with a FormData object.
		 * @see [Body.formData() — Web APIs | MDN]{@link https://developer.mozilla.org/docs/Web/API/Body/formData}
		 * @throws {TypeError} content-typeヘッダが結び付けられていない場合、MIME型にboundaryパラメータが存在しない場合、
		 *		またはMIME型が「multipart/form-data」でも「application/x-www-form-urlencoded」でもない場合。
		 * @returns {Promise.<FormData>}
		 */
		formData: function formData()
		{
			return this.text().then(function (text) {
				let contentType = this.headers.get('content-type');
				if (!contentType) {
					throw new TypeError(Body.EXCEPTION_MESSAGE);
				}
				let mime = Body.parseHeaderFieldValue(contentType);
				switch (mime[0].toLowerCase()) {
					case 'multipart/form-data':
						if (!('boundary' in mime[1])) {
							throw new TypeError(Body.EXCEPTION_MESSAGE);
						}
						return Body.parseBytes(text, mime[1].boundary);
					case 'application/x-www-form-urlencoded':
						return Body.convertToFormData(text);
					default:
						throw new TypeError(Body.EXCEPTION_MESSAGE);
				}
			}.bind(this));
		},
	};
	
	Request.prototype.formData = Body.formData;
	Response.prototype.formData = Body.formData;
}

// Polyfill for Microsoft Edge
try {
	new File([''], '');
} catch (exception) {
	File = new Proxy(File, {
		construct: function (File, argumentsList) {
			if (argumentsList.length < 2) {
				throw new TypeError('Argument not optional');
			}
			let file = new Blob(argumentsList[0], argumentsList[2] || {});
			Object.defineProperty(file, 'name', {get: function () {
				return String(argumentsList[1]);
			}});
			if (argumentsList[2] && 'lastModified' in argumentsList[2]) {
				Object.defineProperty(file, 'lastModified', {get: function () {
					return Number.parseInt(argumentsList[2]);
				}});
			}
			Object.setPrototypeOf(file, File.prototype);
			return file;
		},
	});
}

if (!('get' in new FormData())) {
	let entriesList = new WeakMap();
	FormData.prototype.append = new Proxy(FormData.prototype.append, {
		apply: function (append, formData, argumentsList) {
			Reflect.apply(append, formData, argumentsList);
			let entries = entriesList.get(formData) || [];
			entries.push([
				String(argumentsList[0]),
				argumentsList[1] instanceof Blob
					? (!(argumentsList[1] instanceof File) || 2 in argumentsList
						? new File([argumentsList[1]], 2 in argumentsList ? argumentsList[2] : 'blob')
						: argumentsList[1])
					: String(argumentsList[1]),
			]);
			entriesList.set(formData, entries);
		},
	});
	FormData.prototype.has = function (name) {
		let entries = entriesList.get(this) || [];
		return entries.some(function (entry) {
			return entry[0] === name;
		});
	};
	FormData.prototype.get = function (name) {
		let entries = entriesList.get(this) || [];
		let entry = entries.find(function (entry) {
			return entry[0] === name;
		});
		return entry ? entry[1] : null;
	};
}

})();
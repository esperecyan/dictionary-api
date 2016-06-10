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

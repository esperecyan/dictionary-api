'use strict';

let noScript = document.getElementsByClassName('no-script')[0];
let strong = noScript.getElementsByTagName('strong')[0];
if (!('fetch' in window)) {
	strong.textContent = 'ご利用中のブラウザは WindowOrWorkerGlobalScope#fetch() に対応していません。';
} else if (!('formData' in Response.prototype)) {
	strong.textContent = 'ご利用中のブラウザは Body#formData() に対応していません。';
} else {
	noScript.remove();
	let form = document.forms[0];
	for (let formControl of Array.from(form)) {
		formControl.disabled = false;
	}
	
	let toInteligenceoQuiz = Array.from(form.to).find(radioButton => radioButton.value === 'Inteligenceω クイズ');
	let inputFile = form.input.parentElement;
	let inputText;
	
	form.addEventListener('change', function (event) {
		if (event.target.name !== 'from') {
			return;
		}
		switch (event.target.value) {
			case '自動判定':
				form.input.accept = 'text/plain,text/csv,application/zip,.cfq,.dat,.txt,.csv';
				break;
			case 'キャッチフィーリング':
				form.input.accept = '.cfq';
				break;
			case 'きゃっちま':
				form.input.accept = '.dat';
				break;
			case 'Inteligenceω クイズ':
				form.input.accept = '.txt';
				break;
			case 'Inteligenceω しりとり':
				form.input.accept = '';
				break;
			case 'ピクトセンス':
				if (inputText) {
					inputFile.replaceWith(inputText);
				} else {
					inputFile.insertAdjacentHTML('afterend', '<textarea name="input" required=""></textarea>');
					inputText = inputFile.nextElementSibling;
					inputFile.remove();
				}
				break;
			case '汎用辞書':
				form.input.accept = 'text/csv,application/zip';
				break;
		}
		
		if (event.target.value !== 'ピクトセンス' && inputText && document.contains(inputText)) {
			inputText.replaceWith(inputFile);
		}
		
		toInteligenceoQuiz.disabled = !['', 'Inteligenceω クイズ', '汎用辞書'].includes(event.target.value);
		if (toInteligenceoQuiz.disabled) {
			toInteligenceoQuiz.parentElement.title = '指定した辞書形式からInteligenceω クイズへは変換できません。'
		} else {
			toInteligenceoQuiz.parentElement.removeAttribute('title');
		}
	});
	form.querySelector('[name="from"]:checked').dispatchEvent(new Event('change', {bubbles: true}));
	
	form.addEventListener('submit', function (event) {
		event.preventDefault();
		let submitButton = event.target.getElementsByTagName('button')[0];
		submitButton.disabled = true;
		
		let to = event.target.to.value;
		
		let formData = new FormData(event.target);
		let input = event.target.input;
		if (event.target.input.type !== 'file') {
			// 変換前の辞書形式がピクトセンスなら
			formData.set('input', new File([input.value], 'dictionary.csv', {type: 'text/csv; header=absent; charset=UTF-8'}));
		}
		window.fetch(event.target.action, {
			method: 'POST',
			body: formData,
		}).then(function (response) {
			return response.ok
				? response.formData()
				: response.json().then(problemDetail => Promise.reject(new DOMException(
					`【${problemDetail.title}】${problemDetail.detail}`,
					problemDetail.type === 'https://github.com/esperecyan/dictionary-api/blob/master/serialize-error.md' ? 'DataError' : 'SyntaxError'
				)));
		}).then(function (formData) {
			// 辞書のダウンロード
			let output = formData.get('output');
			if (to === 'ピクトセンス') {
				return new Response(output).text().then(function (text) {
					event.target.output.innerHTML = h`<textarea>${text}</textarea>`;
					return Promise.resolve(formData);
				});
			} else {
				event.target.output.innerHTML = h`<a href="${URL.createObjectURL(output)}" download="${output.name}">変換した辞書をダウンロードする</a>`;
				event.target.output.firstElementChild.click();
				return Promise.resolve(formData);
			}
		}).then(function (formData) {
			event.target.output.hidden = false;
			return Promise.resolve(formData);
		}).then(function (formData) {
			// 構文解析ログの表示
			let parserLogs = document.getElementById('parser-logs');
			if (formData.has('parser-logs')) {
				return new Response(formData.get('parser-logs')).json().then(function (problemDetail) {
					parserLogs.getElementsByTagName('ol')[0].innerHTML
						= problemDetail.logs.map(log => h`<li>${log.level}: <pre>${log.message}</pre></li>`).join('');
					parserLogs.hidden = false;
					return Promise.resolve(formData);
				});
			} else {
				parserLogs.hidden = true;
				return Promise.resolve(formData);
			}
		}).then(function (formData) {
			// 直列化ログの表示
			let serializerLogs = document.getElementById('serializer-logs');
			if (formData.has('serializer-logs')) {
				return new Response(formData.get('serializer-logs')).json().then(function (problemDetail) {
					serializerLogs.getElementsByTagName('ol')[0].innerHTML
						= problemDetail.logs.map(log => h`<li><pre>${log.level}: ${log.message}</pre></li>`).join('');
					serializerLogs.hidden = false;
					return Promise.resolve(formData);
				});
			} else {
				serializerLogs.hidden = true;
				return Promise.resolve(formData);
			}
		}).then(function () {
			submitButton.disabled = false;
		}).catch(function (error) {
			event.target.output.innerHTML = h`<strong><span class="error">エラーが発生しました。</span><br /><pre>${error}</pre></strong>`;
			event.target.output.hidden = false;
			document.getElementById('parser-logs').hidden = true;
			document.getElementById('serializer-logs').hidden = true;
			submitButton.disabled = false;
		});
	});
	
}

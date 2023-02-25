import 'https://cdn.plot.ly/plotly-2.18.2.min.js';

const APP = '3DPABLMV';

const debug = true; // 'debug' in document.documentElement.mesh;

const store = isStorageAvailable(window.localStorage)
	? window.localStorage
	: null;

debug && console.log(`[${APP}]`, 'isStorageAvailable:', !!store);

/**
 * @param {HTMLElement} element
 * @param {boolean}     busy
 */
function applyBusyState(element, busy = true) {
	element.setAttribute('aria-busy', (element.ariaBusy = busy));
}

/**
 * @param {HTMLElement} element
 * @param {boolean}     disabled
 */
function applyDisabledState(element, disabled = true) {
	element.setAttribute('aria-disabled', (element.ariaDisabled = disabled));
}

/**
 * @param {int[]} array
 *
 * @returns {int}
 */
function calculateStandardDeviation(array) {
	if (!array || array.length === 0) {
		return 0;
	}

	const n = array.length;
	const mean = array.reduce((a, b) => a + b) / n;
	return Math.sqrt(array.map(x => Math.pow(x - mean, 2)).reduce((a, b) => a + b) / n);
}

/**
 * @param {HTMLOutputElement} outputElement
 * @param {Object}            data
 *
 * @returns {Promise<[number, PlotlyHTMLElement]>}
 */
function drawGraph(graphElement, stdElement, data) {
	const promises = [];

	promises.push(new Promise((resolve, reject) => {
		try {
			const std = calculateStandardDeviation(data.flat()).toFixed(3);
			stdElement.value = `Standard Deviation: ${std} mm`;
			resolve(std);
		} catch (error) {
			reject(error);
		}
	}));

	const graphData = [
		{
			z: data,
			type: 'surface',
			contours: {
				z: {
					show: true,
					usecolormap: true,
					highlightcolor: "#42F462",
					project: {
						z: true,
					},
				},
			},
		},
	];

	const graphLayout = {
		autosize: true,
		margin: {
			l: 0,
			r: 0,
			b: 0,
			t: 0,
		},
		scene: {
			zaxis: {
				autorange: false,
				range: [ -1, 1 ],
			},
			camera: {
				eye: {
					x: 0,
					y: -1.25,
					z: 1.25,
				},
			},
		},
	};

	const graphConfig = {
		responsive: true
	};

	const graphPromise = Plotly.react(graphElement, graphData, graphLayout, graphConfig);
	graphPromise.then(() => applyBusyState(graphElement, false));
	promises.push(graphPromise);

	return Promise.all(promises);
}

/**
 * @param {string} rawData
 *
 * @returns {string}
 */
function filterRawData(rawData) {
	rawData = rawData.split('\n');

	if (rawData[0].trim().match(/^0\s+[\s\d]+\d$/)) {
		rawData.shift();
	}

	for (const i in rawData) {
		rawData[i] = rawData[i]
			.trim()
			.replace(/< \d+:\d+:\d+(\s+(AM|PM))?:/g, '')
			.replace(/[\[\]]/g, ' ')
			.replace(/\s+/g, '\t')
			.split('\t')
			.map((point) => parseFloat(point));

		if (rawData[i][0] == i) {
			rawData[i].shift();
		}
	}

	return rawData;
}

/**
 * Determines whether a Web Storage API is both supported and available.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Web_Storage_API/Using_the_Web_Storage_API#Feature-detecting_localStorage
 *
 * @param  {Storage} storage
 * @return {boolean}
 */
function isStorageAvailable(storage) {
	try {
		const x = '__storage_test__';
		storage.setItem(x, x);
		storage.removeItem(x);
		return true;
	} catch (error) {
		return error instanceof DOMException && (
			// everything except Firefox
			error.code === 22 ||
			// Firefox
			error.code === 1014 ||
			// test name field too, because code might not be present
			// everything except Firefox
			error.name === 'QuotaExceededError' ||
			// Firefox
			error.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
			// acknowledge QuotaExceededError only if there's something already stored
			(storage && storage.length !== 0);
	}
}

/**
 * Determines whether the specified error is a storage quota exceeded.
 *
 * @param  {Error} error
 * @return {boolean}
 */
function isStorageQuotaExceeded(error) {
	var quotaExceeded = false;
	if (error) {
		if (error.code) {
			switch (error.code) {
				case 22:
					quotaExceeded = true;
					break;
				case 1014:
					// Firefox
					if (error.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
						quotaExceeded = true;
					}
					break;
			}
		} else if (error.number === -2147024882) {
			// Internet Explorer 8
			quotaExceeded = true;
		}
	}

	return quotaExceeded;
}

class MeshPanelElement extends HTMLElement {
	constructor() {
		super();

		this.#bindEventListeners();
		this.#mountButtons();
		this.#mountInput();
	}

	#bindEventListeners() {
		// debug && console.group(`[${APP}]`, 'MeshPanelElement.#bindEventListeners');

		const resetterElement = this.resetterElement;
		if (resetterElement) {
			// debug && console.log('Binding Resetter:', resetterElement);
			resetterElement.addEventListener(
				'click',
				(event) => this.reset(),
				{ capture: true }
			);
		}

		const submitterElement = this.submitterElement;
		if (submitterElement) {
			// debug && console.log('Binding Submitter:', submitterElement);
			submitterElement.addEventListener(
				'click',
				(event) => this.submit(),
				{ capture: true }
			);
		}

		// debug && console.groupEnd();
	}

	#filterInput(value) {
		return value.replace(/^[\r\n]+|[\r\n]+$/g, '');
	}

	#mountButtons() {
		// debug && console.group(`[${APP}]`, 'MeshPanelElement.#mountButtons');

		this.controlElements.forEach(
			(controlElement) => applyDisabledState(controlElement, false)
		);

		// debug && console.groupEnd();
	}

	#resetOutput() {
		Plotly.purge(this.graphElement);
		this.outputElements.forEach((outputElement) => outputElement.value = '');
	}

	async reset() {
		debug && console.group(`[${APP}]`, 'MeshPanelElement.reset');

		if (!this.#isIdle) {
			debug && (
				console.log('Not Idle'),
				console.groupEnd()
			);
			return;
		}

		this.#isBusy = true;

		const inputElement = this.inputElement;
		inputElement.value = '';

		if (store) {
			try {
				store.removeItem(inputElement.id);
			} catch (error) {
				console.warn(`[${APP}]`, 'Storage:', `Cannot remove value of [${inputElement.id}]:`, error);
			}
		}

		this.#resetOutput();

		this.#isBusy = false;

		debug && console.groupEnd();
	}

	async submit() {
		debug && console.group(`[${APP}]`, 'MeshPanelElement.submit');

		if (!this.#isIdle) {
			debug && (
				console.log('Not Idle'),
				console.groupEnd()
			);
			return;
		}

		this.#isBusy = true;

		const inputElement = this.inputElement;
		inputElement.value = this.#filterInput(inputElement.value);

		if (!inputElement.value) {
			this.#isBusy = false;
			debug && (
				console.log('No Value'),
				console.groupEnd()
			);
			return;
		}

		if (store) {
			try {
				store.setItem(inputElement.id, inputElement.value);
			} catch (error) {
				console.warn(`[${APP}]`, 'Storage:', `Cannot store value of [${inputElement.id}]:`, (
					isStorageQuotaExceeded(error)
					? 'Storage is full'
					: error
				));
			}
		}

		await this.#processInput();

		this.#isBusy = false;

		debug && console.groupEnd();
	}

	async #mountInput() {
		debug && console.group(`[${APP}]`, 'MeshPanelElement.#mountInput');

		if (!this.#isIdle) {
			debug && (
				console.log('Not Idle'),
				console.groupEnd()
			);
			return;
		}

		this.#isBusy = true;

		const inputElement = this.inputElement;

		if (!inputElement.value && store?.length) {
			try {
				const storeItem = store.getItem(inputElement.id);
				if (storeItem) {
					inputElement.value = this.#filterInput(storeItem);
				}
			} catch (error) {
				console.warn(`[${APP}]`, 'Storage:', `Cannot retrieve value of [${inputElement.id}]:`, error);
			}
		}

		if (inputElement.value) {
			await this.#processInput();
		}

		this.#isBusy = false;

		debug && console.groupEnd();
	}

	async #processInput() {
		debug && console.log(`[${APP}]`, 'MeshPanelElement.#processInput');
		return await drawGraph(
			this.graphElement,
			this.statsElement,
			filterRawData(this.inputElement.value)
		);
	}

	get controlElements() {
		return this.querySelectorAll('[data-control]');
	}

	get graphElement() {
		return this.querySelector('.c-graph');
	}

	get inputElement() {
		return this.querySelector('textarea');
	}

	/**
	 * @returns {boolean}
	 */
	get isBusy() {
		if (typeof this.ariaBusy === 'boolean') {
			return this.ariaBusy;
		}

		return this.getAttribute('aria-busy') === 'true';
	}

	/**
	 * @returns {boolean}
	 */
	get isEnabled() {
		return !this.#isDisabled();
	}

	get outputElements() {
		return this.querySelectorAll('output');
	}

	get resetterElement() {
		return this.querySelector('[data-control="reset"]');
	}

	get statsElement() {
		return this.querySelector('.c-stats');
	}

	get submitterElement() {
		return this.querySelector('[data-control="visualize"]');
	}

	/**
	 * @returns {boolean}
	 */
	get #isDisabled() {
		if (typeof this.ariaDisabled === 'boolean') {
			return this.ariaDisabled;
		}

		return this.getAttribute('aria-disabled') === 'true';
	}

	/**
	 * @returns {boolean}
	 */
	get #isIdle() {
		return (!this.isBusy && !this.#isDisabled);
	}

	/**
	 * @param {boolean} state
	 */
	set #isBusy(state) {
		applyBusyState(this, state);
		this.inputElement.readOnly = state;
	}
}

customElements.define('c-panel', MeshPanelElement);

class MeshActionsElement extends HTMLElement {
	constructor() {
		super();

		this.#bindEventListeners();
		this.#mountButtons();
	}

	addPanel() {
		debug && console.group(`[${APP}]`, 'MeshActionsElement.resetAll');

		const container = document.querySelector('#mesh-panel-container');
		if (!container) {
			console.warn(`[${APP}]`, 'Cannot add panel:', 'Missing container');

			applyDisabledState(this.adderElement);

			debug && console.groupEnd();
			return;
		}

		const template = document.querySelector('#mesh-panel-template');
		if (!template) {
			console.warn(`[${APP}]`, 'Cannot add panel:', 'Missing template');

			applyDisabledState(this.adderElement);

			debug && console.groupEnd();
			return;
		}

		const clone = template.content.cloneNode(true);

		clone.childNodes.forEach((child) => {
			child.innerHTML = child.innerHTML
				.replace('{$index}', container.childElementCount)
				.replace('{$iteration}', (container.childElementCount + 1))
				.replace('{$data}', '');
		});
		container.appendChild(clone);

		const counter = document.querySelector('#mesh-panel-count');
		if (counter) {
			counter.name = (Number.parseInt(counter.name) + 1);
		}

		debug && console.groupEnd();
	}

	#bindEventListeners() {
		// debug && console.group(`[${APP}]`, 'MeshActionsElement.#bindEventListeners');

		const resetterElement = this.resetterElement;
		if (resetterElement) {
			// debug && console.log('Binding Resetter:', resetterElement);
			resetterElement.addEventListener(
				'click',
				(event) => this.resetAll(),
				{ capture: true }
			);
		}

		const submitterElement = this.submitterElement;
		if (submitterElement) {
			// debug && console.log('Binding Submitter:', submitterElement);
			submitterElement.addEventListener(
				'click',
				(event) => this.submitAll(),
				{ capture: true }
			);
		}

		const adderElement = this.adderElement;
		if (adderElement) {
			// debug && console.log('Binding Adder:', adderElement);
			adderElement.addEventListener(
				'click',
				(event) => {
					event.preventDefault();
					this.addPanel();
				},
				{ capture: true }
			);
		}

		// debug && console.groupEnd();
	}

	#mountButtons() {
		// debug && console.group(`[${APP}]`, 'MeshActionsElement.#mountButtons');

		this.controlElements.forEach(
			(controlElement) => applyDisabledState(controlElement, false)
		);

		// debug && console.groupEnd();
	}

	async resetAll() {
		debug && console.group(`[${APP}]`, 'MeshActionsElement.resetAll');

		document.querySelectorAll('c-panel').forEach((el) => el.reset());

		debug && console.groupEnd();
	}

	async submitAll() {
		debug && console.group(`[${APP}]`, 'MeshActionsElement.submitAll');

		document.querySelectorAll('c-panel').forEach((el) => el.submit());

		debug && console.groupEnd();
	}

	get controlElements() {
		return this.querySelectorAll('[data-control]');
	}

	get adderElement() {
		return this.querySelector('[data-control="add"]');
	}

	get resetterElement() {
		return this.querySelector('[data-control="reset"]');
	}

	get submitterElement() {
		return this.querySelector('[data-control="visualize"]');
	}
}

customElements.define('c-actions', MeshActionsElement);
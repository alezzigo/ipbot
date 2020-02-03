let frameName;
let frameMethod;
let frameSelector;
let method;
var processLogout = function() {
	api.setRequestParameters({
		action: 'logout',
		table: 'users',
		url: apiRequestParameters.current.settings.baseUrl + 'api/users'
	});
	api.sendRequest(function(response) {
		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}
	});
};
const processMethodForm = function(element) {
	const processName = element.hasAttribute('process') ? element.getAttribute('process') : '';
	frameName = element.hasAttribute('frame') ? element.getAttribute('frame') : '';
	frameSelector = '.frame-container[frame="' + frameName + '"]';

	if (
		typeof apiRequestParameters.current.defaults !== 'undefined' &&
		apiRequestParameters.current.action !== 'search' &&
		(
			element.classList.contains('close') ||
			(
				!element.hasAttribute('item_function') &&
				!element.classList.contains('submit')
			)
		)
	) {
		closeFrames(apiRequestParameters.current.defaults);
	}

	if (element.classList.contains('submit')) {
		let frameData = {};
		elements.loop(frameSelector + ' input, ' + frameSelector + ' select, ' + frameSelector + ' textarea', function(index, element) {
			frameData[element.getAttribute('name')] = element.value;
		});
		elements.loop(frameSelector + ' .checkbox', function(index, element) {
			frameData[element.getAttribute('name')] = +element.getAttribute('checked');
		});
		elements.loop(frameSelector + ' input[type="radio"]:checked', function(index, element) {
			frameData[element.getAttribute('name')] = element.value;
		});
		api.setRequestParameters({
			action: frameName,
			data: frameData
		}, true);
	} else if (frameName) {
		openFrame(frameName, frameSelector);
	}

	method = 'process' + capitalizeString(processName);

	if (typeof window[method] === 'function') {
		window[method](frameName, frameSelector);
	}

	processWindowEvents('resize');
};
onLoad(function() {
	selectAllElements('.frame-container', function(selectedElementKey, selectedElement) {
		frameSelector = '.frame-container[frame="' + selectedElement.getAttribute('frame') + '"]';
		selectAllElements(frameSelector + ' input[type="password"], ' + frameSelector + ' input[type="number"], ' + frameSelector + ' input[type="text"]', function(selectedElementKey, selectedElement) {
			selectedElement.removeEventListener('keydown', selectedElement.keydownListener);
			selectedElement.keydownListener = function() {
				if (event.key == 'Enter') {
					let submitButton = elements.get(frameSelector + ' .button.submit');

					if (submitButton) {
						processMethodForm(submitButton);
					}
				}
			};
			selectedElement.addEventListener('keydown', selectedElement.keydownListener);
		});
	});
	selectAllElements('.frame .button.close', function(selectedElementKey, selectedElement) {
		selectedElement.addEventListener('click', function() {
			closeFrames(apiRequestParameters.current.defaults);
		});
	});
	selectAllElements('.frame .checkbox, .frame label.custom-checkbox-label', function(selectedElementKey, selectedElement) {
		selectedElement.addEventListener('click', function(element) {
			let hiddenFieldSelector = 'div[field="' + element.target.getAttribute('name') + '"]';
			let itemSelector = '.checkbox[name="' + element.target.getAttribute('name') + '"]';

			if (elements.get(hiddenFieldSelector)) {
				if (elements.hasClass(hiddenFieldSelector, 'hidden')) {
					elements.removeClass(hiddenFieldSelector, 'hidden');
				} else {
					elements.addClass(hiddenFieldSelector, 'hidden');
				}
			}

			elements.setAttribute(itemSelector, 'checked', +!+elements.getAttribute(itemSelector, 'checked'));

			if (elements.getAttribute(itemSelector, 'toggle-display')) {
				let toggleSelector = '.' + elements.getAttribute(itemSelector, 'toggle-display');

				if (elements.get(toggleSelector)) {
					+item.getAttribute('checked') ? elements.removeClass(toggleSelector, 'hidden') : elements.addClass(toggleSelector, 'hidden');
				}
			}
		});
	});
	selectAllElements('.button.frame-button, .frame .button.submit', function(selectedElementKey, selectedElement) {
		selectedElement.addEventListener('click', function(element) {
			processMethodForm(element.target);
		});
	});
	window.onresize = function() {
		processWindowEvents('resize');
	};
	window.onscroll = function() {
		processWindowEvents('scroll');
	};

	if (elements.get('main').hasAttribute('process')) {
		method = 'process' + capitalizeString(elements.get('main').getAttribute('process'));
	}

	if (window.location.hash) {
		frameName = window.location.hash.substr(1).toLowerCase();
		frameSelector = '.frame-container[frame="' + frameName + '"]';

		if (elements.get(frameSelector)) {
			closeFrames(apiRequestParameters.current.defaults);
			frameMethod = 'process' + capitalizeString(frameName);
			openFrame(frameName, frameSelector);

			if (typeof window[frameMethod] === 'function') {
				method = frameMethod;
			}
		}
	}

	if (
		method &&
		typeof window[method] === 'function'
	) {
		setTimeout(function() {
			window[method](frameName, frameSelector);
		}, 100);
	}
});

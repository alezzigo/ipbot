let frameName;
let frameMethod;
let frameSelector;
let method;
const processLogout = function() {
	api.setRequestParameters({
		action: 'logout',
		table: 'users',
		url: apiRequestParameters.current.settings.base_url + 'api/users'
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
			!element.classList.contains('submit')
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

		if (frameName == 'search') {
			itemGrid = [];
			itemGridCount = 0;
		}
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
	if ((scrollableElements = selectAllElements('.scrollable')).length) {
		scrollableElements.map(function(element) {
			let event = function() {
				const elementContainerDetails = element[1].parentNode.getBoundingClientRect();

				if (elementContainerDetails.width) {
					element[1].parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element[1].querySelector('.item-header').clientHeight + 1) + 'px');
					element[1].setAttribute('style', 'width: ' + elementContainerDetails.width + 'px;');
				}

				element[1].setAttribute('scrolling', +(window.pageYOffset > (elementContainerDetails.top + window.pageYOffset)));
			};
			windowEvents.resize.push(event);
			windowEvents.scroll.push(event);
		});
	}

	selectAllElements('.frame-container').map(function(element) {
		frameSelector = '.frame-container[frame="' + element[1].getAttribute('frame') + '"]';
		selectAllElements(frameSelector + ' input[type="password"], ' + frameSelector + ' input[type="number"], ' + frameSelector + ' input[type="text"]').map(function(element) {
			element[1].removeEventListener('keydown', element[1].keydownListener);
			element[1].keydownListener = function() {
				if (event.key == 'Enter') {
					let submitButton = document.querySelector(frameSelector + ' .button.submit');

					if (submitButton) {
						processMethodForm(submitButton);
					}
				}
			};
			element[1].addEventListener('keydown', element[1].keydownListener);
		});
	});
	selectAllElements('.frame .button.close').map(function(element) {
		element[1].addEventListener('click', function(element) {
			closeFrames(apiRequestParameters.current.defaults);
		});
	});
	selectAllElements('.frame .checkbox, .frame label.custom-checkbox-label').map(function(element) {
		element[1].addEventListener('click', function(element) {
			let hiddenField = document.querySelector('div[field="' + element.target.getAttribute('name') + '"]');
			let item = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]');
			hiddenField ? (hiddenField.classList.contains('hidden') ? hiddenField.classList.remove('hidden') : hiddenField.classList.add('hidden')) : null;
			item.setAttribute('checked', +!+item.getAttribute('checked'));

			if (item.hasAttribute('toggle-display')) {
				let toggleSelector = '.' + item.getAttribute('toggle-display');
				let toggleElement = document.querySelector(toggleSelector);

				if (toggleElement) {
					+item.getAttribute('checked') ? elements.removeClass(toggleSelector, 'hidden') : elements.addClass(toggleSelector, 'hidden');
				}
			}
		});
	});
	selectAllElements('.button.frame-button, .frame .button.submit').map(function(element) {
		element[1].addEventListener('click', function(element) {
			processMethodForm(element.target);
		});
	});

	window.onresize = function() {
		processWindowEvents('resize');
	};
	window.onscroll = function() {
		processWindowEvents('scroll');
	};

	if (document.querySelector('main[process]')) {
		method = 'process' + capitalizeString(document.querySelector('main[process]').getAttribute('process'));
	}

	if (window.location.hash) {
		frameName = window.location.hash.substr(1).toLowerCase();
		frameSelector = '.frame-container[frame="' + frameName + '"]';

		if (document.querySelector(frameSelector)) {
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

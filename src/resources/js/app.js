'use_strict';

var frameName;
var frameMethod;
var frameSelector;
var method;
var processLogout = function() {
	requestParameters.table = 'users';
	requestParameters.action = 'logout';
	requestParameters.url = '/api/users';
	sendRequest(function(response) {
		if (
			typeof response.redirect === 'string' &&
			response.redirect
		) {
			window.location.href = response.redirect;
			return false;
		}
	});
};
var processMethod = function(method, frameName, frameSelector) {
	window[method](frameName, frameSelector);
};
var processMethodForm = function(element) {
	var processName = element.hasAttribute('process') ? element.getAttribute('process') : '';
	frameName = element.hasAttribute('frame') ? element.getAttribute('frame') : '';
	frameSelector = '.frame-container[frame="' + frameName + '"]';

	if (element.classList.contains('close')) {
		closeFrames(defaultTable);
	}

	if (element.classList.contains('submit')) {
		elements.loop(frameSelector + ' input, ' + frameSelector + ' select, ' + frameSelector + ' textarea', function(index, element) {
			requestParameters.data[element.getAttribute('name')] = element.value;
		});
		elements.loop(frameSelector + ' .checkbox', function(index, element) {
			requestParameters.data[element.getAttribute('name')] = +element.getAttribute('checked');
		});
		elements.loop(frameSelector + ' input[type="radio"]:checked', function(index, element) {
			requestParameters.data[element.getAttribute('name')] = element.value;
		});
		previousAction = requestParameters.action;
		requestParameters.action = frameName;

		if (frameName == 'search') {
			itemGrid = [];
			itemGridCount = 0;
		}
	} else if (frameName) {
		openFrame(frameName, frameSelector);
	}

	method = 'process' + capitalizeString(processName);

	if (typeof window[method] === 'function') {
		processMethod(method, frameName, frameSelector);
	}

	processWindowEvents('resize');
};
onLoad(function() {
	if ((scrollableElements = selectAllElements('.scrollable')).length) {
		scrollableElements.map(function(element) {
			var event = function() {
				var elementContainerDetails = element[1].parentNode.getBoundingClientRect();

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
		var frameSelector = '.frame-container[frame="' + element[1].getAttribute('frame') + '"]';
		selectAllElements(frameSelector + ' input[type="text"], ' + frameSelector + ' input[type="password"], ' + frameSelector + ' textarea').map(function(element) {
			element[1].removeEventListener('keydown', element[1].keydownListener);
			element[1].keydownListener = function() {
				if (event.key == 'Enter') {
					var submitButton = document.querySelector(frameSelector + ' .button.submit');

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
			closeFrames(defaultTable);
		});
	});
	selectAllElements('.frame .checkbox, .frame label.custom-checkbox-label').map(function(element) {
		element[1].addEventListener('click', function(element) {
			var hiddenField = document.querySelector('div[field="' + element.target.getAttribute('name') + '"]');
			var item = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]');
			hiddenField ? (hiddenField.classList.contains('hidden') ? hiddenField.classList.remove('hidden') : hiddenField.classList.add('hidden')) : null;
			item.setAttribute('checked', +!+item.getAttribute('checked'));

			if (item.hasAttribute('toggle-display')) {
				var toggleSelector = '.' + item.getAttribute('toggle-display');
				var toggleElement = document.querySelector(toggleSelector);

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
		frameName = replaceCharacter(window.location.hash, 0, '').toLowerCase();
		frameSelector = '.frame-container[frame="' + frameName + '"]';

		if (document.querySelector(frameSelector)) {
			closeFrames(defaultTable);
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
			processMethod(method, frameName, frameSelector);
		}, 100);
	}
});

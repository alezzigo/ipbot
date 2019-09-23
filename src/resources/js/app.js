'use_strict';

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
onLoad(function() {
	var method;

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

	selectAllElements('.window .button.close').map(function(element) {
		element[1].addEventListener('click', function(element) {
			closeWindows(defaultTable);
		});
	});
	selectAllElements('.window .checkbox, .window label.custom-checkbox-label').map(function(element) {
		element[1].addEventListener('click', function(element) {
			var hiddenField = document.querySelector('div[field="' + element.target.getAttribute('name') + '"]');
			var item = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]');
			hiddenField ? (hiddenField.classList.contains('hidden') ? hiddenField.classList.remove('hidden') : hiddenField.classList.add('hidden')) : null;
			item.setAttribute('checked', +!+item.getAttribute('checked'));
		});
	});
	selectAllElements('.button.window-button, .window .button.submit').map(function(element) {
		element[1].addEventListener('click', function(element) {
			var processName = element.target.hasAttribute('process') ? element.target.getAttribute('process') : '';
			var windowName = element.target.hasAttribute('window') ? element.target.getAttribute('window') : '';
			var windowSelector = '.window-container[window="' + windowName + '"]';

			if (element.target.classList.contains('submit')) {
				elements.loop(windowSelector + ' input, ' + windowSelector + ' select, ' + windowSelector + ' textarea', function(index, element) {
					requestParameters.data[element.getAttribute('name')] = element.value;
				});
				elements.loop(windowSelector + ' .checkbox', function(index, element) {
					requestParameters.data[element.getAttribute('name')] = +element.getAttribute('checked');
				});
				elements.loop(windowSelector + ' input[type="radio"]:checked', function(index, element) {
					requestParameters.data[element.getAttribute('name')] = element.value;
				});
				previousAction = requestParameters.action;
				requestParameters.action = windowName;

				if (windowName == 'search') {
					itemGrid = [];
					itemGridCount = 0;
				}
			} else if (windowName) {
				closeWindows(defaultTable);
				openWindow(windowName, windowSelector);
			}

			var method = 'process' + capitalizeString(processName);

			if (typeof window[method] === 'function') {
				window[method](windowName, windowSelector);
			}

			processWindowEvents('resize');
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
		var windowMethod;
		var windowName = replaceCharacter(window.location.hash, 0, '').toLowerCase();
		var windowSelector = '.window-container[window="' + windowName + '"]';

		if (document.querySelector(windowSelector)) {
			closeWindows(defaultTable);
			openWindow(windowName, windowSelector);
			windowMethod = 'process' + capitalizeString(windowName);

			if (typeof window[windowMethod] === 'function') {
				method = windowMethod;
			}
		}
	}

	if (
		method &&
		typeof window[method] === 'function'
	) {
		setTimeout(function() {
			window[method]();
		}, 100);
	}
});

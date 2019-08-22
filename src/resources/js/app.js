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
	if ((scrollableElements = selectAllElements('.scrollable')).length) {
		scrollableElements.map(function(element) {
			var scrollEvent = function() {
				var elementContainerDetails = element[1].parentNode.getBoundingClientRect();

				if (elementContainerDetails.width) {
					element[1].parentNode.querySelector('.item-body').setAttribute('style', 'padding-top: ' + (element[1].querySelector('.item-header').clientHeight + 1) + 'px');
					element[1].setAttribute('style', 'max-width: ' + elementContainerDetails.width + 'px;');
				}

				element[1].setAttribute('scrolling', +(window.pageYOffset > (elementContainerDetails.top + window.pageYOffset)));
			};
			windowEvents.resize.push(scrollEvent);
			windowEvents.scroll.push(scrollEvent);
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
			var method = 'process' + capitalizeString(processName);
			var windowName = element.target.hasAttribute('window') ? element.target.getAttribute('window') : '';
			var windowSelector = '.window-container[window="' + windowName + '"]';

			if (element.target.classList.contains('submit')) {
				elements.loop(windowSelector + ' input, ' + windowSelector + ' select, ' + windowSelector + ' textarea', function(index, element) {
					requestParameters.data[element.getAttribute('name')] = element.value;
				});
				elements.loop(windowSelector + ' .checkbox', function(index, element) {
					requestParameters.data[element.getAttribute('name')] = +element.getAttribute('checked');
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

			if (typeof window[method] === 'function') {
				window[method](windowName, windowSelector);
			}
		});
	});
	processWindowEvents(windowEvents);

	if (window.location.hash) {
		var windowName = replaceCharacter(window.location.hash, 0, '').toLowerCase();
		var windowSelector = '.window-container[window="' + windowName + '"]';

		if (document.querySelector(windowSelector)) {
			closeWindows(defaultTable);
			openWindow(windowName, windowSelector);
		}
	}
});

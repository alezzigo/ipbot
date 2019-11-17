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
			var frameName = element.target.hasAttribute('frame') ? element.target.getAttribute('frame') : '';
			var frameSelector = '.frame-container[frame="' + frameName + '"]';
			var processName = element.target.hasAttribute('process') ? element.target.getAttribute('process') : '';

			if (element.target.classList.contains('submit')) {
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
				closeFrames(defaultTable);
				openFrame(frameName, frameSelector);
			}

			var method = 'process' + capitalizeString(processName);

			if (typeof window[method] === 'function') {
				window[method](frameName, frameSelector);
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
		var frameMethod;
		var frameName = replaceCharacter(window.location.hash, 0, '').toLowerCase();
		var frameSelector = '.frame-container[frame="' + frameName + '"]';

		if (document.querySelector(frameSelector)) {
			closeFrames(defaultTable);
			openFrame(frameName, frameSelector);
			frameMethod = 'process' + capitalizeString(frameName);

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
			window[method]();
		}, 100);
	}
});

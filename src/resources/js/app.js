'use_strict';

onLoad(() => {
	if ((scrollableElements = selectAllElements('.scrollable')).length) {
		scrollableElements.map((element) => {
			var scrollEvent = () => {
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

	selectAllElements('.window .button.close').map((element) => {
		element[1].addEventListener('click', (element) => {
			closeWindows(defaultTable);
		});
	});
	selectAllElements('.window .checkbox, .window label.custom-checkbox-label').map((element) => {
		element[1].addEventListener('click', (element) => {
			var hiddenField = document.querySelector('div[field="' + element.target.getAttribute('name') + '"]'),
				item = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]');
			item.setAttribute('checked', +!+item.getAttribute('checked'));
			hiddenField ? (hiddenField.classList.contains('hidden') ? hiddenField.classList.remove('hidden') : hiddenField.classList.add('hidden')) : null;
		});
	});
	selectAllElements('.button.window, .window .button.submit').map((element) => {
		element[1].addEventListener('click', (element) => {
			var processName = element.target.hasAttribute('process') ? element.target.getAttribute('process') : '';
			var windowName = element.target.getAttribute('window');
			var windowSelector = '.window-container[window="' + windowName + '"]';
			var method = 'process' + capitalizeString(processName);

			if (element.target.classList.contains('submit')) {
				elements.loop(windowSelector + ' input, ' + windowSelector + ' select, ' + windowSelector + ' textarea', (index, element) => {
					requestParameters.data[element.getAttribute('name')] = element.value;
				});
				elements.loop(windowSelector + ' .checkbox', (index, element) => {
					requestParameters.data[element.getAttribute('name')] = +element.getAttribute('checked');
				});
				requestParameters.action = windowName;

				if (windowName == 'search') {
					itemGrid = [];
					itemGridCount = 0;
				}
			} else {
				openWindow(windowSelector);
			}

			if (typeof window[method] === 'function') {
				window[method](windowName, windowSelector);
			}
		});
	});
	processWindowEvents(windowEvents);
});

'use_strict';
var elements = {
	addClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.add(className);
		});
	},
	loop: (selector, callback) => {
		selectAllElements(selector).map((element) => {
			callback(element[0], element[1]);
		});
	},
	removeClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.remove(className);
		});
	},
	html: (selector, value = null) => {
		return selectAllElements(selector).map((element) => {
			return value !== null ? element[1].innerHTML = value : element[1].innerHTML;
		})[0];
	},
	setAttribute: (selector, attribute, value) => {
		selectAllElements(selector).map((element) => {
			element[1].setAttribute(attribute, value);
		});
	}
};

var onLoad = (callback) => {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 1) : callback();
};

var processPagination = (currentPage, pagination) => {
	var checkboxAll = document.querySelector('.checkbox.all'),
		items = document.querySelector('.proxy-configuration .proxy-table'),
		resultsPerPage = +pagination.getAttribute('results');

	var toggle = (checkbox) => {
		var index = checkbox.target.getAttribute('index');
		items.setAttribute('current_checked', index);
		processChecked((window.event.shiftKey ? range(items.getAttribute('previous_checked'), index) : [index]), document.querySelector('.checkbox[index="' + (window.event.shiftKey ? items.getAttribute('previous_checked') : index) + '"]').hasAttribute('checked'));
		items.setAttribute('previous_checked', index);
		toggleAll();
	}
	var toggleAll = (checkbox = null) => {
		if (!checkbox) {
			if (checkboxField = document.querySelector('input[name="checked[]"][group="' + Math.ceil((currentPage * +document.querySelector('.pagination').getAttribute('results')) / 1000) + '"]')) {
				(checkboxField ? checkboxField.value.split(',') : []).map((checkedItem, checkedItemIndex) => {
					var checkedItem = document.querySelector('.checkbox[proxy_id="' + checkedItem + '"]');
					checkedItem ? checkedItem.setAttribute('checked', 'checked') : null;
				});
			}

			return (selectAllElements('.proxy-configuration tr').length === selectAllElements('.proxy-configuration tr .checkbox[checked]').length ? checkboxAll.setAttribute('checked', 'checked') : checkboxAll.removeAttribute('checked'));
		}

		checkbox.target.hasAttribute('checked') ? checkbox.target.removeAttribute('checked') : checkbox.target.setAttribute('checked', null);
		processChecked(selectAllElements('.proxy-configuration tr .checkbox').map((checkbox) => {
			return checkbox[1].getAttribute('index');
		}), checkbox.target.hasAttribute('checked'), true);
	};

	pagination.querySelector('.next').setAttribute('page', 0);
	pagination.querySelector('.previous').setAttribute('page', 0);
	items.innerHTML = '<p>Loading ...</p>';
	var xhr = new XMLHttpRequest();
	var request = {
		action: 'find',
		conditions: {
			order_id: document.querySelector('input[name="order_id"]').value
		},
		fields: [
			'id',
			'order_id',
			'ip',
			'http_port',
			'asn',
			'isp',
			'city',
			'region',
			'country_name',
			'country_code',
			'timezone',
			'whitelisted_ips',
			'username',
			'password',
			'next_replacement_available',
			'replacement_removal_date',
			'status'
		],
		group: 'proxies',
		limit: resultsPerPage,
		offset: ((currentPage * resultsPerPage) - resultsPerPage),
		order: 'modified DESC'
	};
	xhr.open('POST', '/src/php/views/api.php', true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send('json=' + JSON.stringify(request));
	xhr.onload = function(response) {
		if (response.target.status === 200) {
			var response = JSON.parse(response.target.response);
			items.innerHTML = '<table class="table"></table>';
			response.data.map((proxy, index) => {
				items.querySelector('table').innerHTML += '<tr page="' + currentPage + '" proxy_id="' + proxy.id + '" class=""><td style="width: 1px;"><span class="checkbox" index="' + index + '" proxy_id="' + proxy.id + '"></span></td><td><span class="details-container"><span class="details">' + proxy.status + ' Proxy IP ' + proxy.ip + ' Location ' + proxy.city + ', ' + proxy.region + ' ' + proxy.country_code + ' <span class="icon-container"><img src="../../resources/images/icons/flags/' + proxy.country_code.toLowerCase() + '.png" class="flag" alt="' + proxy.country_code + ' flag"></span> ISP ' + proxy.asn + ' Timezone ' + proxy.timezone + ' HTTP + HTTPS Port ' + (proxy.disable_http == 1 ? 'Disabled' : '80') + ' Whitelisted IPs ' + (proxy.whitelisted_ips ? '<textarea>' + proxy.whitelisted_ips + '</textarea>' : 'N/A') + ' Username ' + (proxy.username ? proxy.username : 'N/A') + ' Password ' + (proxy.password ? proxy.password : 'N/A') + '</span></span><span class="table-text">' + proxy.ip + '</span></td>';
			});
			elements.html('.total-results', response.count);
			elements.html('.first-result', currentPage === 1 ? currentPage : ((currentPage * resultsPerPage) - resultsPerPage) + 1);
			elements.html('.last-result', (lastResult = currentPage * resultsPerPage) >= response.count ? response.count : lastResult);
			pagination.setAttribute('current', currentPage);
			pagination.querySelector('.next').setAttribute('page', +elements.html('.last-result') < response.count ? currentPage + 1 : 0);
			pagination.querySelector('.previous').setAttribute('page', currentPage <= 0 ? 0 : currentPage - 1);
			elements.loop('.proxy-configuration tr', (index, row) => {
				var checkbox = row.querySelector('.checkbox');
				checkbox.removeEventListener('click', checkbox.listener);
				checkbox.listener = toggle;
				checkbox.addEventListener('click', toggle);
			});
			checkboxAll.removeEventListener('click', checkboxAll.listener);
			checkboxAll.listener = toggleAll;
			checkboxAll.addEventListener('click', toggleAll);
			toggleAll();
		}
	}
};

var processChecked = (checkboxes, checkboxState, all = false) => {
	var currentPage = +document.querySelector('.pagination').getAttribute('current');
	var group = Math.ceil((currentPage * +document.querySelector('.pagination').getAttribute('results')) / 1000);
	var checkboxField = document.querySelector('input[name="checked[]"][group="' + group + '"]');
	var checkedItems = checkboxField ? checkboxField.value.split(',') : [];
	checkboxes.map((checkbox, checkboxIndex) => {
		var checkboxElement = document.querySelector('.checkbox[index="' + checkbox + '"]');
		var isChecked = ((checkboxes.length > 1 || all) && checkboxState) || ((checkboxes.length === 1 && !all) && !checkboxState) ? +Boolean(checkboxElement.setAttribute('checked', 'checked')) + 1 : +Boolean(checkboxElement.removeAttribute('checked') + 0);
		var proxyId = checkboxElement.getAttribute('proxy_id');
		checkedItems.indexOf(proxyId) >= 0 ? checkedItems.splice(checkedItems.indexOf(proxyId), 1) : null;
		isChecked ? checkedItems.push(proxyId) : null;
	});

	if (checkboxField === null) {
		checkboxField = document.createElement('input');
			checkboxField.setAttribute('group', group);
			checkboxField.setAttribute('name', 'checked[]');
			checkboxField.setAttribute('type', 'hidden');
		document.querySelector('.checked-items').prepend(checkboxField);
	}

	checkboxField.value = checkedItems.filter(checkedItem => checkedItem.length);
	elements.html('.total-checked', 0);
	elements.loop('input[name="checked[]"]', function(checkboxFieldIndex, checkboxField) {
		elements.html('.total-checked', +elements.html('.total-checked') + (+Boolean(checkboxField.value.length) + (checkboxField.value.match(/,/g) || []).length));
	});
	elements.html('.total-checked') ? elements.removeClass('span.icon[proxy-function]', 'hidden') : elements.addClass('span.icon[proxy-function]', 'hidden');
};

var range = (low, high, step = 1) => {
	var array = [],
		high = +high,
		low = +low;

	if (low < high) {
		while (low <= high) {
			array.push(low);
			low += step;
		}
	} else {
		while (low >= high) {
			array.push(low);
			low -= step;
		}
	}

	return array;
}

var selectAllElements = (selector) => {
	return Object.entries(document.querySelectorAll(selector));
};

var unique = (value, index, self) => {
	return self.indexOf(value) === index;
};

String.prototype.trim = (charlist) => {
	return this.replace(new RegExp("[" + charlist + "]+$"), "").replace(new RegExp("^[" + charlist + "]+"), "");
};

if (
	(
		typeof Element.prototype.addEventListener === 'undefined' ||
		typeof Element.prototype.removeEventListener === 'undefined'
	) &&
	(this.attachEvent && this.detachEvent)
) {
	Element.prototype.addEventListener = function (event, callback) {
		event = 'on' + event;
		return this.attachEvent(event, callback);
	};

	Element.prototype.removeEventListener = function (event, callback) {
		event = 'on' + event;
		return this.detachEvent(event, callback);
	};
}

onLoad(() => {
	elements.removeClass('.proxy-configuration', 'hidden');
	elements.addClass('.loading', 'hidden');

	if (pagination = document.querySelector('.pagination')) {
		processPagination(+pagination.getAttribute('current'), pagination)
		selectAllElements('.pagination .button').map((element) => {
			element[1].addEventListener('click', (element) => {
				if ((page = +element.target.getAttribute('page')) > 0) {
					processPagination(page, pagination);
				}
			});
		});
	}

	if ((scrollableElements = selectAllElements('.scrollable')).length) {
		scrollableElements.map((element) => {
			elementDetails = element[1].getBoundingClientRect();
			window.onscroll = () => {
				if (window.pageYOffset >= elementDetails.top) {
					element[1].classList.add('scrolling');
					element[1].setAttribute('style', 'max-width: ' + elementDetails.width + 'px;');
				} else {
					element[1].classList.remove('scrolling');
				}
			};
		});
	}

	selectAllElements('.button.window').map((element) => {
		element[1].addEventListener('click', (element) => {
			elements.removeClass('.window-container[window="' + element.target.getAttribute('window') + '"]', 'hidden');
			document.querySelector('input[name="configuration_action"]').value = element.target.getAttribute('window');
			document.querySelector('main').classList.add('hidden');
		});
	});
	selectAllElements('.window .button.close').map((element) => {
		element[1].addEventListener('click', (element) => {
			elements.loop('.window input', (index, input) => {
				input.value = '';
			});
			elements.addClass('.window-container', 'hidden');
			document.querySelector('main').classList.remove('hidden');
		});
	});
	selectAllElements('.window .button.submit').map((element) => {
		element[1].addEventListener('click', (element) => {
			// ...
			alert('Actions temporarily disabled to implement finding/saving via API.');
			return;
			// ...

			var form = '.window-container[window="' + element.target.getAttribute('form') + '"]';
			elements.loop(form + ' input, ' + form + ' select, ' + form + ' textarea', (index, element) => {
				var value = element.closest('.checkbox-option-container') && element.closest('.checkbox-option-container').classList.contains('hidden') ? '' : element.value;
				document.querySelector('input[name="' + element.getAttribute('name') + '"][type="hidden"]').value = value;
			});
			document.querySelector('.proxy-configuration form').submit();
		});
	});
	selectAllElements('.window .checkbox, .window label.custom-checkbox-label').map((element) => {
		element[1].addEventListener('click', (element) => {
			var	checkbox = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]'),
				hiddenField = document.querySelector('div[field="' + element.target.getAttribute('name') + '"]'),
				hiddenInput = document.querySelector('input[name="' + element.target.getAttribute('name') + '"][type="hidden"]');
			checkbox.hasAttribute('checked') ? checkbox.removeAttribute('checked') : checkbox.setAttribute('checked', 'checked');
			hiddenField ? (hiddenField.classList.contains('hidden') ? hiddenField.classList.remove('hidden') : hiddenField.classList.add('hidden')) : null;
			hiddenInput ? hiddenInput.value = +checkbox.hasAttribute('checked') : null;
		});
	});
	// ...
});

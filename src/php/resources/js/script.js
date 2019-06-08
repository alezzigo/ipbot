var elements = {
	addClass: (selector, className) => {
		selectAll(selector).map((element) => {
			element[1].classList.add(className);
		});
	},
	loop: (selector, callback) => {
		selectAll(selector).map((element) => {
			callback(element[0], element[1]);
		});
	},
	removeClass: (selector, className) => {
		selectAll(selector).map((element) => {
			element[1].classList.remove(className);
		});
	},
	setAttribute: (selector, attribute, value) => {
		selectAll(selector).map((element) => {
			element[1].setAttribute(attribute, value);
		});
	}
}

var onLoad = (callback) => {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 1) : callback();
}

var processPagination = (currentPage) => {
	elements.addClass('.proxy-table tbody tr:not(.hidden)', 'hidden');
	elements.removeClass('.proxy-configuration tbody tr[page="' + currentPage + '"]', 'hidden');
	elements.setAttribute('.proxy-configuration .pagination', 'current', currentPage);
	elements.setAttribute('.proxy-configuration .pagination .previous', 'previous', currentPage <= 0 ? 0 : currentPage - 1);
	elements.setAttribute('.proxy-configuration .pagination .next', 'next', currentPage + 1);
	elements.loop('.proxy-table tbody tr:not(.hidden)', (index, element) => {
		var proxyData = JSON.parse(element.getAttribute('data'));
		var proxyStatusDisplay = 'Next IP Replacement ' + proxyData.next_replacement_available_formatted + ' Auto Replacements ' + (proxyData.auto_replacement_interval_value == '0' ? 'Disabled' : 'Enabled') + '';

		if (proxyData.status.toLowerCase() == 'replaced') {
			proxyStatusDisplay = 'Time to Removal ' + proxyData.replacement_removal_date_formatted;
		}

		element.querySelector('.details-container').innerHTML = '<span class="details">' + proxyData.status + ' Proxy IP ' + proxyData.ip + ' Location ' + proxyData.city + ', ' + proxyData.region + ' ' + proxyData.country_code + ' <img src="../../resources/images/flags/' + proxyData.country_code.toLowerCase() + '.png" class="flag" alt="' + proxyData.country_code + ' flag"> ISP ' + proxyData.asn + ' Timezone ' + proxyData.timezone + ' ' + proxyStatusDisplay + ' HTTP + HTTPS Port ' + (proxyData.disable_http == 1 ? 'Disabled' : '80') + ' Whitelisted IPs ' + (proxyData.whitelisted_ips ? '<textarea>' + proxyData.whitelisted_ips + '</textarea>' : 'N/A') + ' Username ' + (proxyData.username ? proxyData.username : 'N/A') + ' Password ' + (proxyData.password ? proxyData.password : 'N/A') + '</span>';
	});
	// ...
};

var selectAll = (selector) => {
	return Object.entries(document.querySelectorAll(selector));
}

var unique = (value, index, self) => {
	return self.indexOf(value) === index;
}

String.prototype.trim = (charlist) => {
	return this.replace(new RegExp("[" + charlist + "]+$"), "").replace(new RegExp("^[" + charlist + "]+"), "");
};

onLoad(() => {
	elements.removeClass('.proxy-configuration', 'hidden');
	elements.addClass('.loading, .select-all', 'hidden');
	processPagination(0);
	// ...
});

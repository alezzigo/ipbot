var elements = {
	addClass: (selector, className) => {
		select(selector).map((element) => {
			element[1].classList.add(className);
		});
	},
	removeClass: (selector, className) => {
		select(selector).map((element) => {
			element[1].classList.remove(className);
		});
	},
	setAttribute: (selector, attribute, value) => {
		select(selector).map((element) => {
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
	// ...
};

var select = (selector) => {
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
	elements.addClass('.loading, .select-all-results', 'hidden');
	processPagination(0);
	// ...
});

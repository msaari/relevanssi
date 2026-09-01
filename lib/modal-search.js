(function () {
	'use strict';

	var lastTrigger = null;
	var stylesheetPromise = null;

	function loadStylesheet() {
		var settings = window.relevanssiModalSearchSettings || {};
		var existing = document.querySelector('link[href*="modal-search.css"]');

		if (existing || !settings.stylesheet) {
			return Promise.resolve();
		}

		if (stylesheetPromise) {
			return stylesheetPromise;
		}

		stylesheetPromise = new Promise(function (resolve) {
			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = settings.stylesheet;
			link.onload = resolve;
			link.onerror = resolve;
			document.head.appendChild(link);
		});

		return stylesheetPromise;
	}

	function getDialog(id) {
		var dialog = document.getElementById(id);
		return dialog && dialog.classList.contains('relevanssi-modal-search__dialog') ? dialog : null;
	}

	function dispatch(dialog, name) {
		dialog.dispatchEvent(new CustomEvent(name, { bubbles: true }));
	}

	function focusSearchField(dialog) {
		var field = dialog.querySelector('input[type="search"], input[name="s"], input[type="text"]');
		if (field) {
			field.focus();
		}
	}

	function open(id, trigger) {
		var dialog = getDialog(id);
		if (!dialog || dialog.open || dialog.getAttribute('data-relevanssi-modal-search-opening')) {
			return;
		}

		lastTrigger = trigger || document.activeElement;
		dialog.setAttribute('data-relevanssi-modal-search-opening', 'true');
		loadStylesheet().then(function () {
			dialog.removeAttribute('data-relevanssi-modal-search-opening');
			if (typeof dialog.showModal === 'function') {
				dialog.showModal();
			} else {
				dialog.setAttribute('open', '');
				dialog.setAttribute('role', 'dialog');
				dialog.setAttribute('aria-modal', 'true');
			}
			document.documentElement.classList.add('relevanssi-modal-search-is-open');
			focusSearchField(dialog);
			dispatch(dialog, 'relevanssi:modal-open');
		});
	}

	function close(dialog) {
		if (!dialog || !dialog.open) {
			return;
		}

		if (typeof dialog.close === 'function') {
			dialog.close();
		} else {
			dialog.removeAttribute('open');
			onClose(dialog);
		}
	}

	function onClose(dialog) {
		document.documentElement.classList.remove('relevanssi-modal-search-is-open');
		dispatch(dialog, 'relevanssi:modal-close');
		if (lastTrigger && typeof lastTrigger.focus === 'function') {
			lastTrigger.focus();
		}
		lastTrigger = null;
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-relevanssi-modal-search], a[href="#relevanssi-modal-search"]');
		var closeButton = event.target.closest('[data-relevanssi-modal-search-close]');
		var dialog;
		var id;

		if (trigger) {
			event.preventDefault();
			id = trigger.getAttribute('data-relevanssi-modal-search') || 'relevanssi-modal-search';
			open(id, trigger);
			return;
		}

		if (closeButton) {
			close(closeButton.closest('dialog'));
			return;
		}

		dialog = event.target.closest('dialog.relevanssi-modal-search__dialog');
		if (dialog && event.target === dialog) {
			close(dialog);
		}
	});

	document.addEventListener('close', function (event) {
		if (event.target.classList.contains('relevanssi-modal-search__dialog')) {
			onClose(event.target);
		}
	}, true);

	loadStylesheet();

	window.RelevanssiModalSearch = {
		open: function (id) {
			open(id);
		},
		close: function (id) {
			close(getDialog(id));
		}
	};
}());

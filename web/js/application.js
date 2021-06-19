const defaultTimeoutNotification = 1500;
const defaultPositionNotification = 'topRight';
const defaultThemeNotification = 'metroui';
const defaultClassesPrefix = 'askja-';
const isOk = 'ok';
const defaultApiController = 'engine/api.php';

var application = new Vue({
	el: '.' + defaultClassesPrefix + 'app',
	data: {
		themes: [],
		activeTheme: null,
		vitae: [],
		counters: {
			qualifiers: 0,
			half: 0,
			admins: 0,
			summaries: 0,
			all: 0
		}
	},
	methods: {
		remove: function(e) {
			var vId = e.target.dataset.vid;

			request('remove_statement_by_path', {data: {path: this.vitae[vId].link}}, (r) => {
				if (r.status === isOk) {
					if (r.body === true) {
						showNotification('Успешно удалили ведомость!', 'success');

						loadStatements();
					} else {
						showNotification(r.body, 'warning');
					}
				}
			});
		},
		download: function(e) {
			var vId = e.target.dataset.vid,
				obj = this.vitae[vId];

			downloadAsFile(obj.name, location.origin + obj.link.replace(/\.\./, ''));
		}
	}
});

function downloadAsFile(name, url) {
	let a = document.createElement("a");
	a.href = url;
	a.download = name;
	a.click();
}

function showNotification(textNotification, typeNotification = 'alert'){
	new Noty({
		text: textNotification,
		timeout: defaultTimeoutNotification,
		type: typeNotification,
		theme: defaultThemeNotification,
		layout: defaultPositionNotification
	}).show();
}

function initAllModals() {
	MicroModal.init();
}

function request(method, params, callback) {
	try {
		params.ts = (new Date()).getTime();
		params.action = method;

		$.ajax({
			url: defaultApiController,
			data: params,
			method: 'POST',
			success: function(response) {
				return (
				    response !== null && response !== "error" && typeof response === "string" ?
                        callback(JSON.parse(response)) :
                        showNotification("Ошибка выполнения запроса...", "error")
                );
			},
			error: function() {
				showNotification("Ошибка выполнения запроса...", "error");
			}
		});
	} catch(e) {
		showNotification('Error: ' + e.name + ":" + e.message + "<br/>" + e.stack, 'error');
	}
}

function bindMenu() {
	$(".menu-item").click(function () {
		toggleSection($(this));
	});
}

function toggleSection(item) {
	$("section").each(function() {
		if (item.data().hasOwnProperty('target')) {
			if (item.data('target') === $(this).attr('id')) {
				$(this).removeClass('invisible');
				$(this).addClass('visible');
			} else {
				$(this).removeClass('visible');
				$(this).addClass('invisible');
			}
		}
	});
}

$(document).ready(function() {
	initAllModals();

	bindMenu();

	loadStatements();

	jQuery(document).viewitle();
});

function loadStatements() {
	request('get_statements', {filter: getForm()}, (r) => {
		if (r.status === isOk) {
			application.counters.qualifiers = 0;
			application.counters.half = 0;
			application.counters.admins = 0;
			application.counters.summaries = 0;
			application.counters.all = 0;

			for (let vitae of r.body) {
				switch (getCaseByName(vitae.name)) {
					case 1:
						application.counters.all++;

						break;

					case 2:
						application.counters.half++;

						break;

					case 3:
						application.counters.summaries++;

						break;

					case 4:
						application.counters.admins++;

						break;

					case 5:
						application.counters.qualifiers++;

						break;
				}

				application.vitae.push(vitae);
			}

			showNotification('Успешно загрузили ведомости!', 'success');
		} else {
			showNotification('Ошибка загрузки ведомостей', 'error');
		}
	});
}

function getCaseByName(name) {
	return (
		name.indexOf('зачётная') !== -1 ? 1 :
		name.indexOf('семестровая') !== -1 ? 2 :
		name.indexOf('сводная') !== -1 ? 3 :
		name.indexOf('куратора') !== -1 ? 4 :
		name.indexOf('квалификационная') !== -1 ? 5 : 1
	);
}

$(document).keyup(function(e) {
	let sel = $(".reports-list");

	switch (e.keyCode) {
		case 87:
			if (sel.scrollTop() > 0) sel.animate({ scrollTop: 0 }, 500);
			break;

		case 83:
			if (sel.scrollTop() < sel[0].scrollHeight) sel.animate({ scrollTop: sel[0].scrollHeight }, 500)
			break;
	}
});

function getValue(elem) {
	let text;

	if ((text = $(elem).val()).length) {
		return text;
	}

	return null;
}

function getSelectedOption(elem) {
	return $(elem).val();
}

function getForm() {
	return {
		group: getSelectedOption("#selected-group"),
		groupName: getValue("#filter-name"),
		groupCourse: getSelectedOption("#selected-course"),
		groupHalf: getSelectedOption("#selected-past")
	};
}

function setStatus(status = 0) {
	let progress = 0,
		progressText = '',
		progressOperation = '';

	if (typeof status == "object") {
		progress = (status.position / status.max) * 100,
		progressText = Math.floor(progress) + '%',
		progressOperation = status.operation;
	}

	$(".progress-text").html(progressText);
	$(".operation").html(progressOperation);
	$(".progress-line").css({
		width: progress + '%'
	});
}
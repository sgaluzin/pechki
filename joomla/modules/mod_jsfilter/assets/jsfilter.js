// Список селекторов для модулей
var MJSF_TARGETS = {};
// Конфигурация
var MJSF_SETTINGS = {};
// Текст
var MJSF_STRINGS = {};
var sf_timer;
var sf_block;

// ----------------------------------------------------------------------------
// Инициализация
// ----------------------------------------------------------------------------

function sf_init (mid)
{
	var w = jQuery('#jsfilter_'+mid);
	if (!w) return;
	
	// Инициаизация каждого блока модуля
	var list = w.find('fieldset.sf_block');
	list.each(function() {
		var head = jQuery(this).find('.sf_block_header');
		var ctrl = jQuery(this).find('.sf_ctrl');
		var isPopular = false;

		if (!ctrl.length) {
			ctrl = jQuery(this).find('.sf_ctrl_popular');
			isPopular = true;
		}

		if (isPopular && ctrl.length) {
			// Блок "Популярные"
			head.click(sf_popularHandler);
		} else if (!isPopular) {
			// Сворачиваемый блок
			// Инициализация элемента для управления сворачиванием блока
			//head.click(sf_rollHandler);
		}
	});

	// Контроль курсора
	sf_block = null;
	w.find(".sf_block").each(function() {
		jQuery(this).mouseout(sf_blockHandlerMove);
	});

	// Инициализация обработчика предзапросов (для каждого типа элементов формы)
	if (MJSF_SETTINGS[mid].show_tip) {
		list = w.find('input[type="radio"]');
		list.each(function() {
			jQuery(this).change(function() { sf_onChangeFilter(this); });
		});

		list = w.find('input[type="checkbox"]');
		list.each(function() {
			jQuery(this).change(function() { sf_onChangeFilter(this); });
		});

		list = w.find('select');
		list.each(function() {
			jQuery(this).change(function() { sf_onChangeFilter(this); });
		});
	}

	// Инициализация слайдера
	list = w.find('[id="sf_slider_wrap"]');
	list.each(function() {
		sf_sliderInit(this);
	});

	// Инициализация мультиселекта с чекбоксами
	list = w.find('select.multicheck');
	if (list.length) {
		list.multipleSelect({
			selectAllText: MJSF_STRINGS.selectAll,
			allSelected: MJSF_STRINGS.allSelected,
			placeholder: MJSF_STRINGS.multicheckPlaceholder
		});
	}

	// Инициализация элементов "изображения"
	list = w.find('label.images');
	if (list.length) {
		list.each(function() {
			jQuery(this).click(sf_imagesHandler);
			// this.onclick = sfImagesHandler;
		});
	}
	

	// Обработчик сброса формы в исходное состояние
	w.find('input[type="reset"]').click(sf_resetHandler);

	// Инициализация разбиения на страницы
	var form = w.find("form").get(0);
	if (!form.sf_start) {
		jQuery('<input type="hidden" name="sf_start" value=""/>').appendTo(jQuery(form));
	}

	// Восстановление сохраненных параметров и выполнение фильтрации
	if (MJSF_SETTINGS[mid].params && MJSF_SETTINGS[mid].params.length && MJSF_SETTINGS[mid].storedUrl == window.location) {
		jQuery(form).find('#sf_dontstore').val(1);
		var allowLoad = sf_updateValues(jQuery(form), MJSF_SETTINGS[mid].params, 1);
		
		if (allowLoad) {
			sf_load(form, false);
		}
	}
}


// ----------------------------------------------------------------------------
// Обработчик кликов по изображениям
// ----------------------------------------------------------------------------

function sf_imagesHandler (ev)
{
	ev.stopPropagation();
	
	var wrap = ev.target;
	if (!wrap) return;

	wrap = jQuery(wrap);

	if (wrap.prop("tagName").toLowerCase() != 'label')
	{
		wrap = wrap.closest('label.images');
	}

	if (!wrap || !wrap.length) return;

	var input = wrap.find('input');

	if (input[0].checked) {
		wrap.addClass('active');
	} else {
		wrap.removeClass('active');
	}

	if (input[0].disabled) {
		wrap.addClass('disabled');
	} else {
		wrap.removeClass('disabled');
	}
}


// ----------------------------------------------------------------------------
// Обработчик сброса формы фильтра
// ----------------------------------------------------------------------------

function sf_resetHandler (e)
{
	// Запрет продолжения обработки события другими элементами
	e.stopPropagation();

	var w = jQuery(e.target).closest(".sf_wrapper");
	var wrapId = w.attr('id');
	var mid = wrapId.replace(/.*_(\d+)/, "$1");

	// Скртие подсказки
	if (sf_block) {
		sf_tipSlide(0);
	}
	
	// Сброс положения курсоров слайдеров
	w.find('[id="sf_slider_wrap"]').each(function () {
		var wrap = jQuery(this);
		var s = wrap.find("#sf_slider");
		var min = s.slider("option", "min");
		var max = s.slider("option", "max");
		s.slider( {values: [min, max]} );
		wrap.find("#sf_min").val(min);
		wrap.find("#sf_max").val(max);
		wrap.find("#sf_param_min").val(min);
		wrap.find("#sf_param_max").val(max);
		wrap.removeClass('touch');
	});

	// Сброс изображений
	w.find('label.images').each(function () {
		var el = jQuery(this);
		el.removeClass('active disabled');
	});

	var form = w.find("form").get(0);

	// Обнуление счетчика страниц
	form.sf_start.value = 0;

	// Сброс параметров фильтрации
	form.reset();

	// Снятие блокировок (после автоматической деактивации опций)
	var disabledElements = w.find(':disabled');

	jQuery.each(disabledElements, function(index, el) {
		// Пропуск элементов формы для имитации ссылок
		if (el.parentNode.nodeName == 'A') return true;
		el.disabled = false;
	});

	// Обновление всех элементов типа select + checkbox
	var selects = w.find('select.multicheck');
	jQuery.each(selects, function(index, el) {
		jQuery(el).multipleSelect("refresh");
	});


	if (MJSF_TARGETS[0]) {
		var target = jQuery(MJSF_TARGETS[mid])[0];
		if (target) {
			target.remove();
		}
		MJSF_TARGETS[0].show();
		MJSF_TARGETS[0] = null;
	}

	// Передача запроса на сервер для информаирования о сбросе параметров фильтрации
	jQuery.ajax({
		type	: 'post',
		url		: jQuery(form).attr('action') + '&task=reset',
		data	: jQuery(form).serialize(),
		dataType: 'json',
		success	: function(data) {

			if (!data || !data.status) return;

			// Блокировка отфильтрованных значений
			if (data.values) {
				sf_updateValues(jQuery(form), data.values);
			}
			
		} // success
	});
}


// ----------------------------------------------------------------------------
// Обработчик сворачивания блока
// ----------------------------------------------------------------------------

function sf_rollHandler (ev)
{
	var block = jQuery(ev.target).closest('fieldset.sf_block');
	var arrow = block.find('.sf_ctrl');
	var name  = arrow.attr('rel');
	
	block.find('.sf_block_params').slideToggle(50);
	arrow.toggleClass('roll');

	var today = new Date();
	var expire = new Date();
	expire.setTime(today.getTime() + 60 * 60 * 1000); // 1 час
	document.cookie = name + ' = ' + arrow.hasClass('roll') + '; path=/; expires='+expire.toGMTString();
}


// ----------------------------------------------------------------------------
// Обработчик клика по популярным записям
// ----------------------------------------------------------------------------

function sf_popularHandler (ev)
{
	var block = jQuery(ev.target).closest('fieldset.sf_block');
	var ctrl = block.find('.sf_ctrl_popular');
	var names  = ctrl.attr('rel').split('|');
	var isPopular = ctrl.hasClass('roll');
	
	var popular = block.find('.sf_block_params .sf_popular');
	var all = block.find('.sf_block_params .sf_all');

	if (isPopular) {
		all.hide();
		
		// Синхронизация свойств и блокировка скрываемых элементов (чтобы они не передавались на сервер)
		all.find("input, select").each(function() {
			// Поиск копии элемента в блоке all. 
			var target = popular.find('[value="'+this.value+'"]').get(0);
			if (target) {
				// Блокировка элемента, т.к. обнаружена копия в блоке popular
				this.disabled = true;
				// Копирование блокировки актуального значения
				if ( jQuery(this).hasClass('val_disabled') ) {
					jQuery(target).addClass('val_disabled');
				}
				
				// Копирование свойств
				if (typeof(this.checked) != 'undefined') {
					target.checked = this.checked;
				}
				if (typeof(this.selected) != 'undefined') {
					target.selected = this.selected;
				}
			} else {
				// Копия элемента в блоке all отсутствует. Изменения не производятся.
			}
		});

		// Снятие блокировки отображаемых элементов
		popular.find("input, select").each(function() {
			this.disabled = ( jQuery(this).hasClass('val_disabled') ) ? true : false;
		});
		
		// Показ блока и смена надписи в блоке управления
		popular.show();
		ctrl.html(names[1]);

	} else {
		popular.hide();
		
		// Синхронизация свойств и блокировка скрываемых элементов (чтобы они не передавались на сервер)
		// popular.find("input, select").each(function() {
		all.find("input, select").each(function() {
			// Поиск копии элемента в блоке popular
			var src = popular.find('[value="'+this.value+'"]').get(0);
			if (src) {
				if (typeof(this.checked) != 'undefined') {
					this.checked = src.checked;
				}
				if (typeof(this.selected) != 'undefined') {
					this.selected = src.selected;
				}
				// Блокировка элемента если копия блокирована,
				// либо элемент помечен заблокированным при обработке актуальных значений
				this.disabled = ( src.disabled || jQuery(src).hasClass('val_disabled') ) ? true : false;
			} else {
				// Элемент отсутствует в блоке популярных. Изменение только блокировки.
				this.disabled = ( jQuery(this).hasClass('val_disabled') ) ? true : false;;
			}
		});

		// Блокировка всех популярных опций (свернуты)
		popular.find("input, select").each(function() {
			this.disabled = true;
		});
		
		// Показ блока и смена надписи в блоке управления
		all.show();
		ctrl.html(names[0]);
	}

	ctrl.toggleClass('roll');
}


function sf_onChangeFilter (el)
{
	el = jQuery(el);
	var form = el.closest('form.sf_form');
	var tip = form.closest('.sf_wrapper').parent().find('#sf_tip');
	
	// hide
	if (sf_timer) {
		clearInterval(sf_timer);
	}
	sf_block = null;
	tip.hide();

	// Сброс страницы показа
	form.get(0).sf_start.value = 0;

	jQuery.ajax({
		type	: 'post',
		url		: form.attr('action') + '&task=request&pre=1',
		dataType: 'json',
		data	: form.serialize(),
		success	: function(data) {

			if (!data.status) return;

			// Сокрытие tip при выходе указателя за пределы блока
			tip.find('#sf_tip_count').html(data.count);
			sf_tipSlide(1, el);

			tip.find('a').get(0).onclick = function() {
				sf_load( form.get(0) );
				sf_tipSlide(0, el);
			};

			tip[0].onmouseover = function() {
				if (sf_timer) clearInterval(sf_timer);
			};

			// Блокировка отфильтрованных значений
			sf_updateValues(form, data.values);
			
		} // success
	});
}


// mode:
// 0 - Режим актуальных значений
// 1 - Режим восстановления сохраненных значений
// \return		true - можно выполнять фильтрацию, false - загрузка выполнена внутри функции
function sf_updateValues (form, values, mode)
{
	if (typeof(values) == 'undefined') return;
	if (typeof(mode) == 'undefined') mode = 0;

	var linkBlock = null;

	jQuery.each(values, function(index, item) {

		if (item.type == 'slider') {
			sf_updateValuesSlider(form, item, mode);
			
		} else if (item.type == 'select' || item.type == 'multiselect' || item.type == 'multiselect_2') {
			sf_updateValuesSelect(form, item, mode);
			
		} else if (item.type == 'link') {
			// Если присутствует ссылка, то по ней был выполнен клик.
			// Остальные параметры не обрабатываются.
			if (mode == 1 && !linkBlock) {
				// Поиск родительской ссылки
				var link = form.find('[name="'+item.name+'"]');
				if (link.length) {
					linkBlock = link.closest("a");
					linkBlock.attr('rel', 'active');
				}
			}
			
		} else if (item.type == 'images') {
			sf_updateValuesImages(form, item, mode);
			
		} else {
			sf_updateValuesGeneric(form, item, mode);
		}
			
		return true;
	});

	// Активация фильтрации по ссылке (имитация клика)
	if (linkBlock) {
		sf_linkHandler(linkBlock);
		return false;
	}

	return true;
}


function sf_updateValuesSelect(form, item, mode)
{
	var elements = form.find('[name="'+item.name+'"] option');
	
	elements.map(function() {
		if (!this.value) return true;
		
		if (mode) {
			// Режим восстановления сохраненных значений
			this.selected = (jQuery.inArray(this.value, item.values) >= 0) ? true : false;
		} else {
			// Режим актуальных значений
			this.disabled = (jQuery.inArray(this.value, item.values) >= 0) ? false : true;
		}
	});

	if (item.type == 'multiselect_2') {
		var select = form.find('[name="'+item.name+'"]');
		select.multipleSelect("refresh");
	}
}


function sf_updateValuesImages (form, item, mode)
{
	sf_updateValuesGeneric(form, item, mode);

	// Синхронизация изображений со значением input'ов
	var images = form.find('[name="'+item.name+'"]');
	
	images.map(function() {
		var wrap = jQuery(this).closest('label.images');
		if (!wrap || !wrap.length) return;

		if (this.checked) {
			wrap.addClass('active');
		} else {
			wrap.removeClass('active');
		}

		if (this.disabled) {
			wrap.addClass('disabled');
		} else {
			wrap.removeClass('disabled');
		}
	});
}


function sf_updateValuesGeneric(form, item, mode)
{
	var elements = form.find('[name="'+item.name+'"]');
	
	elements.map(function() {
		if (mode) {
			// Режим восстановления сохраненных значений
			if (item.type == 'radio' || item.type == 'checkbox') {
				if (!this.value) return true;
				// Проверка наличия значения в списке актуальных (может совпасть только 1 раз)
				var index = jQuery.inArray(this.value, item.values);
				if (index >= 0) {
					this.checked = true;
					delete item.values[index];
				} else {
					this.checked = false;
				}
				
			} else {
				this.value = item.values;
			}
			
		} else {
			// Режим актуальных значений
			if (!this.value) return true;
			if (jQuery.inArray(this.value, item.values) >= 0) {
				// Изменение блокировки только у элементов,
				// которые были заблокированы текущим обработчиком
				if ( jQuery(this).hasClass('val_disabled') ) {
					this.disabled = false;
				}
				jQuery(this).removeClass('val_disabled');
			} else {
				// При наличии блокировки изменения не вносятся
				if (!this.disabled) {
					this.disabled = true;
					jQuery(this).addClass('val_disabled');
				}
			}
		}
	});
}


function sf_updateValuesSlider(form, item, mode)
{
	var elements = form.find('[name^="'+item.name+'"]');
	var lastWrap = null;
	
	elements.map(function() {
		// Обработка одного слайдера (со всеми служебными полями)
		var wrap = jQuery(this).closest('#sf_slider_wrap');
		if (wrap == lastWrap) return true;
		lastWrap = wrap;

		// Измененные пользователем слайдеры не корректируются
		if ( wrap.hasClass('touch') ) return true;

		// Текстовые поля
		var min = (item.values[0]) ? parseFloat(item.values[0]) : 0;
		var max = (item.values[1]) ? parseFloat(item.values[1]) : 0;
		
		lastWrap.find('#sf_min').val(min);
		lastWrap.find('#sf_max').val(max);
		
		// Перемещение ползунков
		lastWrap.find('#sf_slider').slider("option", "values", [min, max]);

		// Смена передаваемых значений только послу ручной установки
		if ( lastWrap.hasClass('touch') ) {
			lastWrap.find('#sf_param_min').val(min);
			lastWrap.find('#sf_param_max').val(max);
		}
	});
}


function sf_blockHandlerMove (e)
{
	if (!sf_block) return;
	
	if (!e) var e = window.event;

	var reltg = (e.relatedTarget) ? e.relatedTarget : e.toElement;
	if (!reltg) return;

	while (reltg && reltg.tagName != 'BODY'){
		if (reltg == sf_block) {
			if (sf_timer) clearInterval(sf_timer);
			sf_timer = null;
			return;
		}
		reltg = reltg.parentNode;
	}

	if (sf_timer) clearInterval(sf_timer);

	sf_timer = setInterval(function() {
		clearInterval(sf_timer);
		sf_tipSlide(0, sf_block);
		sf_timer = null;
	}, 1500);
}


// mode: 0 - hide, 1 - show
function sf_tipSlide (mode, el)
{
	var block;
	
	if (el) {
		el = jQuery(el);
		block = el.closest(".sf_block");
		sf_block = block[0];
	} else {
		block = jQuery(sf_block);
		el = block;
	}
		
	var blockPos = block.offset();
	var wrap = block.closest('.sf_wrapper');
	var form = block.closest('form');
	var mid = form[0].mid.value;
	var tip = wrap.parent().find('#sf_tip');
	var pos;
	var animateOpt;

	if (!mode) {
		sf_block = null;
	}
	
	// Формирование параметров анимации (смещения)
	if (MJSF_SETTINGS[mid].dir)
	{
		// ----------------------------------
		// Вертикальное смещение подсказки
		// ----------------------------------
		var offset;
		var modifier;
		var adOffset;
		var displayOffset = jQuery(document).scrollTop();
		var hCenter = displayOffset + window.innerHeight / 2;

		// При запуске процесса скрытия начальной позицией является текущая
		if (el[0] == block[0] && mode == 0) {
			modifier = (tip.offset().top > blockPos.top) ? '-' : '+';
			offset = 0; // не нужен
			adOffset = tip.height();
		
		} else if ( (blockPos.top + block.height() / 2) > hCenter ) {
			// Модуль смещен вниз. Вывод кнопки сверху.

			// Вычисление дополнительного смещения для выхода за границы обертки модуля
			// (выплывание подсказки до границы модуля, а не блока)
			adOffset = blockPos.top - wrap.offset().top;
		
			if (mode) {
				offset = 0;
				modifier = '-';
			} else {
				offset = -tip.height();
				modifier = '+';
			}

			// Доп.смещение для тени
			adOffset += ( tip.outerHeight() - tip.height() );
			
		} else {
			// Модуль смещен вверх. Вывод кнопки снизу.

			// Вычисление дополнительного смещения для выхода за границы обертки модуля
			// (выплывание подсказки до границы модуля, а не блока)
			adOffset = 0;
			
			if (mode) {
				// offset = block.height() - tip.height();
				offset = wrap.height() - (blockPos.top - wrap.offset().top) - tip.height();
				modifier = '+';
			} else {
				offset = 0;
				modifier = '-';
			}
		}

		// Если текущий элемент скрыт, то подсказка выводится посередине блока
		if (el.css('display') == 'none') {
			el = block;
		}

		// Формирование параметров начальной позиции элемента
		pos = {
			left: (el[0] == block[0] && mode == 0) ? tip.offset().left : (block.offset().left + block.width() / 2 - tip.width() / 2),
			top: (el[0] == block[0] && mode == 0) ? tip.offset().top : (blockPos.top + offset)
		};

		// Определение конечной координаты подсказки
		var endValue = tip.outerHeight() + adOffset;
		animateOpt = { top: modifier + '=' + endValue + 'px' };
	}
	else
	{
		// ----------------------------------
		// Горизонтальное смещение подсказки
		// ----------------------------------
		var offset;
		var modifier;

		if (el[0] == block[0] && mode == 0) {
			modifier = (tip.offset().left > blockPos.left) ? '-' : '+';
			offset = 0; // не нужен
		
		} else if ( (blockPos.left + block.width() / 2) > window.innerWidth / 2 ) {
			// Модуль смещен вправо. Вывод кнопки слева.
			if (mode) {
				offset = 0;
				modifier = '-';
			} else {
				offset = -tip.width();
				modifier = '+';
			}
		} else {
			// Модуль смещен влево. Вывод кнопки справа.
			if (mode) {
				offset = block.width() - tip.width();
				modifier = '+';
			} else {
				offset = block.width();
				modifier = '-';
			}
		}

		// Если текущий элемент скрыт, то подсказка выводится посередине блока
		if (el.css('display') == 'none') {
			el = block;
		}

		// Формирование параметров начальной позиции элемента
		pos = {
			left: (el[0] == block[0] && mode == 0) ? tip.offset().left : (blockPos.left + offset),
			top: (el[0] == block[0] && mode == 0) ? tip.offset().top : (el.offset().top + el.height() / 2 - tip.height() / 2)
		};

		// Определение конечной координаты подсказки
		var endValue = tip.width();
		animateOpt = { left: modifier + '=' + endValue + 'px' };
	}

	// Вывод водсказки в начальной позиции
	tip.show();
	tip.offset(pos);
	
	// Запуск анимации
	tip.animate(
		animateOpt,
		{
			queue	 : false,
			duration : 300,
			complete : function() { if (!mode) {tip.hide();} }
		}
	);
}


function sf_load (form, resetLinks)
{
	if (typeof(resetLinks) == 'undefined') resetLinks = true;
	
	var mid = form.mid.value;
	form = jQuery(form);
	
	// Проверка наличия селектора
	if ( !MJSF_TARGETS[mid] ) return true;

	var target = jQuery(document).find(MJSF_TARGETS[mid])[0];
	// Проверка наличия элемента на странице
	if (!target) return true;

	target = jQuery(target);

	// Сокрытие кнопки
	if (sf_block) {
		sf_tipSlide(0);
	}

	// Сохранение исходного блока, в котором выводится результат фильтрации
	if (!MJSF_TARGETS[0]) {
		var clone = target.clone();
		target.hide();
		MJSF_TARGETS[0] = target;
		clone.insertBefore(target);
		target = clone;
	}

	// Замена target на блок ожидания
	var sample = jQuery('#jsfilter_ajax_sample').get(0).children[0];
	var loader = jQuery(sample).clone();
	target.empty();
	loader.appendTo(target);

	// Сброс параметров всех ссылок (при необходимости)
	if (resetLinks) {
		form.find("a input").each(function() {
			this.disabled = true;
		});
	}

	// Отправка AJAX-запроса 
	jQuery.ajax({
		type	: 'post',
		url		: form.attr('action') + '&task=request&url='+encodeURIComponent( window.location ),
		dataType: 'html',
		data	: form.serialize(),
		success	: function(data) {

			target.html(data);

			// Сброс параметра запрета сохранения парметров фильтрации
			form.find('#sf_dontstore').val('');

			// Замена ссылок pagination для ajax-запросов
			var list = target.find(".pagination a").each(function() {
				if (!this.href) return true;
				
				var matches = this.href.match(/sf_limitstart=([0-9]+)/);
				var start = (matches && matches[1]) ? matches[1] : 0;
				this.href = "javascript:void(0);"
				this.onclick = function() {
					sf_doPagination(form.get(0), start);
				}
			});

			// Замена панели сортировки
			var wrap = target.find(".box_products_sorting");
			if (wrap) {
				var panelSample = wrap.find(".sf_panel");
				if (panelSample) {
					var panel = panelSample.clone();
					wrap.empty();
					panel.appendTo(wrap);
				}
			}

			// Проверка наличия блока актуальных значений в результате фильтрации.
			var el = target.find('#sf_actual_values');
			if (el) {
				var str = el.text();
				var jsonData;
				
				if (str) {
					jsonData = jQuery.parseJSON(str);
				}

				if (jsonData) {
					sf_updateValues(form, jsonData);
				}
			}

			// Прокрутка страницы к началу списка товаров
			jQuery('html, body').animate({
				scrollTop: target.offset().top
			}, 250);

			// Выполнение пользовательского кода при загрузке данных
			try {
				eval(MJSF_SETTINGS[mid].onload_code);
			} catch (ex) {
			}
		}
	});

	// Блокировка перезагрузки страницы
	return false;
}


function sf_doPagination (form, start)
{
	if (!form) return;

	form.sf_start.value = start;

	// Загрузка данных
	sf_load(form, false);

	// Прокрутка страницы к началу списка товаров
	var mid = form.mid.value;
	if (MJSF_TARGETS[mid]) {
		var target = jQuery(document).find(MJSF_TARGETS[mid]);

		jQuery('html, body').animate({
			scrollTop: target.offset().top
		}, 250);
	}
}


function sf_doSort (fid, el)
{
	el = jQuery(el);
	var name = el.attr('rel');
	var form = jQuery('#'+fid).get(0);
	
	if ( !name || !form ) return;

	var mid = form.mid.value;

	// Обновление направления сортировки
	form.sf_orderby.value = name;
	
	var s = el.find('.sort');
	if ( s.hasClass('asc') ) {
		form.sf_order.value = 'desc';
	} else {
		form.sf_order.value = 'asc';
	}

	if ( sf_load(form, false) ) {
		// AJAX отключен, либо неверный селектор контента
		// Передача запроса (submiit формы)
		form.submit();
	}
	
}


function sf_doPostFilter (fid, el)
{
	var form = jQuery('#'+fid).get(0);
	var stock = jQuery('#'+fid+ ' #stock_state');

	// Обновление значения параметра
	if (el.value == 0) {
		stock.val('');
	} else {
		stock.val('true');
	}

	if ( sf_load(form, false) ) {
		// AJAX отключен, либо неверный селектор контента
		// Передача запроса (submiit формы)
		form.submit();
	}
}


// ----------------------------------------------------------------------------
// Slider
// ----------------------------------------------------------------------------

function sf_sliderInit (wrap)
{
	var wrap = jQuery(wrap);
	var el = wrap.find('#sf_slider');
	if (!el) return;

	var params = jQuery(wrap).attr('rel');
	if (!params) return;
	params = jQuery.parseJSON(params);

	var name = params.name;
	delete(params.name);
	if (!name) return;

	if (!params.values || !params.values.length) {
		params.values[0] = param.min;
		params.values[1] = param.max;
	}
	
	params.stop = sf_sliderHandlerStop;
	params.slide = sf_sliderHandlerSlide;
	
	el[0].slide = null;
	el.slider(params);

	var wrapMin = wrap.find("#sf_slider_min");
	jQuery("<input />", {
		id: 'sf_min',
		type: 'text',
		name: 'sf_dummy[min]',
		autocomplete: 'off',
		rel: params.values[0],
		value: params.values[0],
		keyup: function(ev) {
			var val = ev.target.value;
			// if ( isNaN(val) || val > el.slider("values", 1) ) return;
			if ( isNaN(val) ) return;

			el.slider("values", 0, val);
			wrapMin.find('#sf_param_min').val(val);
		}
	}).appendTo(wrapMin);

	jQuery("<input />", {
		id: 'sf_param_min',
		type: 'hidden',
		name: name+'[min]',
		value: params.values[0]
		// ,
		// disabled: 'disabled'
	}).appendTo(wrapMin);


	var wrapMax = wrap.find("#sf_slider_max");
	jQuery("<input />", {
		id: 'sf_max',
		type: 'text',
		name: 'sf_dummy[max]',
		autocomplete: 'off',
		rel: params.values[1],
		value: params.values[1],
		keyup: function(ev) {
			var val = ev.target.value;
			// if ( isNaN(val) || val < el.slider("values", 0) ) return;
			if ( isNaN(val) ) return;

			el.slider("values", 1, val);
			wrapMax.find('#sf_param_max').val(val);
		}
	}).appendTo(wrapMax);

	jQuery("<input />", {
		id: 'sf_param_max',
		type: 'hidden',
		name: name+'[max]',
		value: params.values[1]
		// ,
		// disabled: 'disabled'
	}).appendTo(wrapMax);
}


function sf_sliderHandlerStop (ev, ui)
{
	var wrap = jQuery(ui.handle).closest("#sf_slider_wrap");
	if (!wrap) return;

	// Фиксация изменения слайдера пользователем
	wrap.addClass('touch');

	var elMin = wrap.find("#sf_param_min");
	var elMax = wrap.find("#sf_param_max");
	var changed = false;

	if (ui.value == ui.values[0] && elMin.attr('rel') != ui.value) {
		// Min
		changed = true;
		// elMin.attr('rel', ui.value);
		elMin.val(ui.value);
	}
	if (ui.value == ui.values[1] && elMax.attr('rel') != ui.value) {
		// Max
		changed = true;
		// elMax.attr('rel', ui.value);
		elMax.val(ui.value);
	}

	// Передача события об измненении фильтра только при
	// изменении значения и при включенной опции отправки предзапросов
	var mid = wrap.closest("form").get(0).mid.value;
	if (!changed || !MJSF_SETTINGS[mid].show_tip) return;

	sf_onChangeFilter(wrap[0]);
}


function sf_sliderHandlerSlide (ev, ui)
{
	var wrap = jQuery(ui.handle).closest("#sf_slider_wrap");
	if (!wrap) return;

	if (ui.value == ui.values[0]) {
		// Min
		var target = wrap.find("#sf_min");
		target.val( ui.value );
	}
	if (ui.value == ui.values[1]) {
		// Max
		var target = wrap.find("#sf_max");
		target.val( ui.value );
	}
}


function sf_linkHandler (link)
{
	if (!link) return;
	link = jQuery(link);
	var form = link.closest("form");

	// Просмотр результатов начиная с первой страницы
	form.get(0).sf_start.value = 0;

	// Блокировка всех ссылок в форме кроме текущей
	form.find("a input").each(function() {
		this.disabled = true;
	});

	link.find("input").each(function() {
		this.disabled = false;
	});
	
	// Отправка запроса фильтрации
	sf_load(form.get(0), false);

	// Блокировка инпутов со значениями, для предотвращения
	// передачи данных текущей ссылки при нажатии на другую
	// jQuery(link).find("input").each(function() {
		// this.disabled = true;
	// });
}

cfgame = {};

cfgame.app = function (gameHash, options) {
	var hash = gameHash; // jatek egyedi hash-e

	var defaults = {
		'logo': null,
		'welcome': null,
		'playButton': {
			'selector': 'start-game',
			'url': BASE_HOST + 'ajax/start'
		},
		'metrics': { // Ez tarolja a metrikak beallitasait
			'settings': {
				'plusHeightPercent': 50,
				'minusHeightPercent': 50
			},
			'items': {} // Tarolja az egyes metrikak beallitasait. Pl: 'money': { 'min': -10000, 'max': 10000  }
		},
		'answer': {
			'url': BASE_HOST + 'ajax/answer'
		},
		'resultForm': {
			'url': BASE_HOST + 'ajax/saveuserdata'
		}
	};

	// Ez a tomb tarolja a konkret bar objektumokat
	var metrics = [];

	var metricsValues = {};

	var settings = $.extend(true, defaults, options);

	this.run = function () {
		_setAppLogo();
		_setWelcome();
		_initPoints();
		_initMetrics();
		_initPlayButton();
		_initHallOfFame();
	};

	var _setAppLogo = function () {
		$('#app-logo')
			.attr('src', settings.logo);
		$('#app-logo')
			.css('display', 'block');
	};

	var _setWelcome = function () {
		$('#welcome')
			.html(settings.welcome);
	};

	var _initPoints = function () {
		_updatePoints(settings.points);
	};

	var _initMetrics = function () {
		var skeletonVertical = $('#chart-skeleton'),
			skeletonHorizontal = $('#chart-skeleton');
		skeletonVertical.removeAttr('id');
		skeletonHorizontal.removeAttr('id');

		$.each(settings.metricsInit, function (idx, data) {
			var verticalBar = _createSkeleton(skeletonVertical, data, 'vertical');
			var horizontalBar = _createSkeleton(skeletonHorizontal, data, 'horizontal');

			// A konkret objektumot is bepakoljuk
			metrics.push({
				'id': data.id,
				'bars': {
					'vertical': verticalBar,
					'horizontal': horizontalBar
				}
			});

			settings.metrics.items[data.id] = {
				'min': data.min,
				'max': data.max
			};

		});
	};

	var _initHallOfFame = function() {
		hallOfFame = new cfgame.hallOfFame();
		hallOfFame.init();
	}

	var _createSkeleton = function (skeleton, data, type) {
		// clone letrehozasa
		var clone = skeleton.clone();
		clone.css('display', 'block');
		// property-k beallitasa
		clone.attr('data-id', data.id);
		clone.attr('data-type', type);
		clone.find('.value')
			.html(data.initial);
		clone.find('.unit')
			.html(data.unit);
		clone.find('.label')
			.html(data.label);

		if (type == 'horizontal') {
			$('#metrics-footer-container')
				.append(clone);
		} else {
			$('#metrics-right-container')
				.append(clone);
		}

		var bar = new cfgame.bar(data),
			selector = 'div[data-id="' + data.id + '"][data-type="' + type + '"] .bar-container',
			barValue = _calculateBarNewValue(selector, data.initial, data.max, data.min);

		if (type == 'horizontal') {
			clone.css('transform', 'rotate(90deg)');
		}

		// Ez allitja be a bar alaperteket
		bar.set(selector, barValue);

		return bar;
	};

	var _calculateBarNewValue = function (selector, newValue, max, min) {
		var height = $(selector)
			.height();
		if (newValue > 0) {
			var barHeight = parseInt(height * settings.metrics.settings.plusHeightPercent / 100),
				proportion = parseInt(max),
				value = parseInt(newValue);

			var barValue = Math.round(barHeight / proportion * value);
		} else if (newValue < 0) {
			var barHeight = parseInt(height * settings.metrics.settings.plusHeightPercent / 100),
				proportion = parseInt(min * -1),
				value = parseInt(newValue);

			var barValue = Math.round(barHeight / proportion * value);
		} else {
			var barValue = 0;
		}

		return barValue;
	};

	var _updateMetricsValue = function (metricsSelector, newValue, startValue) {
		var metricsValueObj = $(metricsSelector + ' span.value');

		if (typeof startValue === "undefined") {
			startValue = metricsValueObj.text();
		}

		metricsValueObj.prop('Counter', startValue)
			.animate({
				Counter: newValue
			}, {
				duration: 500,
				easing: 'swing',
				step: function (now) {
					$(this)
						.text(Math.ceil(now));
				}
			});
	};

	var _updateMetrics = function (metricsData) {
		$.each(metrics, function (idx, data) {
			if (data.id in metricsData) {
				$.each(data.bars, function (type, bar) {
					var metricsSelector = 'div[data-id="' + data.id + '"][data-type="' + type + '"]',
						barContainerSelector = metricsSelector + ' .bar-container',
						value = metricsData[data.id];

					var barValue = _calculateBarNewValue(
						barContainerSelector,
						value,
						settings.metrics.items[data.id].max,
						settings.metrics.items[data.id].min
					);
					bar.set(barContainerSelector, barValue);

					_updateMetricsValue(metricsSelector, value);
				});
			}
		});
	};

	var _updatePoints = function (points) {
		$('#score')
			.html(points);
	};

	var _initPlayButton = function () {
		$('#' + settings.playButton.selector)
			.bind('click', function () {
				wx.ajax(settings.playButton.url, 'h=' + hash, {
					afterSuccess: function (response) {
						$('#left-container').fadeOut(400, function() {
							$(this).empty();
							var task = response.data.task;

							// Kerdes betoltese
							var questionHtmlObj = _setQuestion(task);
							$('#left-container')
								.append(questionHtmlObj)
								.fadeIn(400).find('.answer').blur();
							// Valaszok betoltese
							_setAnswers(task);
						})
					}
				});
			});
	};

	var _setQuestion = function (task) {
		var questionHtmlObj = $('#question-skeleton').clone(),
			questionImgHtmlObj = questionHtmlObj.find('#question-image-skeleton'),
			questionTextHtmlObj = questionHtmlObj.find('#question-text-skeleton'),
			questionFaceTextHtmlObj = questionHtmlObj.find('#question-face-text-skeleton'),
			faceText = task.staff.position + '<br/>' + task.staff.name;

		questionHtmlObj.css('display', 'flex');

		questionImgHtmlObj
			.attr('id', 'question-image')
			.attr('src', task.staff.image);

		questionFaceTextHtmlObj
			.removeAttr('id')
			.html(faceText);

		questionTextHtmlObj
			.attr('id', 'question-text')
			.html(task.question);

		return questionHtmlObj;

	};

	var _setAnswers = function (task) {
		// Valaszok betoltese
		$.each(task.answers, function (answerHash, answer) {
			var answerHtmlObj = $('#answer-skeleton')
				.clone();
			
			answerHtmlObj.hover(function() {
				$(this).addClass('hover');
			}, function() {
				$(this).removeClass('hover');
			});
			
			answerHtmlObj.css('display', '');
			answerHtmlObj.removeAttr('id');
			answerHtmlObj.attr('data-id', answerHash);
			answerHtmlObj.find('.answer-letter')
				.html(answer.letter);
			answerHtmlObj.find('.answer-text')
				.html(answer.label);

			answerHtmlObj.bind('click', function () {
				$('#left-container')
					.fadeOut(400, function () {
						wx.ajax(settings.answer.url, 'h=' + hash + '&q=' + task.id + '&a=' + answerHash, {
							afterSuccess: function (response) {
								// Timeline frissitese
								var timelineData = response.data.timeline;
								var indicatorWidthPercent = Math.ceil(timelineData.time / 360 * 100);
								$('.timeline .cell').css('width', indicatorWidthPercent + '%');
								$('.timeline .cell').text(timelineData.month);

								// Metrics frissitese
								_updateMetrics(response.data.metrics);
								_updatePoints(response.data.points);

								// Feladat jon
								if ('task' in response.data) {
									$('#left-container').empty();
									var task = response.data.task;

									// Kerdes betoltese
									var questionHtmlObj = _setQuestion(task);
									$('#left-container').append(questionHtmlObj);

									// Valaszok betoltese
									_setAnswers(task);

									$('#left-container').fadeIn();

								// Finish jon
								} else if ('finish', response.data) {
									var translations = response.data.translations;
									$('.placehold-left').addClass('placehold-left--narrow');
									$('.sharing').addClass('finish');
									$('#score').text(response.data.points);
									$('.score').fadeIn(1300);
									$('#left-container').empty();
									$('#left-container').html(response.data.finish);

									$('#right-container').empty();
									$('#right-container').addClass('wideFinish');

									// Eredmeny - korok
									var resultcirclesHtmlObj = $('#result-circles-skeleton').clone();
									resultcirclesHtmlObj.attr('id', 'result-circles');
									resultcirclesHtmlObj.removeAttr('style');

									// Eredmeny - egy kor
									var resultcircleHtmlObjSkeleton = $('#result-circle-skeleton');
									resultcircleHtmlObjSkeleton.removeAttr('style');
									$.each(settings.metricsInit, function (idx, mData) {
										var resultcircleHtmlObj = resultcircleHtmlObjSkeleton.clone();
										resultcircleHtmlObj.find('*[data-id=label]').text(mData.label);
										resultcircleHtmlObj.find('*[data-id=unit]').text(mData.unit);
										resultcircleHtmlObj.find('*[data-id=value]').text(response.data.metrics[mData.id]);
										resultcirclesHtmlObj.append(resultcircleHtmlObj);
									});

									$('#right-container')
										.append(resultcirclesHtmlObj);

									// Eredmeny - szoveges
									var resulttextHtmlObj = $('#result-text-skeleton').clone();

									resulttextHtmlObj.attr('id', 'result-text');

									var resultText = translations.FINISH_POSITION_TEXT;
									resulttextHtmlObj.html(resultText);
									resulttextHtmlObj.removeAttr('style');
									$('#right-container').append(resulttextHtmlObj);
									$('#left-container').append(resulttextHtmlObj.clone());

									// Eredmeny form
									var resultformHtmlObj = $('#result-form-skeleton')
										.clone();

									resultformHtmlObj.attr('id', 'result-form');
									resultformHtmlObj.removeAttr('style');

									$('#right-container')
										.append(resultformHtmlObj);

									$('#result-form label[for="name"]').html(translations.FINISH_FORM_NAME_TITLE);
									$('#result-form label[for="email"]').html(translations.FINISH_FORM_EMAIL_TITLE);
									$('#result-form input[name="send-result-form"]').val(translations.FINISH_SEND_BUTTON);

									$('#left-container').fadeIn();
									resultformHtmlObj.find('input[name="send-result-form"]')
										.bind('click', function () {
											var formHtmlObj = $('#result-form'),
												nameHtmlObj = formHtmlObj.find('input[name="name"]'),
												emailHtmlObj = formHtmlObj.find('input[name="email"]'),
												hasError = false;

											if (nameHtmlObj.val() !== '') {
												nameHtmlObj.removeClass('error');
											} else {
												hasError = true;
												nameHtmlObj.addClass('error');
											}

											if (emailHtmlObj.val() !== '') {
												if (_isValidEmailAddress(emailHtmlObj.val())) {
													emailHtmlObj.removeClass('error');
												} else {
													hasError = true;
													emailHtmlObj.addClass('error');
												}
											} else {
												hasError = true;
												emailHtmlObj.addClass('error');
											}

											if (!hasError) {
												wx.ajax(settings.resultForm.url, 'h=' + hash + '&n=' + nameHtmlObj.val() + '&e=' + emailHtmlObj.val(), {
													afterSuccess: function (response) {
														if ('message' in response.data) {
															hallOfFame.show();
															hallOfFame.clearFiltersAndReload();
                                                                                                                        swal("Adataidat mentettük!", response.data.message, "success");
															$('#result-form input[name="name"]')
																.attr('disabled', true);
															$('#result-form input[name="email"]')
																.attr('disabled', true);
														} else if ('messageError' in response.data) {
                                                                                                                    sweetAlert("Nem sikerült!", response.data.messageError, "error");
                                                                                                                }
													}
												});
											}
										});

									// Ujra jatszom gomb hozzaadasa
									var playAgainHtmlObj = $('#play-again-container-skeleton').clone();
									playAgainHtmlObj.removeAttr('id');
									playAgainHtmlObj.removeAttr('style');
									$('#left-container').append(playAgainHtmlObj);
									$('button[name="play-again"]').click(function() {
										location.reload();
									});
								}
							}
						});
					});
			});

			$('#left-container')
				.append(answerHtmlObj);
		});
	};

	var _isValidEmailAddress = function (emailAddress) {
		var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
		return pattern.test(emailAddress);
	};

};

cfgame.hallOfFame = function (options) {

	var defaults = {
		dataTable: {
			"info": false,
			"ordering": false,
			"ajax": {
				"url": "/ajax/halloffame",
				"type": "POST",
				"data": function(d) {
					d.year = $('#year').val();
					d.month = $('#month').val();
					d.week = $('#week').val();
				}
			},
			"language": {
                            "zeroRecords": "Nincs eredmény ",
                            "infoEmpty": "Nincs eredmény",
                            "paginate": {
                                "previous": "Előző",
                                "next": "Következő"
                            }
			}
		}
	}

	var settings = $.extend(true, defaults, options);

	var table = null;

	this.init = function() {
		_initTable();
		_initFilters();
	}

	this.reload = function() {
		_reloadTable();
	}

	this.clearFiltersAndReload = function() {
		_clearFilters();
		_reloadTable();
	}

	this.show = function() {
		$('#hall-of-fame').show();
	}

	var _initTable = function() {
		table = $("#hall-of-fame-table").DataTable(settings.dataTable);
	}

	var _initFilters = function() {
		_clearFilters();
		$('#year').on('change', function () {
			if (this.value != '') {
				$('#month').removeAttr('disabled');
				$('#week').removeAttr('disabled');
			} else {
				$('#week').val('');
				$('#month').val('');
				$('#month').attr('disabled', 'disabled');
				$('#week').attr('disabled', 'disabled');
			}
			table.ajax.reload().draw();
		});

		$('#month').on('change', function () {
			if (this.value != '') {
				$('#week').val('');
			}
			table.ajax.reload().draw();
		});

		$('#week').on('change', function () {
			if (this.value != '') {
				$('#month').val('');
			}
			table.ajax.reload().draw();
		});
	}

	var _reloadTable = function() {
		table.ajax.reload().draw();
	}

	var _clearFilters = function() {
		$('#year').val('');
		$('#month').val('');
		$('#week').val('');

		$('#month').attr('disabled', 'disabled');
		$('#week').attr('disabled', 'disabled');
	}

}

cfgame.bar = function (data) {

	var color = {
		plus: 'green',
		minus: 'red'
	}

	color = data.color;

	this.set = function (selector, value) {
		var transitionTime = 500,
			container = $(selector),
			bar = container.find('.bar'),
			containerHeight = container.height(),
			halfContainerHeight = containerHeight / 2,
			value = value > halfContainerHeight ? halfContainerHeight : value,
			value = value < halfContainerHeight * -1 ? halfContainerHeight * -1 : value,
			barTop = parseInt(bar.css('top')),
			barBottom = parseInt(bar.css('bottom')),
			isPlus = barBottom == halfContainerHeight && barTop < halfContainerHeight ? true : false,
			isMinus = barTop == halfContainerHeight && barBottom < halfContainerHeight ? true : false,
			originalValue = value,
			value = halfContainerHeight - parseInt(value),
			valueInverse = halfContainerHeight - (originalValue * -1);

		if (originalValue === 0) {
			_setBarTop(bar, halfContainerHeight);
			_setBarBottom(bar, halfContainerHeight);
		} else if (originalValue > 0) {
			if (isPlus) {
				_setBarTop(bar, value);
			} else if (isMinus) {
				bar.addClass('to-plus');
				bar.removeClass('minus');
				_setBarBottom(bar, halfContainerHeight);
				_setBarTop(bar, value);

				setTimeout(function () {
					bar.removeClass('to-plus');
				}, transitionTime * 2);
			} else {
				_setBarTop(bar, value);
				bar.removeClass('minus');
			}
		} else {
			if (isMinus) {
				_setBarBottom(bar, valueInverse);
			} else if (isPlus) {
				bar.addClass('to-minus');
				bar.addClass('minus');
				_setBarTop(bar, halfContainerHeight);
				_setBarBottom(bar, valueInverse);

				setTimeout(function () {
					bar.removeClass('to-minus');
				}, transitionTime * 2);
			} else {
				_setBarBottom(bar, valueInverse);
				bar.addClass('minus');
			}
		}
	};

	var _setBarTop = function (bar, value) {
		bar.css({
			"top": value + 'px',
			"background": color.plus
		});
	};

	var _setBarBottom = function (bar, value) {
		bar.css({
			"bottom": value + 'px',
			"background": color.minus
		});
	};

	return this;
};

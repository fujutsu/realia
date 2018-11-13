jQuery(document).ready(function extra() {
	var maxNumberPhotos = 10;

	/*jQuery(document).on("click", "#upload-btn", function (e) {
	 e.preventDefault();
	 var image = wp.media({
	 title   : offer_params.wp_media_title,
	 multiple: true,
	 }).open()
	 .on('select', function (e) {
	 image.state().get('selection').each(function (image) {

	 var image_object = image.toJSON(),
	 offer_blog = '',
	 tooltip_tease = '',
	 offer_map_marker = '',
	 offer_single_tease = '',
	 add_offer_gallery = image_object.url;

	 if (!checkUploadList(jQuery('.ad-form .list-photos'))) return;

	 if (typeof image_object.sizes.offer_blog_loop_tease !== 'undefined') {
	 offer_blog = image_object.sizes.offer_blog_loop_tease.url;
	 }

	 if (typeof image_object.sizes.tooltip_tease_on_map !== 'undefined') {
	 tooltip_tease = image_object.sizes.tooltip_tease_on_map.url;
	 }

	 if (typeof image_object.sizes.offer_map_marker_tease !== 'undefined') {
	 offer_map_marker = image_object.sizes.offer_map_marker_tease.url;
	 }

	 if (typeof image_object.sizes.offer_single_tease !== 'undefined') {
	 offer_single_tease = image_object.sizes.offer_single_tease.url;
	 }

	 if (typeof image_object.sizes.add_offer_gallery_tease !== 'undefined') {
	 add_offer_gallery = image_object.sizes.add_offer_gallery_tease.url;
	 }

	 jQuery('#addPhotoElement').before('<li>' +
	 '<div class="wrapper js_modal-slide-item offer_gallery_item" ' +
	 'data-img-id="' + image_object.id + '" ' +
	 'data-offer-blog-src="' + offer_blog + '" ' +
	 'data-tooltip-tease-src="' + tooltip_tease + '" ' +
	 'data-offer-map-marker-src="' + offer_map_marker + '" ' +
	 'data-offer-single-tease-src="' + offer_single_tease + '" ' +
	 'data-modal-src="' + image_object.url + '" ' +
	 'style="background-image: url(\'' + add_offer_gallery + '\')">' +
	 '</div>' +
	 '<span class="btn btn-remove-elem deco-icon icon-close2"></span>' +
	 '</li>'
	 );

	 // Second check
	 checkUploadList(jQuery('.ad-form .list-photos'));
	 });

	 });

	 });*/


	if (jQuery('#ad-form').length) {
		jQuery(document).on('click', '.address-suggest__drop li', function () {
			var locality = jQuery(this).data('city');
			jQuery('.metro-block-select-inp-row').replaceWith('<div class="metro-block-select"></div>');
			jQuery.ajax({
				url       : offer_params.ajax_url,
				type      : "GET",
				dataType  : 'json',
				data      : 'action=deco_add_offer_show_metro&locality=' + locality,
				beforeSend: function () {
					//container.css('opacity', 0.2);
					//jQuery('.maincontent .loader-wrapper').fadeIn(200);
				},
				success   : function (response) {

					if (response.success) {
						if (response.data.metro) {
							jQuery('.metro-block-select').replaceWith(response.data.metro);
						}
					}
					//jQuery('.maincontent .loader-wrapper').fadeOut(200);
				}
			});
		});
	}

	function showFileUploadErrorsModal(title, content) {

		var modalFrame = jQuery('.js__add-offer-message-modal');

		if (modalFrame.length > 0) {

			try {
				if (!modalFrame.hasClass('active')) {
					modalFrame.addClass('active');

					try {
						var titleBlock = modalFrame.find('[data-title-block="1"]');
						var contentBlock = modalFrame.find('[data-content-block="1"]');

						if (title.length > 0) {
							titleBlock.text(title);
						}

						if (content.length > 0) {
							var htmlToInsert = ''
							var arrayLength = content.length;

							for (var i = 0; i < arrayLength; i++) {
								try {
									htmlToInsert = htmlToInsert + '<p>' + content[i] + '</p>';
								} catch (e) {
									console.log(e);
								}
							}
							contentBlock.html(htmlToInsert);
						}
					} catch (e) {
						console.log(e);
					}

				}
			} catch (e) {
				console.log(e);
			}

		}

	}

	function addOfferGalleryUpload() {

		var galleryInput = jQuery('#js__add-offer-gallery');

		if (galleryInput.length > 0) {

			galleryInput.on('change', function () {

				var dataToUpload = new FormData();

				var supportedFileFormats = [
					'image/jpeg',
					'image/png',
					'image/pjpeg',
					'image/gif'
				];

				var incorrectFormatFiles = [];
				var tooLargeFiles = [];
				var emptyFiles = [];
				var uploadErrorMessage = [];

				var j = 0;

				if (jQuery(this)[0].files.length > 0) {

					var limit = jQuery(this)[0].files.length;

					var list = (jQuery('.ad-form .list-photos'));
					var items = list.find('> li:not(.add-photo-block)'),
						currentMaxNumber = maxNumberPhotos;

					if (limit > currentMaxNumber) {
						limit = currentMaxNumber;
					}

					if (items.length > 0 && limit > (currentMaxNumber - items.length) > 0) {
						limit = currentMaxNumber - items.length;
					}

					for (var i = 0; i < limit; i++) {
						var currentFile = jQuery(this)[0].files[i];

						if (jQuery.inArray(currentFile.type, supportedFileFormats) == '-1') {
							try {
								incorrectFormatFiles.push(currentFile.name);
							} catch (e) {
								console.log(e);
							}
							continue;
						}

						if (currentFile.size == 0) {
							try {
								emptyFiles.push(currentFile.name);
							} catch (e) {
								console.log(e);
							}
							continue;
						}

						if (currentFile.size > 3150000) {
							try {
								tooLargeFiles.push(currentFile.name);
							} catch (e) {
								console.log(e);
							}
							continue;
						}

						try {
							dataToUpload.append('add_offer_gallery_item_' + j, currentFile);
							j++;
						} catch (e) {
							console.log(e);
						}
					}

				}

				if (incorrectFormatFiles.length > 0) {
					uploadErrorMessage.push(offer_params.messages.errorIncorrectFormat.replace('%filenames%', incorrectFormatFiles.join(', ')));
				}

				if (emptyFiles.length > 0) {
					uploadErrorMessage.push(offer_params.messages.errorEmptyFiles.replace('%filenames%', emptyFiles.join(', ')));
				}

				if (tooLargeFiles.length > 0) {
					uploadErrorMessage.push(offer_params.messages.errorTooLargeFiles.replace('%filenames%', tooLargeFiles.join(', ')));
				}

				if (uploadErrorMessage.length > 0) {
					showFileUploadErrorsModal(offer_params.messages.errorPopupTitle, uploadErrorMessage);
				}

				if (uploadErrorMessage.length == 0) {

					dataToUpload.append('action', 'deco_add_offer_gallery_upload');

					try {
						var galleryFromCookie = getCookie('deco_add_offer_gallery_images');

						if (galleryFromCookie.length > 0) {
							dataToUpload.append('gallery_from_cookie', galleryFromCookie);
						}
					} catch (e) {
						console.log(e);
					}

					if (!checkUploadList(jQuery('.ad-form .list-photos'))) return;

					jQuery('.loader-wrapper').fadeIn(200);

					jQuery.ajax({
						url        : offer_params.ajax_url,
						type       : "POST",
						dataType   : 'json',
						data       : dataToUpload,
						processData: false,
						contentType: false,
						cache      : false,

						success: function (response) {
							// console.log(response);
							if (response.success == true) {
								if (response.data.html.length > 0) {
									jQuery('#addPhotoElement').before(response.data.html);
								}
								if (response.data.gallery.length > 0) {
									setCookie('deco_add_offer_gallery_images', response.data.gallery, {'expires': 3600});
								}
							}
							try {
								checkUploadList(jQuery('.ad-form .list-photos'));
							} catch (e) {
								console.log(e);
							}
							jQuery('.loader-wrapper').fadeOut(200);
						},

						error: function (e) {
							console.log(e);
							jQuery('.loader-wrapper').fadeOut(200);
						}
					});
				}

			});

		}

	}

	function setCookie(name, value, options) {
		try {
			options = options || {};

			var expires = options.expires;

			if (typeof expires == "number" && expires) {
				var d = new Date();
				d.setTime(d.getTime() + expires * 1000);
				expires = options.expires = d;
			}
			if (expires && expires.toUTCString) {
				options.expires = expires.toUTCString();
			}

			console.log(typeof(value));
			console.log(value);

			value = encodeURIComponent(value);

			var updatedCookie = name + "=" + value;

			for (var propName in options) {
				updatedCookie += "; " + propName;
				var propValue = options[propName];
				if (propValue !== true) {
					updatedCookie += "=" + propValue;
				}
			}

			document.cookie = updatedCookie;
		} catch (e) {
			console.log(e);
		}
	}

	function getCookie(name) {
		if (name.length > 0) {
			var re = new RegExp(name + "=([^;]+)");
			var value = re.exec(document.cookie);
			return (value != null) ? unescape(value[1]) : '';
		} else {
			return '';
		}
	}

	function deleteCookie(name) {
		try {
			setCookie(name, "", {
				expires: -1
			})
		} catch (e) {
			console.log(e);
		}
	}

	function uploadOfferGallery() {
		// console.log(window.jscd);
		switch (window.jscd.browser) {
			case 'Safari':
				offer_gallery_params.runtimes = "flash,html5,html4";
				break;
			default:
				// offer_gallery_params.runtimes = "html5,silverlight,flash,html4";
				offer_gallery_params.runtimes = "gears,html5,flash,browserplus,silverlight,html4";
		}
		// console.log(offer_gallery_params.runtimes);
		var uploader = new plupload.Uploader(offer_gallery_params);

		// a file was added in the queue
		uploader.bind('FilesAdded', function (up, files) {
			var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);
			jQuery('.loader-wrapper').fadeIn(200);
			plupload.each(files, function (file) {
				if (max > hundredmb && file.size > hundredmb && up.runtime != 'html5') {
					Profile.alert(offer_params.messages.imageLargeError, 'Attention', 'error');
				} else {
					// a file was added, you may want to update your DOM here...
					// console.log(file);
				}
			});

			up.refresh();
			up.start();
		});

		uploader.bind('FileUploaded', function (up, file, response) {

			if (!checkUploadList(jQuery('.ad-form .list-photos'))) return;

			if (response.response) {
				console.log(response);
				jQuery('#addPhotoElement').before(response.response);

				// Second check
				checkUploadList(jQuery('.ad-form .list-photos'));
			}

			/*jQuery('body .change_ava_img').css('background-image', 'url(' + response.response + ')');
			 jQuery('body .header-profile__avatar').css('background-image', 'url(' + response.response + ')');*/

			//jQuery('.loader-wrapper').fadeOut(200);

		});

		uploader.bind('UploadComplete', function () {
			jQuery('.loader-wrapper').fadeOut(200);
		});

		uploader.bind('Error', function (e) {
			jQuery('.loader-wrapper').fadeOut(200);
			console.log(e)
		});


		uploader.init();
	}

	jQuery(document).on('change', '#ad-form [name="deal_type"]', function (e) {
		var type = 'arenda';

		if (jQuery(this).attr('id') == 'deal_type-sell') {
			type = 'prodazha';
		}

		apartamentFilterHandler(type);
	});

	function apartamentFilterHandler(deal) {
		var withoutFurnitureField = jQuery('#without_furniture').parents('.input-block');

		if (deal == 'arenda') {
			withoutFurnitureField.show();
		} else {
			withoutFurnitureField.hide();
		}
	}

	// Remove previously downloaded photo
	jQuery(document).on('click', '.btn-remove-elem', function (e) {
		e.preventDefault();

		var attachedImage = jQuery(this).closest('li');

		if (attachedImage.length > 0) {

			var attachmentHolders = attachedImage.siblings('li:not(.add-photo-block)');

			if (attachmentHolders.length > 0) {
				var newGalleryIds = [];
				attachmentHolders.each(function () {
					var singleAttach = jQuery(this).find('.js_modal-slide-item');
					if (singleAttach.length > 0) {
						var singleAttachId = singleAttach.attr('data-img-id');
						if (singleAttachId.length > 0) {
							newGalleryIds.push(singleAttachId);
						}
					}
				});
				if(newGalleryIds.length > 0){
					setCookie('deco_add_offer_gallery_images', newGalleryIds, {'expires': 3600});
				}
			}else {
				deleteCookie('deco_add_offer_gallery_images');
			}

			var attachmentID = attachedImage.find('.js_modal-slide-item').attr('data-img-id');

			if (attachmentID.length > 0) {
				jQuery.ajax({
					url     : offer_params.ajax_url,
					type    : "POST",
					dataType: 'json',
					data    : 'action=deco_add_offer_remove_gallery_item&item_id=' + attachmentID,
					success : function (response) {
						console.log(response);
					},
					error   : function (e) {
						console.log(e);
					}
				});

			}

			attachedImage.remove();
			checkUploadList(jQuery('.ad-form .list-photos'));
		}
	});

	function validateSubmitAd() {
        jQuery("#add_offer-locality-suggest-input").attr("name", "locality");
        jQuery("#add_offer-street-suggest-input").attr("name", "street");
		jQuery(".ad-form").each(function () {
			var form = jQuery(this),
				Validator = jQuery(this).validate({
					debug         : true,
					ignore        : "",  // necessary for hidden checkboxes and other hidden inputs
					rules         : {
						offer_type: {
							number: true
						},
						price     : {
							number: true
						},
						square    : {
							number: true
						},
						// living_area   : {
						// 	number: true
						// },
						// land_area     : {
						// 	number: true
						// },
						// kitchen_area  : {
						// 	number: true
						// },
						// ceiling_height: {
						// 	number: true
						// },
						//land_plot_area: {
						//	number: true
						//},
						// distance      : {
						// 	number: true
						// },
						city      : {
							required: true
						},
                        locality    : {
							required: true
						},
						street    : {
							required: true
						},
						// floor         : {
						// 	floors: true
						// },
						// floors        : {
						// 	floors: true
						// },
						repair    : {
							number: true
						}
					},
					invalidHandler: function (form, validator) {
						var errors = validator.numberOfInvalids();

						if (errors) {
							jQuery('html, body').stop().animate({
								scrollTop: jQuery(validator.errorList[0].element).parent().offset().top - 150 + 'px'
							}, 400);
						}
					},
					errorPlacement: function () {
						return false;
					},
					highlight     : function (element, errorClass, validClass) {
						var elem = jQuery(element);
						// console.log(jQuery(element));

						elem.addClass('invalid');
						// for checkbox
						if (elem.closest('.ckeckbox-inline-label').length) {
							elem.parent().addClass('invalid-container');
							return;
						} else if (elem.siblings('.ckeckbox-inline-label').length) {
							elem.siblings('[for="' + elem.attr('name') + '"]').addClass('invalid-container');
							return;
						}
						elem.parent().addClass('invalid-container');
					},
					unhighlight   : function (element, errorClass, validClass) {
						var elem = jQuery(element);

						elem.removeClass('invalid');
						// for checkbox
						if (elem.closest('.ckeckbox-inline-label').length) {
							elem.parent().removeClass('invalid-container');
							return;
						} else if (elem.siblings('.ckeckbox-inline-label').length) {
							elem.siblings('[for="' + elem.attr('name') + '"]').removeClass('invalid-container');
							return;
						}
						elem.parent().removeClass('invalid-container');
					},
					submitHandler : function (form, event) {
						event.preventDefault();

						var gallery = [],
							data = {},
							formData = jQuery(form).serializeArray();

						jQuery.map(formData, function (n, i) {
							data[n['name']] = n['value'];
						});

						// console.log(data);

						if (jQuery('.offer_gallery_item').length) {
							jQuery('.offer_gallery_item').each(function () {
								var img = jQuery(this);
								gallery.push({
									'id'                : img.attr('data-img-id'),
									'offer_blog'        : img.attr('data-offer-blog-src'),
									'tooltip_tease'     : img.attr('data-tooltip-tease-src'),
									'offer_map_marker'  : img.attr('data-offer-map-marker-src'),
									'offer_single_tease': img.attr('data-offer-single-tease-src'),
									'src'               : img.attr('data-modal-src'),
								});
							});
						}

						data['gallery'] = gallery;

						data['action'] = 'deco_add_offer';
						data['_ajax_offer_nonce'] = offer_params._wpnonce;

						//console.log(data);
						jQuery.ajax({
							url       : offer_params.ajax_url,
							type      : 'POST',
							dataType  : 'json',
							data      : data,
							beforeSend: function () {
								deco.preloader.stop().fadeIn();
							},
							success   : function (data) {
								//console.log(data);
								if (data.message && data.status == 204) {
									deco.preloader.stop().fadeOut();

									Profile.alert(
										'',
										data.message,
										'error',
										'content_html'
									);
								} else if (data.status == 204 || typeof data === 'undefined') {
									if (data.html) {
										jQuery('.offer-section-block').replaceWith(data.html);
									}
									deco.preloader.stop().fadeOut();
								} else {
									if (data.html) {
										jQuery('.offer-section-block').replaceWith(data.html);
									}
									deco.preloader.stop().fadeOut();
								}
								jQuery('html,body').animate({scrollTop: 0}, 500);
								deleteCookie('deco_add_offer_gallery_images');
							},
							error     : function (e) {
								console.log(e);
								deco.preloader.stop().fadeOut();
								deleteCookie('deco_add_offer_gallery_images');
							}
						});

					}
				});
		});
	}

	jQuery.validator.addMethod("floors", function (floor, element) {
		var form = element.closest('form');
		var floorInp = jQuery(form).find('input[name="floor"]');
		var floorsInp = jQuery(form).find('input[name="floors"]');

		if (!floorInp.length || !floorsInp.length || floorInp.val() == '' || floorsInp.val() == '') {
			return true;
		}

		if (floorInp.val() > floorsInp.val()) {
			return false;
		} else {
			return true;
		}
	}, offer_params.messages.siteUrlNotValid);

	function checkUploadList(list) {
		var items = list.find('> li:not(.add-photo-block)'),
			currentMaxNumber = maxNumberPhotos;

		if (items.length >= currentMaxNumber) {
			list.find('.add-photo-block').hide();
			list.siblings('.form-message-inline').show();
			return false;
		} else {
			list.find('.add-photo-block').show();
			list.siblings('.form-message-inline').hide();
			return true;
		}
	}

	function initMainFilters() {
		var container = '.add-offer-change-parameters-block';
		var cat = jQuery('.offer_category_current_first span.current-value').attr('data-filtercategory');
		var template = jQuery('.maincontent').attr('data-template');

		changeOfferType(cat, container, template);

		jQuery(document).on('click', '.choose_filter_cat a', function (e) {
			e.preventDefault();

			//var cat = jQuery(this).attr('data-filtercategory'),
			//		ajaxUrl,
			//		parent = jQuery(this).parents('.filter_tab_item');
			//if (jQuery('main').hasClass('single-ad-form-page')) {
			//	parent = jQuery(this).parents('.ad-form');
			//}
			//parent.find('[data-filtertohide].hidden').removeClass('hidden');
			//
			//if (!jQuery(this).closest('.search-bar').length) {
			//	ajaxUrl = 'mainFilter/category-' + cat + '.html';
			//}

			cat = jQuery(this).attr('data-filtercategory');

			changeOfferType(cat, container, template);
		});

		function changeOfferType(cat, container, template) {

			var galleryFromCookie = getCookie('deco_add_offer_gallery_images');
			var cookieParameter = '';

			try {
				if (galleryFromCookie.length > 0) {
					cookieParameter = '&gallery_from_cookie=' + galleryFromCookie;
				}
			} catch (e) {
				console.log(e);
			}

			jQuery.ajax({
				url       : offer_params.ajax_url,
				type      : "GET",
				dataType  : 'json',
				data      : 'action=deco_change_offer_type&is_front=1&type=' + cat + '&template=' + template + cookieParameter,
				beforeSend: function () {
					jQuery('.loader-wrapper').fadeIn(200);
				},
				success   : function (data) {
					if (data.status == 204 || typeof data === 'undefined') {

					} else {
						var parent = jQuery(this).parents('.filter_tab_item');

						jQuery(container).html(data.html);

						if (data.offer_type) {
							jQuery('.offer-type-select-block').html(data.offer_type);
						}

						if (cat == 'kvartir') {
							var checkedID = jQuery('#ad-form [name="deal_type"]:checked').attr('id'),
								deal = 'arenda';

							if (checkedID == 'deal_type-sell') {
								deal = 'prodazha';
							}

							apartamentFilterHandler(deal);
						}

						fix_zIndex();
						if (parent.find('.slider-range').length) {
							deco.initRangeSlider();
						}
						deco.initTooltips();
						deco.initAdPage();
						validateSubmitAd();

						if (parent.find('.spoil').length) {
							var sp = parent.find('.spoil-trigg');
							sp.on('click', function (e) {
								e.preventDefault();
								e.stopPropagation();
								console.log('click');
								var parent = jQuery(this).parents('.spoil');
								parent.toggleClass('open');
								var spH = parent.find('.spoil-content').height();

								if (parent.hasClass('open')) {
									parent.find('.spoil-wrap').height(spH);
								}
								else {
									parent.find('.spoil-wrap').height(0);
								}
							});
						}

						//uploadOfferGallery();
						addOfferGalleryUpload();
					}

					jQuery('.loader-wrapper').fadeOut(200);
					// deco.initAddressSuggestNavigation();
				}
			});
		}

		jQuery(document).on('click', '.change_filters', function (e) {
			e.preventDefault();
			var parent = jQuery(this).parents('.filters_block'),
				hideFilters = jQuery(this).attr('data-hidefilter'),
				showFilters = jQuery(this).attr('data-showfilter'),
				hideTargets = parent.find('[data-filtertohide]'),
				showTargets = parent.find('[data-filtertoshow]');

			function compareArrays(array1, array2) {
				var array1 = array1.split(' ');
				var array2 = array2.split(' ');
				var hasEqual = false;
				array1.filter(function (n) {
					if (array2.indexOf(n) != -1) {
						hasEqual = true;
					}
					;
				});
				return hasEqual;
			}

			// Set to defaults
			hideTargets.removeClass('hidden');
			showTargets.addClass('hidden');

			if (hideFilters) {
				// Loop throuh elements that must be hidden
				hideTargets.each(function () {
					var targetHideFilters = jQuery(this).data('filtertohide');

					// Look for matches
					if (compareArrays(hideFilters, targetHideFilters)) {
						jQuery(this).addClass('hidden');
					}
				});
			}

			if (showFilters) {
				// Loop throuh elements that must be shown
				showTargets.each(function () {
					var targetShowFilters = jQuery(this).data('filtertoshow');

					// Look for matches
					if (compareArrays(showFilters, targetShowFilters)) {
						jQuery(this).removeClass('hidden');
					}
				});
			}

		});
	}

	if (!jQuery('#ad-form.edit-form').length) {
		initMainFilters();
	} else {
		validateSubmitAd();
	}

	if (jQuery('#ad-form.edit-form').length > 0 && jQuery('#ad-form.edit-form').hasClass('js_edit-offer-form')) {
		console.log('edit offer');

		if (jQuery('#ad-form [data-filtercategory]').attr('data-filtercategory') == 'kvartir') {
			var checkedID = jQuery('#ad-form [name="deal_type"]:checked').attr('id'),
				deal = 'arenda';

			if (checkedID == 'deal_type-sell') {
				deal = 'prodazha';
			}

			apartamentFilterHandler(deal);
		}

		//uploadOfferGallery();
		addOfferGalleryUpload();
	}

});
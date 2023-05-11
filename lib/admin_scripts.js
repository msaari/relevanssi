/* Confirmation for copying options between blogs */

jQuery(document).ready(function ($) {
	$("#copy_config").on("submit", function () {
		var c = confirm(relevanssi.confirm)
		return c //you can just return c because it will be true or false
	})

	$("#removeallstopwords").on("click", function () {
		var c = confirm(relevanssi.confirm_stopwords)
		return c
	})

	$("#delete_query").on("click", function () {
		var c = confirm(relevanssi.confirm_delete_query)
		return c
	})

	$("#list_custom_fields").on("click", function () {
		var results = $("#relevanssi_custom_field_list")
		var data = {
			action: "relevanssi_list_custom_fields",
		}
		jQuery.post(ajaxurl, data, function (response) {
			console.log(response)
			results.html('<p>' + JSON.parse(response) + '</p>')
		})
	})
})

jQuery(document).ready(function ($) {
	$(".color-field").wpColorPicker()

	var txtcol_control = $("#tr_relevanssi_txt_col")
	var bgcol_control = $("#tr_relevanssi_bg_col")
	var class_control = $("#tr_relevanssi_class")
	var css_control = $("#tr_relevanssi_css")

	$("#relevanssi_highlight").on("change", function () {
		txtcol_control.addClass("screen-reader-text")
		bgcol_control.addClass("screen-reader-text")
		class_control.addClass("screen-reader-text")
		css_control.addClass("screen-reader-text")

		if (this.value == "col") txtcol_control.toggleClass("screen-reader-text")
		if (this.value == "bgcol") bgcol_control.toggleClass("screen-reader-text")
		if (this.value == "class") class_control.toggleClass("screen-reader-text")
		if (this.value == "css") css_control.toggleClass("screen-reader-text")
	})

	$("#relevanssi_hilite_title").on("click", function () {
		$("#title_description").toggleClass("screen-reader-text", !this.checked)
	})

	var or_fallback = $("#orfallback")
	$("#relevanssi_implicit_operator").on("change", function () {
		or_fallback.toggleClass("screen-reader-text")
	})

	var index_subscribers = $("#index_subscribers")
	var user_extra_fields = $("#user_extra_fields")
	$("#relevanssi_index_users").on("click", function () {
		$("#user_profile_notice").toggleClass("screen-reader-text", !this.checked)
		index_subscribers.toggleClass("screen-reader-text", !this.checked)
		user_extra_fields.toggleClass("screen-reader-text", !this.checked)
	})

	var taxonomies = $("#taxonomies")
	$("#relevanssi_index_taxonomies").on("click", function () {
		taxonomies.toggleClass("screen-reader-text", !this.checked)
	})

	var post_type_archives = $("#posttypearchives")
	$("#relevanssi_index_post_type_archives").on("click", function () {
		post_type_archives.toggleClass("screen-reader-text", !this.checked)
	})

	var fields_content = $("#index_field_input")
	var fields_select = $("#relevanssi_index_fields_select")
	fields_select.on("change", function () {
		if (this.value == "some") fields_content.show()
		if (this.value != "some") fields_content.hide()
	})

	var index_images = $("#row_index_image_files")
	var index_attachments = $("#relevanssi_index_type_attachment")
	index_attachments.on("click", function () {
		if (this.checked) index_images.show()
		if (!this.checked) index_images.hide()
	})

	$("#show_advanced_indexing").on("click", function (e) {
		$("#advanced_indexing").toggleClass("screen-reader-text")
		$("#hide_advanced_indexing").show()
		$("#show_advanced_indexing").hide()
	})

	$("#hide_advanced_indexing").on("click", function (e) {
		$("#advanced_indexing").toggleClass("screen-reader-text")
		$("#show_advanced_indexing").show()
		$("#hide_advanced_indexing").hide()
	})

	$("#indexing_tab :input").on("change", function (e) {
		$("#build_index").attr("disabled", "disabled")
		var relevanssi_note = $("#relevanssi-note")
		relevanssi_note.show()
		relevanssi_note.html('<p class="description important">' + relevanssi.options_changed + '</p>')
	})

	$("#relevanssi_default_orderby").on("change", function (e) {
		if (this.value == "post_date") {
			$("#relevanssi_throttle").prop("checked", false)
		}
		$("#throttle_disabled").toggleClass("screen-reader-text")
		$("#throttle_enabled").toggleClass("screen-reader-text")
	})

	$("#relevanssi_show_pdf_errors").on("click", function (e) {
		var error_box = $("#relevanssi_pdf_errors")
		error_box.toggle()
		var data = {
			action: "relevanssi_get_pdf_errors",
		}
		jQuery.post(ajaxurl, data, function (response) {
			error_box.val(JSON.parse(response))
		})
	})

	$("#relevanssi_excerpts").on("click", function () {
		$("#relevanssi_breakdown").toggleClass("relevanssi_disabled", !this.checked)
		$("#relevanssi_highlighting").toggleClass(
			"relevanssi_disabled",
			!this.checked
		)
		$("#tr_excerpt_custom_fields").toggleClass(
			"relevanssi_disabled",
			!this.checked
		)
		$("#tr_excerpt_allowable_tags").toggleClass(
			"relevanssi_disabled",
			!this.checked
		)
		$("#tr_excerpt_length").toggleClass("relevanssi_disabled", !this.checked)
		$("#tr_max_excerpts").toggleClass("relevanssi_disabled", !this.checked)
		$("#relevanssi_excerpt_length").attr("disabled", !this.checked)
		$("#relevanssi_excerpt_type").attr("disabled", !this.checked)
		$("#relevanssi_max_excerpts").attr("disabled", !this.checked)
		$("#relevanssi_excerpt_allowable_tags").attr("disabled", !this.checked)
		$("#relevanssi_excerpt_custom_fields").attr("disabled", !this.checked)
		$("#relevanssi_highlight").attr("disabled", !this.checked)
		$("#relevanssi_txt_col").attr("disabled", !this.checked)
		$("#relevanssi_bg_col").attr("disabled", !this.checked)
		$("#relevanssi_css").attr("disabled", !this.checked)
		$("#relevanssi_class").attr("disabled", !this.checked)
		$("#relevanssi_hilite_title").attr("disabled", !this.checked)
		$("#relevanssi_highlight_docs").attr("disabled", !this.checked)
		$("#relevanssi_highlight_comments").attr("disabled", !this.checked)
		$("#relevanssi_show_matches").attr("disabled", !this.checked)
		$("#relevanssi_show_matches_text").attr("disabled", !this.checked)
		$("#relevanssi_expand_highlights").attr("disabled", !this.checked)
	})

	$("#relevanssi_excerpt_custom_fields").on("change", function () {
		$("#relevanssi_excerpt_specific_fields").attr("disabled", !this.checked)
	})

	$("#relevanssi_searchblogs_all").on("click", function () {
		$("#relevanssi_searchblogs").attr("disabled", this.checked)
	})

	var min_word_length = $("#relevanssi_min_word_length")
	min_word_length.on("change", function(e) {
		if ( min_word_length.val() < 1 ) {
			min_word_length.val(1)
		}
		if ( min_word_length.val() > 9 ) {
			min_word_length.val(9)
		}
	})
})

var time = 0
var intervalID = 0

function relevanssiUpdateClock() {
	time++
	var time_formatted = rlv_format_time(Math.round(time))
	document.getElementById("relevanssi_elapsed").innerHTML = time_formatted
}

jQuery(document).ready(function ($) {
	$("#continue_indexing").on("click", function () {
		$("#relevanssi-progress").show()
		$("#results").show()
		$("#relevanssi-timer").show()
		$("#stateoftheindex").html(relevanssi.reload_state)
		$("#indexing_button_instructions").hide()
		var results = document.getElementById("results")
		results.value = ""

		intervalID = window.setInterval(relevanssiUpdateClock, 1000)

		var data = {
			action: "relevanssi_count_missing_posts",
		}
		console.log("Counting posts.")
		results.value += relevanssi.counting_posts + " "
		jQuery.post(ajaxurl, data, function (response) {
			count_response = JSON.parse(response)
			console.log("Counted " + count_response + " posts.")
			results.value += count_response + " " + relevanssi.posts_found + "\n"
			if (count_response > 0) {
				var args = {
					completed: 0,
					total: count_response,
					offset: 0,
					total_seconds: 0,
					limit: relevanssi_params.indexing_limit,
					adjust: relevanssi_params.indexing_adjust,
					extend: true,
					security: nonce.indexing_nonce,
				}
				process_indexing_step(args)
			} else {
				clearInterval(intervalID)
			}
		})
	})
})

function process_indexing_step(args) {
	// console.log(args.completed + " / " + args.total);
	var t0 = performance.now()
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: {
			action: "relevanssi_index_posts",
			completed: args.completed,
			total: args.total,
			offset: args.offset,
			limit: args.limit,
			adjust: args.adjust,
			extend: args.extend,
			security: args.security,
		},
		dataType: "json",
		success: function (response) {
			console.log(response)
			if (response.completed == "done") {
				//console.log("response " + parseInt(response.total_posts));
				var results_textarea = document.getElementById("results")
				results_textarea.value += response.feedback

				document.getElementById("relevanssi_estimated").innerHTML =
					relevanssi.notimeremaining

				var hidden_posts = args.total - parseInt(response.total_posts)
				results_textarea.value +=
					relevanssi.indexing_complete +
					" " +
					hidden_posts +
					" " +
					relevanssi.excluded_posts
				results_textarea.scrollTop = results_textarea.scrollHeight
				jQuery(".rpi-progress div").animate(
					{
						width: response.percentage + "%",
					},
					50,
					function () {
						// Animation complete.
					}
				)

				clearInterval(intervalID)
			} else {
				var t1 = performance.now()
				var time_seconds = (t1 - t0) / 1000
				time_seconds = Math.round(time_seconds * 100) / 100
				args.total_seconds += time_seconds

				var estimated_time = rlv_format_approximate_time(
					Math.round(
						(args.total_seconds / response.percentage) * 100 -
							args.total_seconds
					)
				)

				document.getElementById(
					"relevanssi_estimated"
				).innerHTML = estimated_time

				if (args.adjust) {
					if (time_seconds < 2) {
						args.limit = args.limit * 2
						// current limit can be indexed in less than two seconds; double the limit
					} else if (time_seconds < 5) {
						args.limit += 5
						// current limit can be indexed in less than five seconds; up the limit
					} else if (time_seconds > 20) {
						args.limit = Math.round(args.limit / 2)
						if (args.limit < 1) args.limit = 1
						// current limit takes more than twenty seconds; halve the limit
					} else if (time_seconds > 10) {
						args.limit -= 5
						if (args.limit < 1) args.limit = 1
						// current limit takes more than ten seconds; reduce the limit
					}
				}

				var results_textarea = document.getElementById("results")
				results_textarea.value += response.feedback
				results_textarea.scrollTop = results_textarea.scrollHeight
				var percentage_rounded = Math.round(response.percentage)

				jQuery(".rpi-progress div").animate(
					{
						width: percentage_rounded + "%",
					},
					50,
					function () {
						// Animation complete.
					}
				)
				//console.log("Next step.");
				var new_args = {
					completed: parseInt(response.completed),
					total: args.total,
					offset: response.offset,
					total_seconds: args.total_seconds,
					limit: args.limit,
					adjust: args.adjust,
					extend: args.extend,
					security: args.security,
				}

				process_indexing_step(new_args)
			}
		},
	})
}

function rlv_format_time(total_seconds) {
	var hours = Math.floor(total_seconds / 3600)
	var minutes = Math.floor((total_seconds - hours * 3600) / 60)
	var seconds = total_seconds - hours * 3600 - minutes * 60

	if (minutes < 10) minutes = "0" + minutes
	if (seconds < 10) seconds = "0" + seconds

	return hours + ":" + minutes + ":" + seconds
}

function rlv_format_approximate_time(total_seconds) {
	var hours = Math.floor(total_seconds / 3600)
	var minutes = Math.floor(total_seconds / 60)
	var seconds = total_seconds - hours * 3600 - minutes * 60

	var time = ""
	if (minutes > 99) {
		hour_word = relevanssi.hours
		if (hours == 1) hour_word = relevanssi.hour
		time = relevanssi.about + " " + hours + " " + hour_word
	}
	if (minutes > 79 && minutes < 100) time = relevanssi.ninety_min
	if (minutes > 49 && minutes < 80) time = relevanssi.sixty_min
	if (minutes < 50) {
		if (seconds > 30) minutes += 1
		minute_word = relevanssi.minutes
		if (minutes == 1) minute_word = relevanssi.minute
		time = relevanssi.about + " " + minutes + " " + minute_word
	}
	if (minutes < 1) time = relevanssi.underminute

	return time
}

jQuery(document).ready(function ($) {
	$("#search").on("click", function (e) {
		var results = document.getElementById("results")
		results.innerHTML = "Searching..."
		e.preventDefault()
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "relevanssi_admin_search",
				args: document.getElementById("args").value,
				posts_per_page: document.getElementById("posts_per_page").value,
				post_types: document.getElementById("post_types").value,
				s: document.getElementById("s").value,
				security: nonce.searching_nonce,
			},
			dataType: "json",
			success: function (response) {
				results.innerHTML = response
			},
		})
	})

	// Show the filters on the "Admin search" page.
	$(document).on("click", "#show_filters", function (e) {
		$("#relevanssi_filter_list").toggle()
		$("#show_filters").toggle()
		$("#hide_filters").toggle()
	})

	// Hide the filters on the "Admin search" page.
	$(document).on("click", "#hide_filters", function (e) {
		$("#relevanssi_filter_list").toggle()
		$("#show_filters").toggle()
		$("#hide_filters").toggle()
	})

	$(document).on("click", "#next_page", function (e) {
		e.preventDefault()
		var results = document.getElementById("results")
		var offset = parseInt(document.getElementById("offset").innerHTML)
		var posts = parseInt(document.getElementById("posts_per_page").value)
		results.innerHTML = "Searching..."
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "relevanssi_admin_search",
				args: document.getElementById("args").value,
				posts_per_page: posts,
				s: document.getElementById("s").value,
				offset: offset,
				security: nonce.searching_nonce,
			},
			dataType: "json",
			success: function (response) {
				results.innerHTML = response
			},
		})
	})

	$(document).on("click", "#prev_page", function (e) {
		e.preventDefault()
		var results = document.getElementById("results")
		var offset = parseInt(document.getElementById("offset").innerHTML)
		var posts = parseInt(document.getElementById("posts_per_page").value)
		offset = offset - posts - posts
		if (offset < 0) offset = 0
		results.innerHTML = "Searching..."
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "relevanssi_admin_search",
				args: document.getElementById("args").value,
				posts_per_page: document.getElementById("posts_per_page").value,
				s: document.getElementById("s").value,
				offset: offset,
				security: nonce.searching_nonce,
			},
			dataType: "json",
			success: function (response) {
				results.innerHTML = response
			},
		})
	})

	$(document).on("click", ".pin", function (e) {
		e.preventDefault()
		var keyword = e.target.dataset.keyword
		var post_id = e.target.dataset.postid
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "relevanssi_pin_post",
				keyword,
				post_id,
				security: nonce.searching_nonce,
			},
			dataType: "json",
			success: function (response) {
				var results = document.getElementById("results")
				results.innerHTML = "Searching..."
				e.preventDefault()
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {
						action: "relevanssi_admin_search",
						args: document.getElementById("args").value,
						posts_per_page: document.getElementById("posts_per_page").value,
						s: document.getElementById("s").value,
						security: nonce.searching_nonce,
					},
					dataType: "json",
					success: function (response) {
						results.innerHTML = response
					},
				})
			},
		})
	})

	$(document).on("click", ".unpin", function (e) {
		e.preventDefault()
		var keyword = e.target.dataset.keyword
		var post_id = e.target.dataset.postid
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "relevanssi_unpin_post",
				keyword,
				post_id,
				security: nonce.searching_nonce,
			},
			dataType: "json",
			success: function (response) {
				var results = document.getElementById("results")
				results.innerHTML = "Searching..."
				e.preventDefault()
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					data: {
						action: "relevanssi_admin_search",
						args: document.getElementById("args").value,
						posts_per_page: document.getElementById("posts_per_page").value,
						s: document.getElementById("s").value,
						security: nonce.searching_nonce,
					},
					dataType: "json",
					success: function (response) {
						results.innerHTML = response
					},
				})
			},
		})
	})
})

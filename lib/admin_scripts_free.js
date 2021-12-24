jQuery(document).ready(function ($) {
	$("#build_index").click(function () {
		$("#relevanssi-progress").show()
		$("#results").show()
		$("#relevanssi-timer").show()
		$("#relevanssi-indexing-instructions").show()
		$("#stateoftheindex").html(relevanssi.reload_state)
		$("#indexing_button_instructions").hide()
		var results = document.getElementById("results")
		results.value = ""

		var data = {
			action: "relevanssi_truncate_index",
			security: nonce.indexing_nonce,
		}

		intervalID = window.setInterval(relevanssiUpdateClock, 1000)

		console.log("Truncating index.")
		results.value += relevanssi.truncating_index + " "
		jQuery.post(ajaxurl, data, function (response) {
			truncate_response = JSON.parse(response)
			console.log("Truncate index: " + truncate_response)
			if (truncate_response == true) {
				results.value += relevanssi.done + "\n"
			}

			var data = {
				action: "relevanssi_count_posts",
			}
			console.log("Counting posts.")
			results.value += relevanssi.counting_posts + " "
			jQuery.post(ajaxurl, data, function (response) {
				count_response = JSON.parse(response)
				console.log("Counted " + count_response + " posts.")
				var post_total = parseInt(count_response)
				results.value += count_response + " " + relevanssi.posts_found + "\n"

				var args = {
					completed: 0,
					total: post_total,
					offset: 0,
					total_seconds: 0,
					limit: relevanssi_params.indexing_limit,
					adjust: relevanssi_params.indexing_adjust,
					extend: false,
					security: nonce.indexing_nonce,
				}
				process_indexing_step(args)
			})
		})
	})
})

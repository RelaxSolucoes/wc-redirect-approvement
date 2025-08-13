/* global WCSPPR */
(function() {
	if (!window.WCSPPR || !WCSPPR.orderId) {
		return;
	}

	var attempts = 0;
	var maxAttempts = 120; // ~10 minutos se intervalMs=5000
	var intervalMs = 5000;

	function check() {
		attempts++;
		var url = WCSPPR.ajaxUrl + '?action=wcsppr_check_order_status&order_id=' + encodeURIComponent(WCSPPR.orderId) + '&key=' + encodeURIComponent(WCSPPR.orderKey) + '&nonce=' + encodeURIComponent(WCSPPR.nonce);
		fetch(url, { credentials: 'same-origin' })
			.then(function(r){ return r.json(); })
			.then(function(data){
				if (data && (data.status === 'processing' || data.status === 'completed')) {
					clearInterval(timerId);
					if (WCSPPR.message) {
						// Alerta simples; pode ser trocado por banner customizado no futuro
						alert(WCSPPR.message);
					}
					setTimeout(function(){
						window.location.href = WCSPPR.redirectUrl;
					}, WCSPPR.waitMs);
				}
			})
			.catch(function(){ /* ignora falhas pontuais */ });

		if (attempts >= maxAttempts) {
			clearInterval(timerId);
		}
	}

	var timerId = setInterval(check, intervalMs);
})();



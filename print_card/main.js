document.addEventListener("DOMContentLoaded", function () {
	// Загружаем панель управления
	fetch("controls.html")
		.then(response => response.text())
		.then(html => {
			document.body.insertAdjacentHTML("beforeend", html);
			initControls();
		})
		.catch(error => console.error("Ошибка загрузки controls.html:", error));

	function initControls() {
		const card = document.querySelector("#card");
		const shop = card.getAttribute("data-shop");
		const type = card.getAttribute("data-card-type");
		const isImage = type === 'image';

		// Лого
		document.getElementById("logo-plus").addEventListener("click", () => updateSize("#logo img", "height", 1.05));
		document.getElementById("logo-minus").addEventListener("click", () => updateSize("#logo img", "height", 0.95));
		document.getElementById("logo-toggle").addEventListener("click", () => toggleVisibility("#logo"));
		document.getElementById("logo-up").addEventListener("click", () => updateTransform("#logo", "translateY", -5));
		document.getElementById("logo-down").addEventListener("click", () => updateTransform("#logo", "translateY", 5));

		// Заголовок
		document.getElementById("title-plus").addEventListener("click", () => updateSize("#title", "fontSize", 1.05));
		document.getElementById("title-minus").addEventListener("click", () => updateSize("#title", "fontSize", 0.95));
		document.getElementById("title-toggle").addEventListener("click", () => toggleVisibility("#title_content"));
		document.getElementById("title-up").addEventListener("click", () => updateTransform("#title_content", "translateY", -5));
		document.getElementById("title-down").addEventListener("click", () => updateTransform("#title_content", "translateY", 5));

		// Наш текст
		document.getElementById("our-text-plus").addEventListener("click", () => updateSize("#our_text", isImage ? "width" : "fontSize", 1.05));
		document.getElementById("our-text-minus").addEventListener("click", () => updateSize("#our_text", isImage ? "width" : "fontSize", 0.95));
		document.getElementById("our-text-toggle").addEventListener("click", () => toggleVisibility("#our_text"));
		document.getElementById("our-text-up").addEventListener("click", () => updateTransform("#our_text", "translateY", -5));
		document.getElementById("our-text-down").addEventListener("click", () => updateTransform("#our_text", "translateY", 5));

		// Текст клиента
		document.getElementById("client-text-plus").addEventListener("click", () => updateSize("#customer_text", "fontSize", 1.05));
		document.getElementById("client-text-minus").addEventListener("click", () => updateSize("#customer_text", "fontSize", 0.95));
		document.getElementById("client-text-toggle").addEventListener("click", () => toggleVisibility("#customer_text"));
		document.getElementById("client-text-up").addEventListener("click", () => updateTransform("#customer_text", "translateY", -5));
		document.getElementById("client-text-down").addEventListener("click", () => updateTransform("#customer_text", "translateY", 5));

		// QR-коды
		document.getElementById("qr-codes-plus").addEventListener("click", () => updateTransform("#qr_codes", "scale", 1.05));
		document.getElementById("qr-codes-minus").addEventListener("click", () => updateTransform("#qr_codes", "scale", 0.95));
		document.getElementById("qr-codes-toggle").addEventListener("click", () => toggleVisibility("#qr_codes"));
		document.getElementById("qr-codes-up").addEventListener("click", () => updateTransform("#qr_codes", "translateY", -5));
		document.getElementById("qr-codes-down").addEventListener("click", () => updateTransform("#qr_codes", "translateY", 5));

		// Отступы
		document.getElementById("padding-plus").addEventListener("click", () => updateSize("#card", "padding", 1.05));
		document.getElementById("padding-minus").addEventListener("click", () => updateSize("#card", "padding", 0.95));
	}

	// **Функция для изменения размера (шрифта, паддинга, высоты)**
	function updateSize(selector, property, factor) {
		const element = document.querySelector(selector);
		if (!element) return;
		let currentValue = parseFloat(window.getComputedStyle(element)[property]);
		element.style[property] = `${currentValue * factor}px`;
	}

	// **Функция для обновления transform без перезаписи**
	function updateTransform(selector, type, value) {
		const element = document.querySelector(selector);
		if (!element) return;

		// Достаем текущие transform-значения
		let transform = element.style.transform || "";
		let values = {
			scale: 1,
			translateY: 0,
		};

		// Разбираем существующий transform
		transform.split(" ").forEach(part => {
			if (part.startsWith("scale")) values.scale = parseFloat(part.match(/[\d.]+/)[0]);
			if (part.startsWith("translateY")) values.translateY = parseFloat(part.match(/-?\d+/)[0]);
		});

		// Обновляем нужное значение
		if (type === "scale") values.scale *= value;
		if (type === "translateY") values.translateY += value;

		// Применяем новый transform
		element.style.transform = `scale(${values.scale}) translateY(${values.translateY}px)`;
	}

	// **Функция для скрытия/показа элементов**
	function toggleVisibility(selector) {
		const element = document.querySelector(selector);
		if (!element) return;
		element.style.display = element.style.display === "none" ? "block" : "none";
	}
});

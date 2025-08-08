/**
 * @param {boolean} bool
 */
function checkAll(bool) {
	document.querySelectorAll('#multi_form input[type="checkbox"]')
		.forEach(input => { input.checked = bool; });
}

/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
 *
 * Active-section tracking and smooth scrolling for the module
 * documentation admin tab (GitBook-like layout).
 * Generated from team-ai-config skeleton — no placeholder needed here.
 */

document.addEventListener("DOMContentLoaded", function () {
	const content = document.getElementById("aidoc-content");
	const sidebar = document.getElementById("aidoc-sidebar");
	if (!content || !sidebar) {
		return;
	}

	const links = sidebar.querySelectorAll("a[data-section]");
	const sections = [];
	links.forEach(function (link) {
		const target = document.getElementById(link.getAttribute("data-section"));
		if (target) {
			sections.push({ link: link, target: target });
		}
	});

	function getRelativeTop(el) {
		return el.getBoundingClientRect().top - content.getBoundingClientRect().top;
	}

	function updateActive() {
		let current = null;
		const threshold = 80;
		sections.forEach(function (s) {
			const top = getRelativeTop(s.target);
			if (top <= threshold) {
				current = s;
			}
		});
		links.forEach(function (l) {
			l.classList.remove("active");
		});
		if (current) {
			current.link.classList.add("active");
			const linkRect = current.link.getBoundingClientRect();
			const sidebarRect = sidebar.getBoundingClientRect();
			if (linkRect.top < sidebarRect.top || linkRect.bottom > sidebarRect.bottom) {
				current.link.scrollIntoView({ block: "nearest", behavior: "smooth" });
			}
		}
	}

	content.addEventListener("scroll", updateActive);
	updateActive();

	links.forEach(function (link) {
		link.addEventListener("click", function (e) {
			e.preventDefault();
			const id = this.getAttribute("data-section");
			const target = document.getElementById(id);
			if (target) {
				const top = target.getBoundingClientRect().top - content.getBoundingClientRect().top + content.scrollTop;
				content.scrollTo({ top: top - 20, behavior: "smooth" });
			}
		});
	});
});

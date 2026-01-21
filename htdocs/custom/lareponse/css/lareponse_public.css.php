<?php
header('Content-type: text/css');
?>

:root {
	--bg-color: #ededed;
}

body {
	font-family: Roboto, Arial, Tahoma, Verdana, Helvetica, sans-serif;
	margin: 0;
	background-color: var(--bg-color);
	min-height: 100vh;
	display: flex;
	flex-direction: column;
}

#id-top {
	display: flex;
	flex-grow: 1;
	width: 100%;
	box-shadow: rgba(149, 157, 165, 0.2) 0 8px 24px;
}

.public_article_header {
	padding: 1%;
	width: 100%;
	justify-content: space-between;
	text-align: center;
}
.public_article_header > img {
	max-height: 6vh;
	margin-right: auto;
	margin-left: auto;
}

.border-div {
	width: 5%;
	min-height: 1em;
	display: inline-block;
}

#border-div-left { float: left; }
#border-div-right { float: right; }

#title-div {
	width: 70%;
	display: inline-block;
}
#title-div > h1 {
	font-size: 2em;
}

.container {
	justify-content: center;
	display: flex;
	flex-grow: 10;
}

.fiche {
	border: thin solid white;
	background-color: #fafafa;
	border-radius: 5px;
	width: 90vw;
	padding: 1%;
	margin: 2%;
	box-shadow: rgba(149, 157, 165, 0.2) 0 8px 24px;
}

#ToC {
	border: thin solid white;
	background-color: #fafafa;
	border-radius: 5px;
	box-shadow: rgba(149, 157, 165, 0.2) 0 8px 24px;
	margin: 2% 2% 2% 0px;
}

.iframe {
	display: flex;
	flex-direction: row-reverse;
}
.iframe .fa-expand-alt {
	position: fixed;
	padding: 10px;
}
.iframe .fa-compress-alt {
	z-index: 1;
	position: fixed;
	top: 10px;
	right: 10px;
}

.fiche img {
	max-width: 100%;
	height: auto !important;
}

.public_article_footer {
	justify-content: center;
	font-size: 1em;
	height: 5em;
	padding: 0.5%;
	display: flex;
	flex-grow: 1;
	bottom: 0;
}
.public_article_footer > #inbetween-footer {
	width: 20%;
	height: 100%;
}
.public_article_footer p {
	display: inline-block;
	padding-top: 0.9em;
	float: right;
}

iframe.iframe-fullscreen {
	width: 100%;
	height: 100%;
	position: absolute;
	top: 0;
	left: 0;
	background: white;
}

.container {
	.lareponse_article {
		order: -1;
		h1,h2,h3,h4,h5,h6 {
			scroll-margin-top: 55px !important;
		}
	}

    #ToC > * {
        top: 20px;
    }

	#ToC, .lareponse_article {
		padding: 10px;
		border: thin solid var(--border-color-light);
	}

}

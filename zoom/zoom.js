/*
	This is a modified version of Lightbox for www.macrabbit.com by Jan Van Boghout. Original license:
	
	Lightbox JS: Fullsize Image Overlays 
	by Lokesh Dhakar - http://huddletogether.com/projects/lightbox/

	Licensed under the Creative Commons Attribution 2.5 License - http://creativecommons.org/licenses/by/2.5/
	(basically, do anything you want, just leave my name and link)
*/

var zoomAnimationWaitTimer = null, zoomAnimationTimer = null, zoomAnimationFrame = 0;
var isFirstZoom = true;



function ipccp_getScrollY() {

	if (self.pageYOffset) { return self.pageYOffset; }
	else if (document.documentElement && document.documentElement.scrollTop) { return document.documentElement.scrollTop; }
	else if (document.body) { return document.body.scrollTop; }

	return 0;

}


function ipccp_getPageSize() {
	var xScroll, yScroll;
	if (window.innerHeight && window.scrollMaxY) {	
		xScroll = document.body.scrollWidth;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	var windowWidth, windowHeight;
	if (self.innerHeight) {	// all except Explorer
		windowWidth = self.innerWidth;
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}	
	pageHeight = (yScroll < windowHeight) ? windowHeight : yScroll;
	pageWidth = (xScroll < windowWidth) ? windowWidth : xScroll;
	ipccp_arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight) 
	return ipccp_arrayPageSize;
}


function ipccp_pause(numberMillis) {
	var now = new Date();
	var exitTime = now.getTime() + numberMillis;
	while (true) {
		now = new Date();
		if (now.getTime() > exitTime)
			return;
	}
}



function ipccp_zoomKeyDown(e) {
	var Esc = (window.event) ? 27 : e.DOM_VK_ESCAPE;
	var c = (window.event) ? event.keyCode : e.keyCode;
	if (c == Esc) { ipccp_hideZoom(); }
}



//
// ipccp_showZoom()
// Preloads images. Places new image in lightbox then centers and displays.
//

function ipccp_showZoom(ipccp_objLink)

{
	// prep objects
	var ipccp_overlay = document.getElementById('ipccp_overlay');
	var ipccp_zoomer = document.getElementById('ipccp_zoom');
	var ipccp_zoomedImage = document.getElementById('ipccp_zoom-image');
	var ipccp_loadIndicator = document.getElementById('ipccp_zoom-load');
	var ipccp_arrayPageSize = ipccp_getPageSize();
	var scrollY = ipccp_getScrollY();
	
	// set height of Overlay to take up whole page and show
	ipccp_overlay.style.height = (ipccp_arrayPageSize[1] + 'px');
	ipccp_overlay.style.display = 'block';

	// center loadingImage if it exists
	if (ipccp_loadIndicator) {
		ipccp_loadIndicator.style.top = (scrollY + ((ipccp_arrayPageSize[3] - 35 - 48) / 2) + 'px');
		ipccp_loadIndicator.style.left = (((ipccp_arrayPageSize[0] - 20 - 48) / 2) + 'px');
	}

	// Preload image
	var ipccp_zoomPreload = new Image();
	ipccp_zoomPreload.onload = function() {
		// No need to animate anymore
		clearInterval(zoomAnimationWaitTimer);
		clearInterval(zoomAnimationTimer);

		if (ipccp_loadIndicator)
			ipccp_loadIndicator.style.display = 'none';
			
		// Center zoomed image
		var y = scrollY + ((ipccp_arrayPageSize[3] - 35 - ipccp_zoomPreload.height) / 2);
		var x = ((ipccp_arrayPageSize[0] - 20 - ipccp_zoomPreload.width) / 2);
		ipccp_zoomer.style.top = (y < 0) ? '0' : y + 'px';
		ipccp_zoomer.style.left = (x < 0) ? '0' : x + 'px';
		
		// Show/hide caption
		var ipccp_captionContainer = document.getElementById('ipccp_zoom-captioncontainer');
		if (ipccp_captionContainer != null) {
			if (ipccp_objLink.getAttribute('title_zoomed')) {
				ipccp_captionContainer.style.display = 'block';
				document.getElementById('ipccp_zoom-caption').innerHTML = ipccp_objLink.getAttribute('title_zoomed');
			} else
				ipccp_captionContainer.style.display = 'none';
		}

		// After image is loaded, update the overlay height as the new image might have increased the overall page height.
		ipccp_arrayPageSize = ipccp_getPageSize();
		ipccp_overlay.style.height = (ipccp_arrayPageSize[1] + 'px');

		// Listen to escape
		document.onkeypress = ipccp_zoomKeyDown;
		// Actually set the image
		if (navigator.appVersion.indexOf("MSIE")!=-1) // Small pause between the image loading and displaying prevents flicker in IE.
			ipccp_pause(250);
		ipccp_zoomedImage.src = ipccp_objLink.href;
		ipccp_zoomer.style.display = 'block';
		return false;
	}

	if (document.getElementById('ipccp_zoom-load') != null)
		zoomAnimationWaitTimer = setInterval(ipccp_delayAnimateLoad, 500);
		
	// Show the ipccp_zoomer somewhere offscreen to preload its images
	if (isFirstZoom == true) {
		ipccp_zoomer.style.top = '-10000px';
		ipccp_zoomer.style.display = 'block';
		isFirstZoom = false;
	}
	ipccp_zoomPreload.src = ipccp_objLink.href;
}

function ipccp_delayAnimateLoad()
{
	clearInterval(zoomAnimationWaitTimer);

	document.getElementById('ipccp_zoom-load').style.display = 'block';
	zoomAnimationTimer = setInterval(ipccp_animateLoad, 66);
}

function ipccp_animateLoad()
{
	var ipccp_loadIndicator = document.getElementById('ipccp_zoom-load');
	ipccp_loadIndicator.style.backgroundPosition = '0 -'+(zoomAnimationFrame * 48)+'px';
	zoomAnimationFrame = (zoomAnimationFrame + 1) % 12;
}


function ipccp_hideZoom()
{
	clearInterval(zoomAnimationWaitTimer);
	clearInterval(zoomAnimationTimer);
	var ipccp_loadIndicator = document.getElementById('ipccp_zoom-load');
	if (ipccp_loadIndicator != null)
		ipccp_loadIndicator.style.display = 'none';
	document.getElementById('ipccp_overlay').style.display = 'none';
	document.getElementById('ipccp_zoom').style.display = 'none';
	document.onkeypress = '';
}



function ipccp_setupZoom()
{
	if (!document.getElementsByTagName) { return; }

	// First, load the Zooming style sheet
	var ipccp_zoomStyleSheet = document.createElement("link");
	ipccp_zoomStyleSheet.setAttribute('rel','stylesheet');
	ipccp_zoomStyleSheet.setAttribute('type','text/css');
	ipccp_zoomStyleSheet.setAttribute('href','/wordpress/wp-content/plugins/ipccp/zoom/zoom.css');
	var ipccp_head = document.getElementsByTagName("head").item(0);
	ipccp_head.appendChild(ipccp_zoomStyleSheet);

	// Now, find all anchors that are zoomable
	var anchs = document.getElementsByTagName("area");
	for (var i=0; i<anchs.length; i++) {
		var anch = anchs[i];
		if ( anch.getAttribute("href")  && (anch.getAttribute("rel") == "ipccp") ){
			anch.onclick = function () { ipccp_showZoom(this); return false; }
		}
	}

	
	var ipccp_objBody = document.getElementsByTagName("body").item(0);
	
	// create overlay div and hardcode some functional styles (aesthetic styles are in CSS file)
	var ipccp_overlay = document.createElement("div");
	ipccp_overlay.setAttribute('id','ipccp_overlay');
	ipccp_overlay.onclick = function () {ipccp_hideZoom(); return false;}
	ipccp_overlay.style.display = 'none';
	ipccp_overlay.style.position = 'absolute';
	ipccp_overlay.style.top = '0';
	ipccp_overlay.style.left = '0';
	ipccp_overlay.style.zIndex = '90';
 	ipccp_overlay.style.width = '100%';
	ipccp_objBody.appendChild(ipccp_overlay);
	
	var ipccp_arrayPageSize = ipccp_getPageSize();

	// Preload and create loader image
	var ipccp_loadPreloader = new Image();	
	ipccp_loadPreloader.onload=function(){

		var ipccp_objLoadingImageLink = document.createElement("a");
		ipccp_objLoadingImageLink.setAttribute('href','#');
		ipccp_objLoadingImageLink.onclick = function () {ipccp_hideZoom(); return false;}
		ipccp_overlay.appendChild(ipccp_objLoadingImageLink);
		
		var ipccp_loadIndicator = document.createElement("span");
		ipccp_loadIndicator.setAttribute('id','ipccp_zoom-load');
		ipccp_loadIndicator.style.position = 'absolute';
		ipccp_loadIndicator.style.zIndex = '150';
		ipccp_objLoadingImageLink.appendChild(ipccp_loadIndicator);

		ipccp_loadPreloader.onload=function(){};	//	clear onLoad, as IE will flip out w/animated gifs

		return false;
	}
	ipccp_loadPreloader.src = '/wordpress/wp-content/plugins/ipccp/zoom/ZoomProgress.png';
	
	// Create the shadow elements (someone would get a heart attack if this was in the regular HTML)
	var ipccp_zoomContainer = document.createElement("div");
	ipccp_zoomContainer.setAttribute('id','ipccp_zoom');
	ipccp_objBody.insertBefore(ipccp_zoomContainer, ipccp_overlay.nextSibling);

	// Top shadow
	var topShadow = document.createElement("div");
	topShadow.setAttribute('class','top');
	topShadow.appendChild(document.createElement("div"));
	ipccp_zoomContainer.appendChild(topShadow);

	// Inner shadow
	var ipccp_innerOne = document.createElement("div");
	var ipccp_innerTwo = document.createElement("div");
	var ipccp_innerThree = document.createElement("div");
	var ipccp_contentContainer = document.createElement("div");
	ipccp_innerOne.setAttribute('class','i1');
	ipccp_innerTwo.setAttribute('class','i2');
	ipccp_innerThree.setAttribute('class','i3');
	ipccp_contentContainer.setAttribute('id','ipccp_zoom-content');

	ipccp_innerThree.appendChild(ipccp_contentContainer);
	ipccp_innerTwo.appendChild(ipccp_innerThree);
	ipccp_innerOne.appendChild(ipccp_innerTwo);
	ipccp_zoomContainer.appendChild(ipccp_innerOne);
	
	// Bottom shadow
	var ipccp_bottomShadow = document.createElement("div");
	ipccp_bottomShadow.setAttribute('class','bottom');
	ipccp_bottomShadow.appendChild(document.createElement("div"));
	ipccp_zoomContainer.appendChild(ipccp_bottomShadow);

	// Close button
	var ipccp_closeButton = document.createElement("a");
	ipccp_closeButton.setAttribute('id','ipccp_zoom-close');
	ipccp_closeButton.setAttribute('href','#');
	ipccp_closeButton.setAttribute('title','Click to close');
	ipccp_closeButton.onclick = function () { ipccp_hideZoom(); return false; }
	ipccp_contentContainer.appendChild(ipccp_closeButton);
	
	// create image
	var ipccp_objLink = document.createElement("img");
	ipccp_objLink.setAttribute('id','ipccp_zoom-image');
	ipccp_objLink.setAttribute('usemap','#IPCCP_MAX');
	ipccp_objLink.onclick = function () { ipccp_hideZoom(); return false; }
	ipccp_contentContainer.appendChild(ipccp_objLink);

	
	// create caption
	var ipccp_captionContainer = document.createElement("div");
	ipccp_captionContainer.setAttribute('id','ipccp_zoom-captioncontainer');
	var ipccp_innerCaptionOne = document.createElement("div");
	var ipccp_innerCaptionTwo = document.createElement("div");
	
	var ipccp_objCaption = document.createElement("span");
	ipccp_objCaption.setAttribute('id','ipccp_zoom-caption');
	ipccp_contentContainer.appendChild(ipccp_captionContainer);
	ipccp_captionContainer.appendChild(ipccp_innerCaptionOne);
	ipccp_innerCaptionOne.appendChild(ipccp_innerCaptionTwo);
	ipccp_innerCaptionTwo.appendChild(ipccp_objCaption);
}



function ipccp_addLoadEvent(func)
{	
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else {
		window.onload = function() { oldonload(); func(); }
	}
}


// Initalize zooming

ipccp_addLoadEvent(ipccp_setupZoom);

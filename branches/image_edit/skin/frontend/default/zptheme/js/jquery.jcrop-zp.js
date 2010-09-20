/**
 * jquery.Jcrop.js v0.9.8
 * jQuery Image Cropping Plugin
 *
 * very very raw version
 *
 * Primary Author: Kelly Hallman <khallman@gmail.com>
 * Fork by: ZetaPrints <http://www.ZetaPrints.com>
 *
 * Released under MIT License by Kelly Hallman (2008-2009) {{{
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:

 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.

 * }}}
 */

(function($) {

$.Jcrop = function(obj,opt)
{
	/*
	 * Initialization
	 */

	/*
	 * Sanitize some options
	 */
	var obj = obj, opt = opt;
	
	// var obj = $('img', obj), opt = opt;

	if (typeof(obj) !== 'object') obj = $(obj)[0];
	if (typeof(opt) !== 'object') opt = { };

	// Some on-the-fly fixes for MSIE...sigh
	if (!('trackDocument' in opt))
	{
		opt.trackDocument = $.browser.msie ? false : true;
		if ($.browser.msie && $.browser.version.split('.')[0] == '8')
			opt.trackDocument = true;
	}

	if (!('keySupport' in opt))
			opt.keySupport = $.browser.msie ? false : true;


	/*
	 * Extend the default options
	 */
	var defaults = {

		// Basic Settings
		trackDocument:		false,
		baseClass:			'jcrop',
		addClass:			null,

		// Styling Options
		bgColor:			'black',
		bgOpacity:			.6,
		borderOpacity:		.4,
		handleOpacity:		.5,

		handlePad:			5,
		handleSize:			9,
		handleOffset:		5,
		edgeMargin:			14,

		aspectRatio:		0,
		keySupport:			true,
		cornerHandles:		true,
		sideHandles:		true,
		drawBorders:		true,
		// dragEdges:			true,

		boxWidth:			0,
		boxHeight:			0,

		boundary:			8,
		animationDelay:		20,
		swingSpeed:			3,

		allowSelect:		true,
		allowMove:			true,
		allowResize:		true,

		minSelect:			[ 0, 0 ],
		maxSize:			[ 0, 0 ],
		minSize:			[ 0, 0 ],

		// Callbacks / Event Handlers
		onChange: function() { },
		onSelect: function() { },
		
		// cropping, imagemoving
		// modeSniff: function() {
		// 	return 'cropping';
		// }
		mode: 'cropping'
	};
	var options = defaults;
	setOptions(opt);


	/**
	 * Export CSS rules not related to design in current document
	 */
	function exportCSS()
	{
		var css = $(
			'<STYLE type="text/css">' +
			'.' + options.baseClass + '-handle {position: absolute; z-index: 500; opacity: 0.5;}' +
			'.' + options.baseClass + '-handle-n-resize {cursor: n-resize;}' +
			'.' + options.baseClass + '-handle-s-resize {cursor: s-resize;}' +
			'.' + options.baseClass + '-handle-e-resize {cursor: e-resize;}' +
			'.' + options.baseClass + '-handle-w-resize {cursor: w-resize;}' +
			'.' + options.baseClass + '-handle-sw-resize {cursor: sw-resize;}' +
			'.' + options.baseClass + '-handle-nw-resize {cursor: nw-resize;}' +
			'.' + options.baseClass + '-handle-ne-resize {cursor: ne-resize;}' +
			'.' + options.baseClass + '-handle-se-resize {cursor: se-resize;}' +
			'.' + options.baseClass + '-handle-disabled {cursor: default;}' +
			'</STYLE>'
		);
		$(document.head).append(css);
	}
	document.head = document.head || document.getElementsByTagName('head')[0];
	// $(document).ready(function() {
	exportCSS();
	// });


	// }}}
	// Initialize some jQuery objects {{{

	var $imageOriginal = $('img', obj);
	$imageOriginal.css({position: 'absolute', border:'dotted 1px green'})
	var $imgXZ = $imageOriginal.clone().removeAttr('id').css({ position: 'absolute' });//.attr('id', 'img');
	
	var origimg_boundx = $imageOriginal.width(), origimg_boundy = $imageOriginal.height();
	
	var $obj = $(obj);

	$imgXZ.width($imageOriginal.width());
	$imgXZ.height($imageOriginal.height());
	// $imageOriginal.after($imgXZ).hide();
	// $(obj).hide();

	presize($imgXZ, options.boxWidth, options.boxHeight);

	// var boundx = $imgXZ.width(), boundy = $imgXZ.height(),
	var boundx = $obj.width();
	var boundy = $obj.height();

	var $div = $('<div />')
		.width(boundx)
		.height(boundy)
		.addClass(cssClass('holder'))
		.css({
			marginTop: -boundy,
			position: 'relative',
			backgroundColor: options.bgColor,
			opacity: options.bgOpacity
		}).insertAfter($obj)
		// .append($imgXZ)
		// }).insertAfter($imageOriginal).append($imgXZ);
	
	
	if (options.addClass) $div.addClass(options.addClass);
	//$imgXZ.wrap($div);

	var $imageClipped = $('<img />')
			.attr('src', $imageOriginal.attr('src'))
			// .attr('id', 'img2')
			.css('position', 'absolute')
			.width(origimg_boundx)
			.height(origimg_boundy);

	var $img_holder = $('<div />')
		.width(pct(100)).height(pct(100))
		.css({
			zIndex: 310,
			position: 'absolute',
			overflow: 'hidden'
		})
		.append($imageClipped);

	// $imageClipped.after($imgXZ.clone().attr('xx', 'xxx'));

	var $hdl_holder = $('<div />')
		.width(pct(100)).height(pct(100))
		.css('zIndex',320);

	var $sel = $('<div />')
		.css({
			position: 'absolute',
			zIndex: 300
		})
		//.insertBefore($imgXZ)
		.appendTo($div)
		.append($img_holder, $hdl_holder)
	;

	var bound = options.boundary;
	var $trk = newTracker()
		.width(boundx + (bound * 2))
		.height(boundy + (bound * 2))
		.css({
			// border: 'dotted 1px orange',
			position: 'absolute',
			top: px(-bound),
			left: px(-bound),
			zIndex: 290
		})
		.mousedown(
			function(_event){
				// m_tr.message({type: 'warning', message: 'options.mode: ' + options.mode});
				m_tr.message({type: 'warning', message: 'left: ' + $(_event.currentTarget).left + ', top: ' + $(_event.currentTarget).top + ', this: ' + $(this)});
				if(options.mode=='cropping') {
					return newSelection(_event);
				}
				// var movedImage = $(this);//$(_event.currentTarget);
				var delta = {
					xClipped: _event.pageX - $imageClipped.position().left,
					yClipped: _event.pageY - $imageClipped.position().top,
					xOriginal: _event.pageX - $imageOriginal.position().left,
					yOriginal: _event.pageY - $imageOriginal.position().top
				}
				$(this).mousemove(function(_event){
					imageMover(_event, delta);//, movedImage);
				})
			}
		)
		.mouseup(
			function(_event){
				$(this).unbind('mousemove');
				imageMoverStop(_event, $(_event.currentTarget));
			}
		).mouseout(
			function(_event){
				$(this).unbind('mousemove');
				imageMoverStop(_event, $(_event.currentTarget));
			}
		);

	// Set more variables {{{

	var xlimit, ylimit, xmin, ymin;
	var xscale, yscale, enabled = true;
	var docOffset = getPos($imageClipped),
		// Internal states
		btndown, lastcurs, dimmed, animating,
		shift_down;

	// }}}
		

		// }}}
	// Internal Modules {{{

	var Coords = function()
	{
		var x1 = 0, y1 = 0, x2 = 0, y2 = 0, ox, oy;

		function setPressed(pos)
		{
			var pos = rebound(pos);
			x2 = x1 = pos[0];
			y2 = y1 = pos[1];
		};

		function setCurrent(pos)
		{
			var pos = rebound(pos);
			ox = pos[0] - x2;
			oy = pos[1] - y2;
			x2 = pos[0];
			y2 = pos[1];
		}

		function getOffset()
		{
			return [ ox, oy ];
		};

		function moveOffset(offset)
		{
			var ox = offset[0], oy = offset[1];

			if (0 > x1 + ox) ox -= ox + x1;
			if (0 > y1 + oy) oy -= oy + y1;

			if (boundy < y2 + oy) oy += boundy - (y2 + oy);
			if (boundx < x2 + ox) ox += boundx - (x2 + ox);

			x1 += ox;
			x2 += ox;
			y1 += oy;
			y2 += oy;
		}

		function getCorner(ord)
		{
			var c = getFixed();
			switch(ord)
			{
				case 'ne': return [ c.x2, c.y ];
				case 'nw': return [ c.x, c.y ];
				case 'se': return [ c.x2, c.y2 ];
				case 'sw': return [ c.x, c.y2 ];
			}
		}

		function getFixed()
		{
			if (!options.aspectRatio) return getRect();
			// This function could use some optimization I think...
			var aspect = options.aspectRatio,
				min_x = options.minSize[0] / xscale, 
				min_y = options.minSize[1] / yscale,
				max_x = options.maxSize[0] / xscale, 
				max_y = options.maxSize[1] / yscale,
				rw = x2 - x1,
				rh = y2 - y1,
				rwa = Math.abs(rw),
				rha = Math.abs(rh),
				real_ratio = rwa / rha,
				xx, yy
			;
			if (max_x == 0) { max_x = boundx * 10 }
			if (max_y == 0) { max_y = boundy * 10 }
			if (real_ratio < aspect)
			{
				yy = y2;
				w = rha * aspect;
				xx = rw < 0 ? x1 - w : w + x1;

				if (xx < 0)
				{
					xx = 0;
					h = Math.abs((xx - x1) / aspect);
					yy = rh < 0 ? y1 - h : h + y1;
				}
				else if (xx > boundx)
				{
					xx = boundx;
					h = Math.abs((xx - x1) / aspect);
					yy = rh < 0 ? y1 - h : h + y1;
				}
			}
			else
			{
				xx = x2;
				h = rwa / aspect;
				yy = rh < 0 ? y1 - h : y1 + h;
				if (yy < 0)
				{
					yy = 0;
					w = Math.abs((yy - y1) * aspect);
					xx = rw < 0 ? x1 - w : w + x1;
				}
				else if (yy > boundy)
				{
					yy = boundy;
					w = Math.abs(yy - y1) * aspect;
					xx = rw < 0 ? x1 - w : w + x1;
				}
			}

			// Magic %-)
			if(xx > x1) { // right side
			  if(xx - x1 < min_x) {
				xx = x1 + min_x;
			  } else if (xx - x1 > max_x) {
				xx = x1 + max_x;
			  }
			  if(yy > y1) {
				yy = y1 + (xx - x1)/aspect;
			  } else {
				yy = y1 - (xx - x1)/aspect;
			  }
			} else if (xx < x1) { // left side
			  if(x1 - xx < min_x) {
				xx = x1 - min_x
			  } else if (x1 - xx > max_x) {
				xx = x1 - max_x;
			  }
			  if(yy > y1) {
				yy = y1 + (x1 - xx)/aspect;
			  } else {
				yy = y1 - (x1 - xx)/aspect;
			  }
			}

			if(xx < 0) {
				x1 -= xx;
				xx = 0;
			} else  if (xx > boundx) {
				x1 -= xx - boundx;
				xx = boundx;
			}

			if(yy < 0) {
				y1 -= yy;
				yy = 0;
			} else  if (yy > boundy) {
				y1 -= yy - boundy;
				yy = boundy;
			}

			return last = makeObj(flipCoords(x1, y1, xx, yy));
		}

		function rebound(p)
		{
			if (p[0] < 0) p[0] = 0;
			if (p[1] < 0) p[1] = 0;

			if (p[0] > boundx) p[0] = boundx;
			if (p[1] > boundy) p[1] = boundy;

			return [ p[0], p[1] ];
		}

		function flipCoords(x1,y1,x2,y2)
		{
			var xa = x1, xb = x2, ya = y1, yb = y2;
			if (x2 < x1)
			{
				xa = x2;
				xb = x1;
			}
			if (y2 < y1)
			{
				ya = y2;
				yb = y1;
			}
			return [ Math.round(xa), Math.round(ya), Math.round(xb), Math.round(yb) ];
		}

		function getRect()
		{
			var xsize = x2 - x1;
			var ysize = y2 - y1;

			if (xlimit && (Math.abs(xsize) > xlimit))
				x2 = (xsize > 0) ? (x1 + xlimit) : (x1 - xlimit);
			if (ylimit && (Math.abs(ysize) > ylimit))
				y2 = (ysize > 0) ? (y1 + ylimit) : (y1 - ylimit);

			if (ymin && (Math.abs(ysize) < ymin))
				y2 = (ysize > 0) ? (y1 + ymin) : (y1 - ymin);
			if (xmin && (Math.abs(xsize) < xmin))
				x2 = (xsize > 0) ? (x1 + xmin) : (x1 - xmin);

			if (x1 < 0) { x2 -= x1; x1 -= x1; }
			if (y1 < 0) { y2 -= y1; y1 -= y1; }
			if (x2 < 0) { x1 -= x2; x2 -= x2; }
			if (y2 < 0) { y1 -= y2; y2 -= y2; }
			if (x2 > boundx) { var delta = x2 - boundx; x1 -= delta; x2 -= delta; }
			if (y2 > boundy) { var delta = y2 - boundy; y1 -= delta; y2 -= delta; }
			if (x1 > boundx) { var delta = x1 - boundy; y2 -= delta; y1 -= delta; }
			if (y1 > boundy) { var delta = y1 - boundy; y2 -= delta; y1 -= delta; }

			return makeObj(flipCoords(x1,y1,x2,y2));
		}

		function makeObj(a)
		{
			return { x: a[0], y: a[1], x2: a[2], y2: a[3],
				w: a[2] - a[0], h: a[3] - a[1] };
		}

		return {
			flipCoords: flipCoords,
			setPressed: setPressed,
			setCurrent: setCurrent,
			getOffset: getOffset,
			moveOffset: moveOffset,
			getCorner: getCorner,
			getFixed: getFixed
		};
	}();

	var Selection = function()
	{
		var start, end, dragmode, awake, hdep = 370;
		var borders = { };
		var handles = { };
		var seehandles = false;
		var hhs = options.handleOffset;

		/* Insert draggable elements {{{*/

		// Insert border divs for outline
		if (options.drawBorders) {
			borders = {
					top: insertBorder('hline')
						.css('top',$.browser.msie?px(-1):px(0)),
					bottom: insertBorder('hline'),
					left: insertBorder('vline'),
					right: insertBorder('vline')
			};
		}

		// Insert handles on edges
		/* if (options.dragEdges) {
			handle.t = insertDragbar('n');
			handle.b = insertDragbar('s');
			handle.r = insertDragbar('e');
			handle.l = insertDragbar('w');
		} */

		// Insert side handles
		options.sideHandles &&
			createHandles(['n', 's', 'e', 'w']);

		// Insert corner handles
		options.cornerHandles &&
			createHandles(['sw', 'nw', 'ne', 'se']);

		// activateHandles();


		/*
		 * Private Methods
		 */

		function insertBorder(_type)
		{
			var jq = $('<div />')
				.css({
					position: 'absolute',
					opacity: options.borderOpacity
				})
				.addClass(cssClass(_type));
			$img_holder.append(jq);
			return jq;
		}

		function dragDiv(_ord)//, zi)
		{
			var jq = $('<div />')
				.mousedown(createDragger(_ord))
				.css({
					// cursor: ord+'-resize',
					// position: 'absolute',
					// zIndex: zi 
				})
			;
			$hdl_holder.append(jq);
			return jq;
		}

		function insertHandle(_ord)
		{
			return dragDiv(_ord/*,hdep++*/)
				.css({
					top: px(-hhs+1),
					left: px(-hhs+1)
					// opacity: options.handleOpacity
				})
				.addClass(cssClass('handle'))
				// .addClass(cssClass('handle-' + _ord + '-resize'));
		}

//		function insertDragbar(_ord)
//		{
//			var s = options.handleSize,
//				o = hhs,
//				h = s, w = s,
//				t = o, l = o;
//
//		switch(_ord)
//			{
//				case 'n': case 's': w = pct(100); break;
//				case 'e': case 'w': h = pct(100); break;
//			}
//
//			return dragDiv(_ord/*,hdep++*/)
//				.width(w)
//				.height(h)
//				.css({
//					top: px(-t+1),
//					left: px(-l+1)
//				});
//		}

		function createHandles(_li)
		{
			for(i in _li)
				handles[_li[i]] = insertHandle(_li[i]);
		}

		function activateHandles()
		{
			for(var handleOrientation in handles) {
				handles[handleOrientation].addClass(cssClass('handle-' + handleOrientation + '-resize'))
				//handle-ne-resize
			}
		}

		function deactivateHandles()
		{
			for(var handleOrientation in handles) {
				handles[handleOrientation].addClass(cssClass('handle-disabled'))
				//handle-ne-resize
			}
		}

		function moveHandles(_c)
		{
			var midvert = Math.round((_c.h / 2) - hhs),
				midhoriz = Math.round((_c.w / 2) - hhs),
				north = west = -hhs + 1,
				east = _c.w - hhs,
				south = _c.h - hhs,
				x, y;

			'e' in handles &&
				handles.e.css({ top: px(midvert), left: px(east) }) &&
				handles.w.css({ top: px(midvert) }) &&
				handles.s.css({ top: px(south), left: px(midhoriz) }) &&
				handles.n.css({ left: px(midhoriz) });

			'ne' in handles &&
				handles.ne.css({ left: px(east) }) &&
				handles.se.css({ top: px(south), left: px(east) }) &&
				handles.sw.css({ top: px(south) });

			'b' in handles &&
				handles.b.css({ top: px(south) }) &&
				handles.r.css({ left: px(east) });
		}

		function moveto(_x, _y)
		{
			$imageClipped.css({
				top: px(-_y),
				left: px(-_x)
			});
			$sel.css({
				top: px(_y),
				left: px(_x)
			});
		}

		function resize(_w, _h)
		{
			$sel.width(_w).height(_h);
		}

		function refresh()
		{
			var c = Coords.getFixed();

			Coords.setPressed([c.x,c.y]);
			Coords.setCurrent([c.x2,c.y2]);

			updateVisible();
		}


		/*
		 * Internal Methods
		 */

		function updateVisible()
		{
			if(awake)
				return update();
		}

		function update()
		{
			var c = Coords.getFixed();

			resize(c.w,c.h);
			moveto(c.x,c.y);

			options.drawBorders &&
			borders['right'].css({
				left: px(c.w-1)
			}) &&
			borders['bottom'].css({
				top: px(c.h-1)
			});

			seehandles && moveHandles(c);
			awake || show();

			options.onChange(unscale(c));
		}

		function show()
		{
			$sel.show();
			// $imgXZ.css('opacity',options.bgOpacity);
			$div.css('opacity', options.bgOpacity);
			awake = true;
		}

		function release()
		{
			disableHandles();
			$sel.hide();
			// $imgXZ.css('opacity', 1);
			$imageClipped.css('opacity', 1);
			awake = false;
		}

		function showHandles()
		{
			if (seehandles)
			{
				moveHandles(Coords.getFixed());
				$hdl_holder.show();
			}
		}

		function enableHandles()
		{ 
			seehandles = true;
			if (options.allowResize)
			{
				moveHandles(Coords.getFixed());
				$hdl_holder.show();
				return true;
			}
		}

		function disableHandles()
		{
			seehandles = false;
			$hdl_holder.hide();
		}

		function animMode(_v)
		{
			(animating = _v) ? disableHandles(): enableHandles();
		}

		function done()
		{
			animMode(false);
			refresh();
		}


		var $track = newTracker().mousedown(createDragger('move')).css({
			cursor: 'move',
			position: 'absolute',
			zIndex: 360
		})

		$img_holder.append($track);
		disableHandles();

		return {
			updateVisible: updateVisible,
			update: update,
			release: release,
			refresh: refresh,
			setCursor: function (cursor) { $track.css('cursor',cursor); },
			enableHandles: enableHandles,
			enableOnly: function() { seehandles = true; },
			showHandles: showHandles,
			disableHandles: disableHandles,
			animMode: animMode,
			done: done,
			activateHandles: activateHandles,
			deactivateHandles: deactivateHandles
		};
	}();
	// end Selection class

	var Tracker = function()
	{
		var onMove		= function() { },
			onDone		= function() { },
			trackDoc	= options.trackDocument;

		if (!trackDoc)
		{
			$trk
				.mousemove(trackMove)
				.mouseup(trackUp)
				.mouseout(trackUp)
			;
		}

		function toFront()
		{
			$trk.css({zIndex:450});
			if (trackDoc)
			{
				$(document)
					.mousemove(trackMove)
					.mouseup(trackUp)
				;
			}
		}

		function toBack()
		{
			$trk.css({zIndex:290});
			if (trackDoc)
			{
				$(document)
					.unbind('mousemove',trackMove)
					.unbind('mouseup',trackUp)
				;
			}
		}

		function trackMove(e)
		{
			onMove(mouseAbs(e));
		}

		function trackUp(e)
		{
			e.preventDefault();
			e.stopPropagation();

			if (btndown)
			{
				btndown = false;

				onDone(mouseAbs(e));
				options.onSelect(unscale(Coords.getFixed()));
				toBack();
				onMove = function() { };
				onDone = function() { };
			}

			return false;
		}

		function activateHandlers(move, done)
		{
			btndown = true;
			onMove = move;
			onDone = done;
			toFront();
			return false;
		}

		function setCursor(t) { $trk.css('cursor',t); }

		// $imgXZ.before($trk);
		$trk.appendTo($div);
		return {
			activateHandlers: activateHandlers,
			setCursor: setCursor
		};
	}();
	
	// var watchMode = function()
	// {
	// 	//alert(options.mode);
	// 	alert($hdl_holder)
	// }
	
	
	var KeyManager = function()
	{
		var $keymgr = $('<input type="radio" />').css({
			position: 'absolute',
			left: '-30px'
		})
		.keyup(parseKeyUp)
		.keypress(parseKey)
		.blur(onBlur);

		var $keywrap = $('<div />').css({
			position: 'absolute',
			overflow: 'hidden'
		})
		.append($keymgr);

		function parseKeyUp(e)
		{
			// alert(e.ctrlKey + ', ' + e.keyCode)
			// if (e.ctrlKey) {
			// 	alert('ctrl key pressed');
			// } else {
			// 	alert(334);
			// }
		}

		function watchKeys()
		{
			if (options.keySupport)
			{
				$keymgr.show();
				$keymgr.focus();
			}
		};

		function onBlur(_e)
		{
			$keymgr.hide();
		};

		function doNudge(_e, _x, _y)
		{
			if (options.allowMove) {
				Coords.moveOffset([_x, _y]);
				Selection.updateVisible();
			};
			_e.preventDefault();
			_e.stopPropagation();
		};

		function parseKey(_e)
		{
			if (_e.ctrlKey) {
				// alert('ctrlKey pressed');
				return true;
			}
			shift_down = _e.shiftKey ? true : false;
			var nudge = shift_down ? 10 : 1;
			switch(_e.keyCode)
			{
				case 37: doNudge(_e, -nudge, 0); break;
				case 39: doNudge(_e, nudge, 0); break;
				case 38: doNudge(_e, 0, -nudge); break;
				case 40: doNudge(_e, 0, nudge); break;

				case 27: Selection.release(); break;

				case 9: return true;
			}

			return nothing(_e);
		}

		if(options.keySupport)
			$keywrap.insertBefore($imageClipped);
			// $keywrap.insertBefore($imgXZ);

		return {watchKeys: watchKeys};
	}();


	/*
	 * Internal Methods
	 */

	function px(_n) { return '' + parseInt(_n) + 'px'; };
	function pct(_n) { return '' + parseInt(_n) + '%'; };
	function cssClass(_cl) { return options.baseClass + '-' + _cl; };
	
	function getPos(_obj)
	{
		// Updated in v0.9.4 to use built-in dimensions plugin
		var pos = $(_obj).offset();
		return [pos.left, pos.top];
	}

	function mouseAbs(_e)
	{
		return [(_e.pageX - docOffset[0]), (_e.pageY - docOffset[1])];
	}

	function myCursor(_type)
	{
		if (_type != lastcurs)
		{
			Tracker.setCursor(_type);
			//Handles.xsetCursor(_type);
			lastcurs = _type;
		}
	}

	function startDragMode(_mode, _pos)
	{
		// docOffset = getPos($imgXZ);
		docOffset = getPos($imageClipped);
		Tracker.setCursor(_mode=='move' ? _mode : _mode + '-resize');

		if (_mode == 'move')
			return Tracker.activateHandlers(createMover(_pos), doneSelect);

		var fc = Coords.getFixed();
		var opp = oppLockCorner(_mode);
		var opc = Coords.getCorner(oppLockCorner(opp));

		Coords.setPressed(Coords.getCorner(opp));
		Coords.setCurrent(opc);

		Tracker.activateHandlers(dragmodeHandler(_mode, fc), doneSelect);
	}

	function dragmodeHandler(_mode, _f)
	{
		return function(_pos) {
			if (!options.aspectRatio) switch(_mode)
			{
				case 'e': _pos[1] = _f.y2; break;
				case 'w': _pos[1] = _f.y2; break;
				case 'n': _pos[0] = _f.x2; break;
				case 's': _pos[0] = _f.x2; break;
			}
			else switch(_mode)
			{
				case 'e': _pos[1] = _f.y+1; break;
				case 'w': _pos[1] = _f.y+1; break;
				case 'n': _pos[0] = _f.x+1; break;
				case 's': _pos[0] = _f.x+1; break;
			}
			Coords.setCurrent(_pos);
			Selection.update();
		};
	}

	function createMover(_pos)
	{
		var lloc = _pos;
		KeyManager.watchKeys();
		watchMode();

		return function(_pos)
		{
			Coords.moveOffset([_pos[0] - lloc[0], _pos[1] - lloc[1]]);
			lloc = _pos;
			
			Selection.update();
		};
	};

	function oppLockCorner(_ord)
	{
		switch(_ord)
		{
			case 'n': return 'sw';
			case 's': return 'nw';
			case 'e': return 'nw';
			case 'w': return 'ne';
			case 'ne': return 'sw';
			case 'nw': return 'se';
			case 'se': return 'nw';
			case 'sw': return 'ne';
		}
	}

	function createDragger(_ord)
	{
		return function(_e) {
			if (options.disabled) return false;
			if ((_ord == 'move') && !options.allowMove) return false;
			btndown = true;
			startDragMode(_ord, mouseAbs(_e));
			_e.stopPropagation();
			_e.preventDefault();
			return false;
		};
	}

	function presize($obj, _w, _h)
	{
		var nw = $obj.width();
		var nh = $obj.height();

		if ((nw > _w) && _w > 0)
		{
			nw = _w;
			nh = (_w / $obj.width()) * $obj.height();
		}

		if ((nh > _h) && _h > 0)
		{
			nh = _h;
			nw = (_h / $obj.height()) * $obj.width();
		}

		xscale = $obj.width() / nw;
		yscale = $obj.height() / nh;
		$obj.width(nw).height(nh);
	}

	function unscale(_c)
	{
		return {
			x: parseInt(_c.x * xscale),
			y: parseInt(_c.y * yscale), 
			x2: parseInt(_c.x2 * xscale),
			y2: parseInt(_c.y2 * yscale), 
			w: parseInt(_c.w * xscale),
			h: parseInt(_c.h * yscale)
		};
	}

	function doneSelect(_pos)
	{
		var c = Coords.getFixed();
		if (c.w > options.minSelect[0] && c.h > options.minSelect[1])
		{
			Selection.enableHandles();
			Selection.done();
		}
		else
		{
			Selection.release();
		}
		Tracker.setCursor(options.allowSelect ? 'crosshair' : 'default' );
	}

	function processMouseDown(_event)
	{
		m_tr.message({type: 'info', message: 'options.mode: ' + options.mode});
		if(options.mode=='cropping') {
			return newSelection(_event);
		} else {
			return newImageMover(_event);
		}
	}

	function imageMover(_event, _delta, _movedImage)
	{
		var xPosClipped = _event.pageX - _delta.xClipped;
		var yPosClipped = _event.pageY - _delta.yClipped;
		var xPosOriginal = _event.pageX - _delta.xOriginal;
		var yPosOriginal = _event.pageY - _delta.yOriginal;
		// m_tr.message({type: 'error', message: 'xPos: ' + xPos + ', yPos: ' + yPos});
		$imageClipped.css({
			left: xPosClipped,
			top: yPosClipped
		});
		$imageOriginal.css({
			left: xPosOriginal,
			top: yPosOriginal
		});
	}

	function imageMoverStop(_event, _currentTarget)
	{
	}

	function newSelection(_event)
	{
		if(options.disabled) return false;
		if(!options.allowSelect) return false;

		// if(options.mode!='cropping')
		// 	return false;
		// m_tr.message({type: 'warning', message: 'cropping'});
		
		btndown = true;
		// docOffset = getPos($imgXZ);
		docOffset = getPos($imageClipped);
		Selection.disableHandles();
		myCursor('crosshair');
		var pos = mouseAbs(_event);
		Coords.setPressed(pos);
		Tracker.activateHandlers(selectDrag, doneSelect);
		KeyManager.watchKeys();
		watchMode();
		Selection.update();
		Selection.activateHandles();

		_event.stopPropagation();
		_event.preventDefault();
		return false;
	}

	function selectDrag(_pos)
	{
		Coords.setCurrent(_pos);
		Selection.update();
	}

	function newTracker()
	{
		var trk = $('<div><spacer type="block" width="100%" height="100%"></spacer></div>')
			.addClass(cssClass('tracker'));
		$.browser.msie && trk.css({
			opacity: 0,
			backgroundColor: 'white'
		});
		return trk;
	}

	function watchMode()
	{
		//alert(options.mode);
		//alert(j5_var_dump($hdl_holder, 'hdl_holder'))
		if ($hdl_holder) {
			// alert($hdl_holder.length)
			//$('.holder', $hdl_holder).each(function(){
			//});
			if (options.mode=='cropping') {
				$hdl_holder.show();
			} else {
				$hdl_holder.hide();
			}
		}
	}


	/*
	 * API methods
	 */

	function animateTo(_a)
	{
		var x1 = _a[0] / xscale;
		var y1 = _a[1] / yscale;
		var x2 = _a[2] / xscale;
		var y2 = _a[3] / yscale;

		if (animating) return;

		var animto = Coords.flipCoords(x1, y1, x2, y2);
		var c = Coords.getFixed();
		var animat = initcr = [c.x, c.y, c.x2, c.y2];
		var interv = options.animationDelay;

		var x = animat[0];
		var y = animat[1];
		var x2 = animat[2];
		var y2 = animat[3];
		var ix1 = animto[0] - initcr[0];
		var iy1 = animto[1] - initcr[1];
		var ix2 = animto[2] - initcr[2];
		var iy2 = animto[3] - initcr[3];
		var pcent = 0;
		var velocity = options.swingSpeed;

		Selection.animMode(true);

		var animator = function()
		{
			return function()
			{
				pcent += (100 - pcent) / velocity;

				animat[0] = x + ((pcent / 100) * ix1);
				animat[1] = y + ((pcent / 100) * iy1);
				animat[2] = x2 + ((pcent / 100) * ix2);
				animat[3] = y2 + ((pcent / 100) * iy2);

				if (pcent < 100) animateStart();
					else Selection.done();

				if (pcent >= 99.8) pcent = 100;

				setSelectRaw(animat);
			};
		}();

		function animateStart()
		{
			window.setTimeout(animator, interv);
		}

		animateStart();
	}

	function setSelect(_rect)
	{
		setSelectRaw([_rect[0] / xscale, _rect[1] / yscale, _rect[2] / xscale, _rect[3] / yscale]);
	}

	function setSelectRaw(_l)
	{
		Coords.setPressed([_l[0], _l[1]]);
		Coords.setCurrent([_l[2], _l[3]]);
		Selection.update();
	}

	function setOptions(_opt)
	{
		if (typeof(_opt) != 'object') _opt = { };
		options = $.extend(options, _opt);

		if (typeof(options.onChange)!=='function')
			options.onChange = function() { };

		if (typeof(options.onSelect)!=='function')
			options.onSelect = function() { };

		watchMode();
	}

	function tellSelect()
	{
		return unscale(Coords.getFixed());
	};

	function tellScaled()
	{
		return Coords.getFixed();
	}

	function setOptionsNew(_opt)
	{
		setOptions(_opt);
		interfaceUpdate();
	};

	function disableCrop()
	{
		options.disabled = true;
		Selection.disableHandles();
		Selection.setCursor('default');
		Tracker.setCursor('default');
	}

	function enableCrop()
	{
		options.disabled = false;
		interfaceUpdate();
	}

	function cancelCrop()
	{
		Selection.done();
		Tracker.activateHandlers(null,null);
	};

	function destroy()
	{
		$div.remove();
		$imageOriginal.show();
	};

	/**
	 * This method tweaks the interface based on options object.
	 * Called when options are changed and at end of initialization.
	 */
	function interfaceUpdate(_alt)
	{
		options.allowResize ?
			_alt ? Selection.enableOnly() : Selection.enableHandles() :
			Selection.disableHandles();

		Tracker.setCursor(options.allowSelect ? 'crosshair' : 'default');
		Selection.setCursor(options.allowMove ? 'move' : 'default');

		// $div.css('backgroundColor',options.bgColor);

		if ('setSelect' in options) {
			setSelect(opt.setSelect);
			Selection.done();
			delete(options.setSelect);
		}

		if ('trueSize' in options) {
			xscale = options.trueSize[0] / boundx;
			yscale = options.trueSize[1] / boundy;
		}

		xlimit = options.maxSize[0] || 0;
		ylimit = options.maxSize[1] || 0;
		xmin = options.minSize[0] || 0;
		ymin = options.minSize[1] || 0;

		if ('outerImage' in options)
		{
			// $imgXZ.attr('src', options.outerImage);
			$imageClipped.attr('src', options.outerImage);
			delete(options.outerImage);
		}

		Selection.refresh();
	}

	$hdl_holder.hide();
	interfaceUpdate(true);

	var api = {
		animateTo: animateTo,
		setSelect: setSelect,
		setOptions: setOptionsNew,
		tellSelect: tellSelect,
		tellScaled: tellScaled,

		disable: disableCrop,
		enable: enableCrop,
		cancel: cancelCrop,

		focus: KeyManager.watchKeys,

		getBounds: function() { return [boundx * xscale, boundy * yscale ];},
		getWidgetSize: function() { return [boundx, boundy];},

		release: Selection.release,
		destroy: destroy
	};

	$imageOriginal.data('Jcrop', api);

	return api;
};

$.fn.Jcrop = function(options)
{
	function attachWhenDone(_from)
	{
		alert('1: ' + _from)
		var loadsrc = options.useImg || _from.src;
		var img = new Image();
		img.onload = function() { $.Jcrop(_from,options); };
		img.src = loadsrc;
	};
	/*}}}*/
	if (typeof(options) !== 'object') options = { };

	// Iterate over each object, attach Jcrop
	this.each(function()
	{
		// alert('3: ' + this)
		alert('3: ' + $(this)[0].tagName)
		// If we've already attached to this object
		if ($(this).data('Jcrop'))
		{
			alert('2: ' + $(this)[0].tagName)
			// The API can be requested this way (undocumented)
			if (options == 'api') return $(this).data('Jcrop');
			// Otherwise, we just reset the options...
			else $(this).data('Jcrop').setOptions(options);
		}
		// If we haven't been attached, preload and attach
		else attachWhenDone(this);
	});

	// Return "this" so we're chainable a la jQuery plugin-style!
	return this;
};
/*}}}*/

})(jQuery);

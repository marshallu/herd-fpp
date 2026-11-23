/**
 * Herd Focal Point Picker.
 *
 * <herd-fpp-picker> wraps the Focal Point field that includes/herd-fpp-picker.php
 * renders. When it connects, it finds the image preview next to the field (in
 * the media modal or on the Edit Media screen), draws a handle over it, and
 * keeps the read-only `left top` input in sync with the handle. Changing the
 * input fires `change`, which is how WordPress knows to save attachment fields
 * in the media modal; the Edit Media screen saves them on Update.
 *
 * No dependencies: plain pointer events instead of jQuery UI.
 */
( () => {
	'use strict';

	const round = ( value ) => Math.round( value * 100 ) / 100;
	const clamp = ( value ) => Math.min( 1, Math.max( 0, value ) );

	/**
	 * Parse a stored `left top` value into two fractions from 0 to 1, or null.
	 * Values above 1 are percentages, as in the PHP side.
	 *
	 * @param {string} value
	 * @return {[number, number] | null}
	 */
	function parseValue( value ) {
		const parts = value.trim().split( /\s+/ ).map( Number );
		if ( parts.length !== 2 || parts.some( Number.isNaN ) ) {
			return null;
		}
		return parts.map( ( part ) => round( clamp( part > 1 ? part / 100 : part ) ) );
	}

	/**
	 * Find the image preview this field belongs to.
	 *
	 * @param {HTMLElement} field
	 * @return {HTMLElement | null} The element wrapping the <img>.
	 */
	function findImageWrap( field ) {
		const details = field.closest( '.attachment-details, .media-frame-content' );
		if ( details ) {
			return details.querySelector( '.thumbnail-image' );
		}
		const classic = field.closest( '#post-body-content' );
		if ( classic ) {
			return classic.querySelector( '.wp_attachment_image p' );
		}
		return null;
	}

	class HerdFppPicker extends HTMLElement {
		connectedCallback() {
			this.input = this.querySelector( '.herd-fpp-input' );
			this.resetButton = this.querySelector( '.herd-fpp-reset' );
			this.defaultPoint = [
				Number( this.dataset.defaultLeft ?? 0.5 ),
				Number( this.dataset.defaultTop ?? 0.5 ),
			];

			// Wait for the media modal to finish rendering the preview beside us.
			requestAnimationFrame( () => requestAnimationFrame( () => this.attach() ) );
		}

		disconnectedCallback() {
			this.detach();
		}

		attach() {
			if ( ! this.isConnected || this.wrap ) {
				return;
			}

			const wrap = findImageWrap( this );
			const img = wrap?.querySelector( 'img' );
			if ( ! wrap || ! img || wrap.hasAttribute( 'data-herd-fpp' ) ) {
				return;
			}

			this.wrap = wrap;
			this.img = img;
			wrap.setAttribute( 'data-herd-fpp', '' );

			this.handle = document.createElement( 'button' );
			this.handle.type = 'button';
			this.handle.className = 'herd-fpp-handle';
			this.handle.title = this.dataset.handleLabel;
			this.handle.setAttribute( 'aria-label', this.handle.title );
			wrap.appendChild( this.handle );

			this.preview = document.createElement( 'div' );
			this.preview.className = 'herd-fpp-preview';
			this.preview.setAttribute( 'aria-hidden', 'true' );
			this.preview.innerHTML = '<div class="herd-fpp-preview-portrait"></div><div class="herd-fpp-preview-landscape"></div>';
			document.body.appendChild( this.preview );

			img.setAttribute( 'draggable', 'false' );
			img.addEventListener( 'pointerdown', this.onPointerDown );
			img.addEventListener( 'load', this.render );
			this.handle.addEventListener( 'pointerdown', this.onPointerDown );
			this.handle.addEventListener( 'dblclick', this.reset );
			this.handle.addEventListener( 'keydown', this.onKeyDown );
			this.resetButton?.addEventListener( 'click', this.reset );
			window.addEventListener( 'resize', this.render );

			this.render();
		}

		detach() {
			if ( ! this.wrap ) {
				return;
			}
			this.img.removeEventListener( 'pointerdown', this.onPointerDown );
			this.img.removeEventListener( 'load', this.render );
			this.resetButton?.removeEventListener( 'click', this.reset );
			window.removeEventListener( 'resize', this.render );
			this.handle.remove();
			this.preview.remove();
			this.wrap.removeAttribute( 'data-herd-fpp' );
			document.body.removeAttribute( 'data-herd-fpp-dragging' );
			this.wrap = this.img = this.handle = this.preview = null;
		}

		/** The current point, from the input. */
		get point() {
			return parseValue( this.input.value ) ?? this.defaultPoint;
		}

		/**
		 * Store a new point and fire `change` so WordPress saves it.
		 *
		 * @param {[number, number]} point
		 * @param {boolean}          save  False while dragging, to save once on release.
		 */
		setPoint( [ left, top ], save = true ) {
			const value = `${ round( clamp( left ) ) } ${ round( clamp( top ) ) }`;
			const changed = value !== this.input.value;
			this.input.value = value;
			this.render();
			if ( save && ( changed || this.dirty ) ) {
				this.dirty = false;
				this.input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} else if ( changed ) {
				this.dirty = true;
			}
		}

		/** Move the handle and preview to the current point. */
		render = () => {
			if ( ! this.img ) {
				return;
			}
			const [ left, top ] = this.point;
			const { img, handle, preview } = this;

			handle.style.left = `${ img.offsetLeft + img.offsetWidth * left }px`;
			handle.style.top = `${ img.offsetTop + img.offsetHeight * top }px`;

			preview.style.setProperty( '--herd-fpp-image', `url("${ img.currentSrc || img.src }")` );
			preview.style.setProperty( '--herd-fpp-position', `${ left * 100 }% ${ top * 100 }%` );

			if ( this.resetButton ) {
				this.resetButton.disabled = left === this.defaultPoint[ 0 ] && top === this.defaultPoint[ 1 ];
			}
		};

		/**
		 * The point under the pointer, as fractions of the image.
		 *
		 * @param {PointerEvent} event
		 * @return {[number, number]}
		 */
		pointFromEvent( event ) {
			const rect = this.img.getBoundingClientRect();
			return [
				( event.clientX - rect.left ) / rect.width,
				( event.clientY - rect.top ) / rect.height,
			];
		}

		/**
		 * Pressing on the image or handle moves the point there, and dragging
		 * moves it along; the point is saved when the pointer is released.
		 *
		 * @param {PointerEvent} event
		 */
		onPointerDown = ( event ) => {
			if ( event.button !== 0 ) {
				return;
			}
			event.preventDefault();

			const target = event.currentTarget;
			target.setPointerCapture( event.pointerId );
			if ( target !== this.handle ) {
				this.setPoint( this.pointFromEvent( event ), false );
			}

			document.body.setAttribute( 'data-herd-fpp-dragging', '' );
			this.preview.classList.add( 'is-visible' );

			const move = ( moveEvent ) => this.setPoint( this.pointFromEvent( moveEvent ), false );
			const end = () => {
				target.removeEventListener( 'pointermove', move );
				target.removeEventListener( 'pointerup', end );
				target.removeEventListener( 'pointercancel', end );
				document.body.removeAttribute( 'data-herd-fpp-dragging' );
				this.preview?.classList.remove( 'is-visible' );
				this.setPoint( this.point );
			};

			target.addEventListener( 'pointermove', move );
			target.addEventListener( 'pointerup', end );
			target.addEventListener( 'pointercancel', end );
		};

		/**
		 * Arrow keys nudge the focused handle by 1%, or 10% with Shift.
		 *
		 * @param {KeyboardEvent} event
		 */
		onKeyDown = ( event ) => {
			const step = event.shiftKey ? 0.1 : 0.01;
			const moves = {
				ArrowLeft: [ -step, 0 ],
				ArrowRight: [ step, 0 ],
				ArrowUp: [ 0, -step ],
				ArrowDown: [ 0, step ],
			};
			const move = moves[ event.key ];
			if ( ! move ) {
				return;
			}
			event.preventDefault();
			const [ left, top ] = this.point;
			this.setPoint( [ left + move[ 0 ], top + move[ 1 ] ] );
		};

		/** Move the point back to the default position. */
		reset = () => {
			this.setPoint( this.defaultPoint );
		};
	}

	if ( ! customElements.get( 'herd-fpp-picker' ) ) {
		customElements.define( 'herd-fpp-picker', HerdFppPicker );
	}
} )();

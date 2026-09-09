/**
 * Sortable list widget - vanilla JS, no jQuery/jQuery UI Sortable
 * dependency. Used both by the edit-post order metabox(es) and by the
 * Default Order tab's term-order editor (see functions-metabox.php and
 * tab-default-order.php).
 *
 * Each <ul class="azrcrv-to-sortable-list"> carries a
 * data-azrcrv-to-hidden-input attribute naming a hidden <input>'s name
 * attribute (not necessarily unique as an id - see the lookup below) that
 * this script keeps in sync with the list's current order (a
 * comma-separated list of each <li>'s data-term-id), so the order survives
 * a normal form submit with no further JS needed server-side.
 *
 * Reordering works two ways, independently of each other:
 * - Drag and drop, via the native HTML5 Drag and Drop API.
 * - Up/down buttons on each item, which are ordinary <button> elements and
 *   so are already keyboard-operable (Tab to focus, Enter/Space to
 *   activate) - this is the accessible fallback, since drag-and-drop alone
 *   cannot be operated without a mouse.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	'use strict';

	var lists = document.querySelectorAll( '.azrcrv-to-sortable-list' );

	lists.forEach( function ( list ) {
		initSortableList( list );
	} );

	/**
	 * Wire up one sortable list.
	 *
	 * @param {HTMLElement} list The <ul class="azrcrv-to-sortable-list"> element.
	 */
	function initSortableList( list ) {

		var hiddenInputId = list.getAttribute( 'data-azrcrv-to-hidden-input' );

		// Looked up by [name], not getElementById(): ClassicPress/WordPress
		// renders every metabox inside a wrapper element using the
		// metabox's own registered ID as that wrapper's HTML id attribute,
		// so an id chosen for a hidden input here could coincidentally
		// collide with a metabox wrapper's id elsewhere on the same
		// screen. getElementById() is only reliable when ids are unique;
		// the name attribute is what the browser actually uses to build
		// the submitted form data, so that's what's used to find the
		// field here, regardless of what else on the page might share its
		// id.
		var hiddenInput = hiddenInputId ? document.querySelector( 'input[name="' + hiddenInputId + '"]' ) : null;

		var draggedItem   = null;
		var dropTarget    = null; // { item: <li>, position: 'before' | 'after' } | null
		var listDropAtEnd = false;

		/**
		 * Write the list's current DOM order into the hidden input.
		 */
		function syncHiddenInput() {
			if ( ! hiddenInput ) {
				return;
			}
			var ids = Array.prototype.map.call(
				list.querySelectorAll( '.azrcrv-to-sortable-item' ),
				function ( item ) {
					return item.getAttribute( 'data-term-id' );
				}
			);
			hiddenInput.value = ids.join( ',' );
		}

		/**
		 * Remove the "drag over" affordance class from every item.
		 */
		function clearDragOverClasses() {
			list.querySelectorAll( '.azrcrv-to-drag-over' ).forEach( function ( el ) {
				el.classList.remove( 'azrcrv-to-drag-over' );
			} );
		}

		/**
		 * Actually move draggedItem to wherever the most recent dragover
		 * said it should go, then clear that pending state. This is the
		 * *only* place the DOM is mutated for a drag-and-drop reorder -
		 * deliberately not done continuously during dragover, since
		 * mutating the list while the pointer is still over it changes
		 * what's under the pointer and can cause the browser to
		 * immediately re-fire dragover for the new element underneath,
		 * undoing (or flip-flopping) the very move that was just made -
		 * especially with very short lists, where the dragged item and
		 * its neighbour can end up swapping back and forth.
		 */
		function applyPendingMove() {

			if ( ! draggedItem ) {
				return;
			}

			if ( listDropAtEnd ) {
				list.appendChild( draggedItem );
			} else if ( dropTarget && dropTarget.item !== draggedItem ) {
				if ( 'before' === dropTarget.position ) {
					list.insertBefore( draggedItem, dropTarget.item );
				} else {
					list.insertBefore( draggedItem, dropTarget.item.nextSibling );
				}
			}

			dropTarget    = null;
			listDropAtEnd = false;
		}

		list.querySelectorAll( '.azrcrv-to-sortable-item' ).forEach( function ( item ) {
			bindItem( item );
		} );

		/**
		 * Bind drag-and-drop and up/down button handlers to one <li>.
		 *
		 * @param {HTMLElement} item The <li class="azrcrv-to-sortable-item"> element.
		 */
		function bindItem( item ) {

			item.addEventListener( 'dragstart', function () {
				draggedItem   = item;
				dropTarget    = null;
				listDropAtEnd = false;
				item.classList.add( 'azrcrv-to-dragging' );
			} );

			item.addEventListener( 'dragend', function () {
				item.classList.remove( 'azrcrv-to-dragging' );
				clearDragOverClasses();
				draggedItem   = null;
				dropTarget    = null;
				listDropAtEnd = false;
			} );

			item.addEventListener( 'dragover', function ( e ) {
				e.preventDefault();

				if ( ! draggedItem || draggedItem === item ) {
					return;
				}

				var rect     = item.getBoundingClientRect();
				var midpoint = rect.top + ( rect.height / 2 );

				clearDragOverClasses();
				item.classList.add( 'azrcrv-to-drag-over' );

				listDropAtEnd = false;
				dropTarget    = {
					item:     item,
					position: ( e.clientY < midpoint ) ? 'before' : 'after',
				};
			} );

			item.addEventListener( 'drop', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				clearDragOverClasses();
				applyPendingMove();
				syncHiddenInput();
			} );

			var moveUpButton   = item.querySelector( '.azrcrv-to-move-up' );
			var moveDownButton = item.querySelector( '.azrcrv-to-move-down' );

			if ( moveUpButton ) {
				moveUpButton.addEventListener( 'click', function () {
					var previous = item.previousElementSibling;
					if ( previous ) {
						list.insertBefore( item, previous );
						syncHiddenInput();
						moveUpButton.focus();
					}
				} );
			}

			if ( moveDownButton ) {
				moveDownButton.addEventListener( 'click', function () {
					var next = item.nextElementSibling;
					if ( next ) {
						list.insertBefore( next, item );
						syncHiddenInput();
						moveDownButton.focus();
					}
				} );
			}
		}

		// Allow dropping below the last item, by dropping on empty space
		// within the list itself (rather than on any particular <li>).
		list.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			if ( e.target === list ) {
				clearDragOverClasses();
				dropTarget    = null;
				listDropAtEnd = true;
			}
		} );

		list.addEventListener( 'drop', function ( e ) {
			e.preventDefault();
			clearDragOverClasses();
			applyPendingMove();
			syncHiddenInput();
		} );

		syncHiddenInput();

		// Final safety net: force one more sync right before the
		// enclosing form actually submits, regardless of whether every
		// drag/drop or button interaction along the way correctly fired
		// its own sync. This is what the saved order is actually built
		// from - everything above exists only to keep the hidden input
		// visually/functionally in sync *during* editing.
		var form = list.closest( 'form' );
		if ( form ) {
			form.addEventListener( 'submit', syncHiddenInput );
		}
	}

} );

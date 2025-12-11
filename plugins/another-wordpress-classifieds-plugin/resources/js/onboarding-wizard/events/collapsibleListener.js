/**
 * Internal dependencies
 */
import { getElements } from '../elements';
import { HIDDEN_CLASS, OPEN_CLASS } from '../shared';

/**
 * Manages event handling for collapsible sections in the onboarding wizard.
 *
 * @return {void}
 */
function addCollapsibleEvents() {
	const { collapsible } = getElements();

	collapsible.addEventListener( 'click', onCollapsibleClick );
}

/**
 * Handles the click event on the collapsible section.
 *
 * @private
 * @param {Event} event The event object
 * @return {void}
 */
const onCollapsibleClick = ( event ) => {
	event.preventDefault();

	const collapsible = event.currentTarget;
	collapsible.classList.toggle( OPEN_CLASS );

	const content = collapsible.nextElementSibling;
	content.classList.toggle( HIDDEN_CLASS );

	content.classList.toggle( 'awpcp-fadein-down' );
};

export default addCollapsibleEvents;

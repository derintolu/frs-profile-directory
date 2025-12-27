/**
 * Loan Officer Directory - WordPress Interactivity API
 *
 * @package FRSProfileDirectory
 */

import { store, getContext } from '@wordpress/interactivity';

const { state } = store('frs/lo-directory', {
	state: {
		visibleCount: 12,
		totalCount: 0,
		searchQuery: '',
		selectedState: '',
	},

	actions: {
		loadMore() {
			state.visibleCount = Math.min(
				state.visibleCount + 12,
				state.totalCount
			);
		},

		updateSearch(event) {
			state.searchQuery = event.target.value.toLowerCase();
		},

		updateStateFilter(event) {
			state.selectedState = event.target.value;
		},

		clearSearch() {
			state.searchQuery = '';
		},
	},

	callbacks: {
		isCardHidden() {
			const context = getContext();
			// Card index from context, visibleCount from state
			return context.index > state.visibleCount;
		},
		isAllLoaded() {
			return state.visibleCount >= state.totalCount;
		},
	},
});

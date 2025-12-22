/**
 * Loan Officer Directory - WordPress Interactivity API
 *
 * @package FRSProfileDirectory
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store('frs/lo-directory', {
	state: {
		get perPage() {
			return state.perPage ?? 12;
		},
		searchQuery: '',
		selectedState: '',
	},

	actions: {
		loadMore() {
			const context = getContext();
			const increment = state.perPage;
			context.visibleCount = Math.min(
				context.visibleCount + increment,
				context.totalCount
			);
			context.allLoaded = context.visibleCount >= context.totalCount;
		},

		updateSearch(event) {
			state.searchQuery = event.target.value.toLowerCase();
		},

		updateStateFilter(event) {
			state.selectedState = event.target.value;
		},

		clearFilters() {
			state.searchQuery = '';
			state.selectedState = '';
			// Reset visible count when filters are cleared
			const context = getContext();
			context.visibleCount = state.perPage;
			context.allLoaded = context.visibleCount >= context.totalCount;
		},

		clearSearch() {
			state.searchQuery = '';
		},
	},

	callbacks: {
		isCardHidden() {
			const context = getContext();
			const cardContext = getContext();

			// Check if card is beyond visible count
			if (cardContext.index > context.visibleCount) {
				return true;
			}

			// Check search filter
			if (state.searchQuery) {
				const name = (cardContext.name || '').toLowerCase();
				const location = (cardContext.location || '').toLowerCase();
				const email = (cardContext.email || '').toLowerCase();
				if (
					!name.includes(state.searchQuery) &&
					!location.includes(state.searchQuery) &&
					!email.includes(state.searchQuery)
				) {
					return true;
				}
			}

			// Check state filter
			if (state.selectedState) {
				const areas = cardContext.serviceAreas || [];
				if (!areas.includes(state.selectedState)) {
					return true;
				}
			}

			return false;
		},

		hasActiveFilters() {
			return state.searchQuery !== '' || state.selectedState !== '';
		},

		showClearButton() {
			return state.searchQuery !== '';
		},
	},
});

<?php
/**
 * LO Directory Search Block - PHP Rendered
 */

declare(strict_types=1);

$placeholder = $attributes['placeholder'] ?? 'Search by name, location, or specialty...';
$wrapper_attributes = get_block_wrapper_attributes(['class' => 'frs-lo-search']);
?>

<div <?php echo $wrapper_attributes; ?>>
    <div class="frs-lo-search__wrapper">
        <svg class="frs-lo-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.3-4.3"/>
        </svg>
        <input
            type="text"
            class="frs-lo-search__input"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            id="frs-lo-search-input"
        />
        <button type="button" class="frs-lo-search__clear" id="frs-lo-search-clear">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
</div>

<script>
(function() {
    const input = document.getElementById('frs-lo-search-input');
    const clear = document.getElementById('frs-lo-search-clear');

    if (!input) return;

    let debounceTimer;

    input.addEventListener('input', function() {
        const value = this.value.trim().toLowerCase();

        // Toggle clear button
        if (clear) {
            clear.classList.toggle('visible', value.length > 0);
        }

        // Debounce search
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            // Dispatch custom event for directory to listen to
            document.dispatchEvent(new CustomEvent('frs-lo-search', {
                detail: { query: value }
            }));

            // Also filter cards directly if directory block exists
            filterCards(value);
        }, 200);
    });

    if (clear) {
        clear.addEventListener('click', function() {
            input.value = '';
            clear.classList.remove('visible');
            document.dispatchEvent(new CustomEvent('frs-lo-search', {
                detail: { query: '' }
            }));
            filterCards('');
            input.focus();
        });
    }

    function filterCards(query) {
        const cards = document.querySelectorAll('.frs-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.querySelector('.frs-card__name')?.textContent?.toLowerCase() || '';
            const title = card.querySelector('.frs-card__title-nmls')?.textContent?.toLowerCase() || '';
            const areas = Array.from(card.querySelectorAll('.frs-card__area-tag')).map(t => t.textContent.toLowerCase()).join(' ');
            const contact = card.querySelector('.frs-card__contact')?.textContent?.toLowerCase() || '';

            const searchText = `${name} ${title} ${areas} ${contact}`;
            const matches = !query || searchText.includes(query);

            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        // Update count
        const countEl = document.querySelector('.frs-directory-block__count');
        if (countEl) {
            countEl.textContent = `${visibleCount} loan officer${visibleCount !== 1 ? 's' : ''} found`;
        }
    }
})();
</script>

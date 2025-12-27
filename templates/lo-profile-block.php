<?php
/**
 * LO Profile Template - Renders lo-detail block
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main">
    <div class="frs-profile-page">
        <?php
        // Render the lo-detail block - it will pick up the slug from query var
        echo render_block([
            'blockName' => 'frs/lo-detail',
            'attrs' => [],
        ]);
        ?>
    </div>
</main>

<?php
get_footer();

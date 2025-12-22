/**
 * Loan Officer Profile - Editor Registration
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';

import metadata from './block.json';
import './style.css';

registerBlockType(metadata.name, {
    edit: function Edit() {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <Placeholder
                    icon="businessperson"
                    label="Loan Officer Profile"
                    instructions="Displays a full loan officer profile page. Use on a page with /profile/{slug} URL pattern."
                />
            </div>
        );
    },
});

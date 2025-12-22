/**
 * Loan Officer Directory - Editor Registration
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
                    icon="groups"
                    label="Loan Officer Directory"
                    instructions="Displays a searchable directory of loan officers with state and service area filtering."
                />
            </div>
        );
    },
});

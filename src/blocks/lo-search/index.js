import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './style.css';

registerBlockType('frs/lo-search', {
    edit: function Edit({ attributes, setAttributes }) {
        const blockProps = useBlockProps({ className: 'frs-lo-search' });

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Placeholder Text"
                            value={attributes.placeholder}
                            onChange={(placeholder) => setAttributes({ placeholder })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <div className="frs-lo-search__wrapper">
                        <svg className="frs-lo-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input
                            type="text"
                            className="frs-lo-search__input"
                            placeholder={attributes.placeholder}
                            disabled
                        />
                    </div>
                </div>
            </>
        );
    },
    save: function Save() {
        return null; // PHP rendered
    },
});

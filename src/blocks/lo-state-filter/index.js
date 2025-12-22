import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './style.css';

registerBlockType('frs/lo-state-filter', {
    edit: function Edit({ attributes, setAttributes }) {
        const blockProps = useBlockProps({ className: 'frs-lo-state-filter' });

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Settings">
                        <TextControl
                            label="Label"
                            value={attributes.label}
                            onChange={(label) => setAttributes({ label })}
                        />
                        <TextControl
                            label="Hub URL"
                            value={attributes.hubUrl}
                            onChange={(hubUrl) => setAttributes({ hubUrl })}
                            help="Leave empty for current site"
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <div className="frs-lo-state-filter__wrapper">
                        <label className="frs-lo-state-filter__label">{attributes.label}</label>
                        <select className="frs-lo-state-filter__select" disabled>
                            <option>All States</option>
                            <option>TX</option>
                            <option>CA</option>
                            <option>FL</option>
                        </select>
                    </div>
                </div>
            </>
        );
    },
    save: function Save() {
        return null; // PHP rendered
    },
});

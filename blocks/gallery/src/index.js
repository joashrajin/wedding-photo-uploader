import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    RangeControl, 
    ToggleControl, 
    SelectControl,
    ColorPicker
} from '@wordpress/components';
import './style.scss';
import './editor.scss';

registerBlockType('wedding-photo-uploader/gallery', {
    edit: function Edit({ attributes, setAttributes }) {
        const blockProps = useBlockProps();
        const { 
            columns, 
            gutter, 
            imageSize, 
            showUploaderInfo,
            backgroundColor,
            textColor,
            fontSize,
            borderRadius
        } = attributes;

        // Get image sizes from localized data
        const imageSizes = window.wpuGalleryData?.imageSizes || {
            thumbnail: 'Thumbnail',
            medium: 'Medium',
            large: 'Large',
            full: 'Full Size'
        };

        const imageSizeOptions = Object.entries(imageSizes).map(([value, label]) => ({
            value,
            label: label.charAt(0).toUpperCase() + label.slice(1)
        }));

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Gallery Settings', 'wedding-photo-uploader')}>
                        <RangeControl
                            label={__('Columns', 'wedding-photo-uploader')}
                            value={columns}
                            onChange={(value) => setAttributes({ columns: value })}
                            min={1}
                            max={6}
                        />
                        <RangeControl
                            label={__('Gutter (px)', 'wedding-photo-uploader')}
                            value={gutter}
                            onChange={(value) => setAttributes({ gutter: value })}
                            min={0}
                            max={50}
                        />
                        <SelectControl
                            label={__('Image Size', 'wedding-photo-uploader')}
                            value={imageSize}
                            options={imageSizeOptions}
                            onChange={(value) => setAttributes({ imageSize: value })}
                        />
                        <ToggleControl
                            label={__('Show Uploader Info', 'wedding-photo-uploader')}
                            checked={showUploaderInfo}
                            onChange={(value) => setAttributes({ showUploaderInfo: value })}
                        />
                    </PanelBody>
                    <PanelBody title={__('Style Settings', 'wedding-photo-uploader')}>
                        <div className="components-base-control">
                            <label className="components-base-control__label">
                                {__('Background Color', 'wedding-photo-uploader')}
                            </label>
                            <ColorPicker
                                color={backgroundColor}
                                onChangeComplete={(value) => setAttributes({ backgroundColor: value.hex })}
                                disableAlpha
                            />
                        </div>
                        <div className="components-base-control">
                            <label className="components-base-control__label">
                                {__('Text Color', 'wedding-photo-uploader')}
                            </label>
                            <ColorPicker
                                color={textColor}
                                onChangeComplete={(value) => setAttributes({ textColor: value.hex })}
                                disableAlpha
                            />
                        </div>
                        <RangeControl
                            label={__('Font Size (px)', 'wedding-photo-uploader')}
                            value={fontSize}
                            onChange={(value) => setAttributes({ fontSize: value })}
                            min={12}
                            max={24}
                        />
                        <RangeControl
                            label={__('Border Radius (px)', 'wedding-photo-uploader')}
                            value={borderRadius}
                            onChange={(value) => setAttributes({ borderRadius: value })}
                            min={0}
                            max={20}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className="wedding-gallery-placeholder">
                        <div className="wedding-gallery-preview" style={{
                            '--columns': columns,
                            '--gutter': `${gutter}px`,
                            '--background-color': backgroundColor,
                            '--text-color': textColor,
                            '--font-size': `${fontSize}px`,
                            '--border-radius': `${borderRadius}px`
                        }}>
                            {[...Array(columns * 2)].map((_, index) => (
                                <div key={index} className="gallery-item placeholder">
                                    <div className="gallery-image"></div>
                                    {showUploaderInfo && (
                                        <div className="photo-meta">
                                            <div className="uploader-name">John Doe</div>
                                            <div className="upload-date">January 1, 2024</div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                        <p className="wedding-gallery-note">
                            {__('This is a preview. The actual gallery will display approved photos from your wedding guests.', 'wedding-photo-uploader')}
                        </p>
                    </div>
                </div>
            </>
        );
    },
    
    // This block is rendered server-side
    save: () => null
}); 
/**
 * WordPress components for creating the block UI
 */
import {
  useBlockProps,
  InspectorControls,
  RichText,
  PanelColorSettings,
  FontSizePicker,
} from '@wordpress/block-editor';

import {
  PanelBody,
  TextControl,
  RangeControl,
} from '@wordpress/components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set block attributes.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
  const {
    title,
    description,
    submitButtonText,
    borderRadius,
  } = attributes;

  const blockProps = useBlockProps({
    style: {
      borderRadius: borderRadius,
    }
  });

  return (
    <>
      <InspectorControls>
        <PanelBody title="Form Settings">
          <TextControl
            label="Submit Button Text"
            value={submitButtonText}
            onChange={(value) => setAttributes({ submitButtonText: value })}
          />
          <RangeControl
            label="Border Radius"
            value={parseInt(borderRadius)}
            onChange={(value) => setAttributes({ borderRadius: value + 'px' })}
            min={0}
            max={50}
          />
        </PanelBody>
        <PanelColorSettings
          title="Color Settings"
          initialOpen={false}
          colorSettings={[
            {
              value: attributes.backgroundColor,
              onChange: (color) => setAttributes({ backgroundColor: color }),
              label: 'Background Color',
            },
            {
              value: attributes.textColor,
              onChange: (color) => setAttributes({ textColor: color }),
              label: 'Text Color',
            },
          ]}
        />
        <PanelBody title="Typography" initialOpen={false}>
          <FontSizePicker
            value={attributes.fontSize}
            onChange={(size) => setAttributes({ fontSize: size })}
          />
        </PanelBody>
      </InspectorControls>
      
      <div {...blockProps}>
        <div className="wedding-photo-uploader-block-edit">
          <RichText
            tagName="h2"
            className="wedding-photo-uploader-title"
            value={title}
            onChange={(value) => setAttributes({ title: value })}
            placeholder="Enter form title"
          />
          
          <RichText
            tagName="p"
            className="wedding-photo-uploader-description"
            value={description}
            onChange={(value) => setAttributes({ description: value })}
            placeholder="Enter form description"
          />
          
          <div className="wedding-photo-uploader-form-preview">
            <div className="form-fields-preview">
              <div className="form-field-preview">
                <label>Name</label>
                <div className="input-preview"></div>
              </div>
              <div className="form-field-preview">
                <label>Photos</label>
                <div className="file-input-preview"></div>
              </div>
            </div>
            
            <div className="submit-button-preview">
                                  {submitButtonText || "Upload Media"}
            </div>
          </div>
          
          <p className="wedding-photo-uploader-note">
            This is a preview. The actual form will appear on the front end.
          </p>
        </div>
      </div>
    </>
  );
} 
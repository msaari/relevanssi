<?php
/**
 * /lib/settings/infrastructure/class-relevanssi-setting-field-factory.php
 *
 * Factory class definition for Relevanssi premium setting fields handlers.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Factory
 *
 * Centralized coordinator mapping control type strings to concrete field object
 * class instances using design pattern abstractions.
 */
class Relevanssi_Setting_Field_Factory {

	/**
	 * Map configuration types to concrete class names.
	 *
	 * @var array
	 */
	private static $registry = array(
		'text'                   => 'Relevanssi_Setting_Field_Text',
		'textarea'               => 'Relevanssi_Setting_Field_Textarea',
		'color'                  => 'Relevanssi_Setting_Field_Color',
		'select'                 => 'Relevanssi_Setting_Field_Select',
		'checkbox'               => 'Relevanssi_Setting_Field_Checkbox',
		'radio'                  => 'Relevanssi_Setting_Field_Radio',
		'multicheckbox'          => 'Relevanssi_Setting_Field_Multicheckbox',
		'number'                 => 'Relevanssi_Setting_Field_Number',
		'subheader'              => 'Relevanssi_Setting_Field_Subheader',
		'post_types_table'       => 'Relevanssi_Setting_Field_Post_Types_Table',
		'taxonomies_table'       => 'Relevanssi_Setting_Field_Taxonomies_Table',
		'custom_fields_list'     => 'Relevanssi_Setting_Field_Custom_Fields_List',
		'attachment_manager'     => 'Relevanssi_Setting_Field_Attachment_Manager',
		'custom_fields_group'    => 'Relevanssi_Setting_Field_Custom_Fields_Group',
		'callback'               => 'Relevanssi_Setting_Field_Callback',
		'related_keywords'       => 'Relevanssi_Setting_Field_Related_Keywords',
		'related_post_types'     => 'Relevanssi_Setting_Field_Related_Post_Types',
		'media_upload'           => 'Relevanssi_Setting_Field_Media_Upload',
		'submit_button'          => 'Relevanssi_Setting_Field_Submit_Button',
		'upsell'                 => 'Relevanssi_Setting_Field_Upsell',
		'redirects'              => 'Relevanssi_Setting_Field_Redirects',
		'synonyms'               => 'Relevanssi_Setting_Field_Synonyms',
		'category_checklist'     => 'Relevanssi_Setting_Field_Category_Checklist',
		'standalone_taxonomies'  => 'Relevanssi_Setting_Field_Standalone_Taxonomies',
		'stopwords_manager'      => 'Relevanssi_Setting_Field_Stopwords_Manager',
		'body_stopwords_manager' => 'Relevanssi_Setting_Field_Body_Stopwords_Manager',
		'weights_table'          => 'Relevanssi_Setting_Field_Weights_Table',
		// New fields go here.
	);

	/**
	 * Instantiates the correct field handler object.
	 *
	 * @param string $id     Unique option field key string descriptor identifier.
	 * @param array  $config Keyed definitions specifying control constraints.
	 *
	 * @throws InvalidArgumentException When an unsupported field type engine string is provided.
	 * @return Relevanssi_Setting_Field_Interface Concrete instantiated control element handler.
	 */
	public static function create( string $id, array $config ): Relevanssi_Setting_Field_Interface {
		$type = $config['type'] ?? 'text';

		/**
		 * Filters the resolved class name for a given settings field type.
		 *
		 * Allows extensions to register dynamic fields custom handlers override setups.
		 *
		 * @param string|null $class_name Name of the concrete implementation class.
		 */
		$class_name = apply_filters( "relevanssi_setting_field_class_{$type}", self::$registry[ $type ] ?? null );

		if ( ! $class_name || ! class_exists( $class_name ) ) {
			throw new InvalidArgumentException( sprintf( 'Unsupported Relevanssi settings field element type: "%s"', esc_attr( $type ) ) );
		}

		return new $class_name( $id, $config );
	}
}

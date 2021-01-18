<?php
namespace ScssLibrary\Settings;

/**
 * Componentes y controles para el panel de personalización de controles
 */
class ScssLibrary
{
	use \Baxtian\Singleton;

	/**
	 * Inicializa el componente
	 */
	protected function __construct()
	{
		add_action('customize_register', [$this, 'options']);
	}

	/**
	 * Agregar el panel a la pantalla de administración y persoanlización
	 * @param  WP_Customize_Manager $wp_customize Instancia del controlador del personalizador
	 */
	public function options($wp_customize)
	{
		// Agregar sección de Redes sociales
		$wp_customize->add_section(
			'scsslibrary',
			[
				'title' => __('SCSS Compiler', 'scsslib'),
			]
		);

		// Declarar el campo para editor de mensajes
		$wp_customize->add_setting(
			'scsslibrary[develop]',
			[
				'type' => 'option', // or 'theme_mod'
			]
		);

		$wp_customize->add_control(
			'scsslibrary[develop]',
			[
				'label'       => __('Developer mode', 'scsslib'),
				'description' => __('Enable this option if you want to always compile the files. This is helpful while developing but remember to disable it when in production.', 'scsslib'),
				'section'     => 'scsslibrary',
				'settings'    => 'scsslibrary[develop]',
				'type'        => 'checkbox',
			]
		);
	}
}

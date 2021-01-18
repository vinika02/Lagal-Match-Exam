<?php
/*

Copyright 2019 Juan Sebastián Echeverry (baxtian.echeverry@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

namespace ScssLibrary;

use ScssPhp\ScssPhp\Compiler;
use Exception;

/**
 * Clase para agregar arhivos de estilo scss directamente con la función wp_enqueue_style.
 */
class ScssLibrary
{
	use \Baxtian\Singleton;

	// Arreglo apra guardar los mensajes de error de compilación
	protected $errors = [];

	// Directorio donde se escribirán los archivos compilados
	private $build_dir;
	private $build_url;

	// Modo de desarrollo
	private $modo_desarrollo;

	/**
	 * Inicializa el componente
	 */
	protected function __construct()
	{
		add_action('plugins_loaded', [$this, 'plugin_setup']);
		add_filter('style_loader_src', [$this, 'style_loader_src'], 10, 2);
		add_action('wp_footer', [$this, 'wp_footer']);
		add_action('admin_notices', [$this, 'admin_notices']);
		add_action('admin_bar_menu', [$this, 'admin_bar_menu'], 100);

		$this->modo_desarrollo = false;

		$this->set_directory();
	}

	/**
	 * Función después de activar los plugins
	 */
	public function plugin_setup(): void
	{
		// Activar el traductor
		load_plugin_textdomain('scsslib', false, basename(dirname(__FILE__, 2)) . '/languages/');
	}

	public function set_directory($path = false)
	{
		if (!$path) {
			// Directorio donde se almacenará el cache
			$pathname = '/build/scss_library/';
			if (is_multisite()) {
				$blog_id   = get_current_blog_id();
				$pathname .= $blog_id . '/';
			}

			$this->build_dir = WP_CONTENT_DIR . $pathname;
			$this->build_url = WP_CONTENT_URL . $pathname;
		} else {
			$this->build_dir = $path;
			$this->build_url = 'file:/' . $path;
		}
	}

	/**
	 * Función para atender los estilos y compilar aquellos que son de extensión SCSS
	 * @param  string $src    URL del archivo a ser atendido
	 * @param  string $handle Nombre con el que se identifica internamente
	 * @return string         URL a la versión copilada o al original en caso de no haber sido compilado
	 */
	public function style_loader_src($src, $handle): string
	{
		// Si el nombre el archivo no tiene el texto scss entonces
		// retornar el estilo sin cambios
		if (strpos($src, 'scss') === false) {
			return $src;
		}

		// ¿Ya sabemos si estamos en modo de desarrollo?
		if (!$this->modo_desarrollo) {
			// Determinar si hubo un cambio
			$opciones = get_option('scsslibrary');

			// El modo de desarrollo se presenta porque este la opción develop activa
			// en las opciones del plugin, porque esté definido WP_DEBUG o porque ne la url esté el atributo recompile_scss_files
			$this->modo_desarrollo = (
				(isset($opciones['develop']) && $opciones['develop']) ||
				(defined('WP_DEBUG') && WP_DEBUG === true) ||
				(isset($_GET['recompile_scss_files'])) ||
				(isset($_GET['activate_scss_library_devmode']))
			) ? true : false;
		}

		// Parsear la URL del archivo de estilo
		$url      = parse_url($src);
		$pathinfo = pathinfo($url['path']);

		// Revisión detallada para determinar si la extensión corresponde
		if ($pathinfo['extension'] !== 'scss') {
			return $src;
		}

		// Convertir la URL a rutas absolutas.
		$in = preg_replace('/^' . preg_quote(site_url(), '/') . '/i', '', $src);

		// Ignorar SCSS de CDNs, otros dominios y rutas relativos
		if (preg_match('#^//#', $in) || strpos($in, '/') !== 0) {
			return $src;
		}

		// Crear ruta completa
		$in = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $url['path'];

		// Si es parte de un multisitio entonces hay que retirar el 'dominio'
		if (is_multisite()) {
			$aux                 = get_blog_details();
			$blog_details_path   = $aux->path;
			if ($blog_details_path != PATH_CURRENT_SITE) {
				$in = str_replace($blog_details_path, PATH_CURRENT_SITE, $in);
			}
		}

		// Confirmar que el archivo fuente existe
		if (file_exists($in) === false) {
			array_push($this->errors, [
				'handle'  => $handle,
				'file'    => basename($in),
				'message' => __('Source file not found.', 'scsslib'),
			]);

			return $src;
		}

		// Generar nombre único paa el archivo compilado
		$outName = sha1($url['path']) . '.css';

		// Directorios donde se guardarán los archivos compilados
		$outputDir = $this->build_dir;
		$outputUrl = $this->build_url . $outName;

		// Crear el directorio de archivos compilados si no existe
		if (is_dir($outputDir) === false) {
			if (wp_mkdir_p($outputDir) === false) {
				array_push($this->errors, [
					'handle'  => $handle,
					'file'    => 'Cache Directory',
					'message' => __('File Permissions Error, unable to create cache directory. Please make sure the Wordpress Uploads directory is writable.', 'scsslib'),
				]);
				delete_transient('scsslib_filemtimes');

				return $src;
			}
		}

		// Revisar que el directorio donde se almacenarán los archivos
		// compilados tiene permisos de escritura
		if (is_writable($outputDir) === false) {
			array_push($this->errors, [
				'handle'  => $handle,
				'file'    => 'Cache Directory',
				'message' => __('File Permissions Error, permission denied. Please make the cache directory writable.', 'scsslib'),
			]);
			delete_transient('scsslib_filemtimes');

			return $src;
		}

		// Ruta comleta para el archivo compilado
		$out = $outputDir . $outName;

		// Bandera para saber si se requiere compilar el archivo. Por defecto suponemos
		// que es o no necesario compilar segun si estamso en el modo de desarrollo o no.
		$compileRequired = $this->modo_desarrollo;

		// Obtener la fecha que tenemos almacenada como fecha de creación de cada archivos
		if (($filemtimes = get_transient('scsslib_filemtimes')) === false) {
			$filemtimes = [];
		}

		// Compara la fecha de creación del archivo compilado con la fecha de creación del
		// archivo fuente. Si es más reciente entones hay que compilar.
		if (isset($filemtimes[$out]) === false || $filemtimes[$out] < filemtime($in)) {
			$compileRequired = true;
		}

		// Obtener las variables variables
		$variables = apply_filters('scsslib_compiler_variables', [
			'template_directory_uri'   => get_template_directory_uri(),
			'stylesheet_directory_uri' => get_stylesheet_directory_uri(),
		]);

		// Si las variables no coinciden entonces hay que compilar
		if ($compileRequired === false) {
			$signature = sha1(serialize($variables));
			if ($signature !== get_transient('scsslib_variables_signature')) {
				$compileRequired = true;
				set_transient('scsslib_variables_signature', $signature);
			}
		}

		// Si el archivo no existe entonces hay que compilar
		if (!file_exists($outputDir . $outName)) {
			$compileRequired = true;
		}

		// ¿Debemos o no compilar?
		if ($compileRequired) {
			// Compilar de SCSS a CSS
			try {
				// Tipo de formato por defecto
				$formatter = 'ScssPhp\ScssPhp\Formatter\Expanded';

				// Inicializar compilador
				$compiler = new Compiler();

				// Determinar las varianles para el archivo de depuración
				$srcmap_data = [
					// Ruta absoluta donde se escribirá el archivo .map
					'sourceMapWriteTo'  => $outputDir . $outName . '.map',
					// URL completa o relativa al archivp .map
					'sourceMapURL'      => $outputUrl . '.map',
					// (Opcional) URL relativa o completa al archivo .css compilado
					'sourceMapFilename' => $outputUrl,
					// Ruta parcial (raiz del servidor) para crear la URL relativa
					'sourceMapBasepath' => rtrim(ABSPATH, '/'),
					// (Opcional) Antepuesto a las entradas de campo 'fuente' para reubicar archivos fuente
					'sourceRoot'        => $src,
				];

				// Configuración para crear el archivo .map de depuración.
				$compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
				$compiler->setSourceMapOptions($srcmap_data);

				// Configuración para inicializar el compilador.
				$compiler->setFormatter($formatter);
				$compiler->setVariables($variables);
				$compiler->setImportPaths(dirname($in));

				$css = $compiler->compile(file_get_contents($in), $in);
			} catch (Exception $e) {
				array_push($this->errors, [
					'handle'  => $handle,
					'file'    => basename($in),
					'message' => $e->getMessage(),
				]);

				return $src;
			}

			// Transformar las rutas relativas para que funcionen correctamente
			$css = preg_replace('#(url\((?![\'"]?(?:https?:|/))[\'"]?)#miu', '$1' . dirname($url['path']) . '/', $css);

			// Guardar el archivo compilado.
			file_put_contents($out, $css);

			// Guardar el tiempo de creación del archivo.
			$filemtimes[$out] = filemtime($out);
			set_transient('scsslib_filemtimes', $filemtimes);
		}

		// Construir URL del archivio compilado con las cadenas de consulta que
		// venían en la URL del archivo fuente.
		return empty($url['query']) ? $outputUrl : $outputUrl . '?' . $url['query'];
	}

	/**
	 * Los mensajes para ser desplegados en la parte duperior del dashboard.
	 * De momento muestra una advertencia si estamos en modo de desarrollador
	 */
	public function admin_notices() :void
	{
		// Leer de la sopciones si estamos en modo develop
		$opciones = get_option('scsslibrary');

		//Si hay un parámetro por get para desactivar el modo de desarrollo, desactivarlo
		if (isset($_GET['deactivate_scss_library_devmode'])) {
			$opciones['develop'] = false;
			update_option('scsslibrary', $opciones);
		}

		if (isset($opciones['develop']) && $opciones['develop'] === true) {
			$url = parse_url($_SERVER['REQUEST_URI']);

			// Parcear el query
			$query = [];
			if (isset($url['query'])) {
				parse_str($url['query'], $query);
			}
			$query['deactivate_scss_library_devmode'] = true;
			$url['query']                             = http_build_query($query);
			$url                                      = $url['path'] . '?' . $url['query'];

			$text = sprintf(__("The development mode from the <strong>SCSS-Library</strong> is active. Remember to <a href='%s'>deactivate it</a> in case this is a production environment.", 'scsslib'), $url);
			printf('<div class="error"><p>%s</p></div>', $text);
		}
	}

	/**
	 * Abregar items a la barra de administración para manejar scss-library
	 * @param  Object $admin_bar Barra de administración
	 */
	public function admin_bar_menu($admin_bar): void
	{
		// Solo los usuarios con permisos para editar temas pueden tener
		// acceso a estas acciones
		if (current_user_can('edit_theme_options')) {

			// Items para habilitar o deshabilitar
			$opciones = get_option('scsslibrary');

			// ¿Activar el modo de desarrollo?
			if (isset($_GET['activate_scss_library_devmode'])) {
				$opciones['develop'] = true;
				update_option('scsslibrary', $opciones);
			}

			// ¿Desactivar el modo de desarrollo?
			if (isset($_GET['deactivate_scss_library_devmode'])) {
				$opciones['develop'] = false;
				update_option('scsslibrary', $opciones);
			}

			// Item contenedor principal
			$admin_bar->add_menu([
				'id'    => 'scss-library',
				'title' => __('SCSS Library', 'scsslib'),
				'href'  => '#',
				'meta'  => [
					'class' => (isset($opciones['develop']) && $opciones['develop']) ? 'sl-alert' : '',
					'html'  => '<style>
						#wpadminbar .menupop.sl-alert > a.ab-item { color: white; background: #9c3e3d; }
						</style>',
				],
			]);

			// Elementos para la URL
			$url = parse_url($_SERVER['REQUEST_URI']);

			// Parcear el query
			$query = [];
			if (isset($url['query'])) {
				parse_str($url['query'], $query);
			}
			$query1['recompile_scss_files']            = true;
			$query2['deactivate_scss_library_devmode'] = true;
			$query3['activate_scss_library_devmode']   = true;

			// Sub item para recompilar
			if (!is_admin()) {
				$admin_bar->add_menu([
					'id'     => 'clear-scss',
					'parent' => 'scss-library',
					'title'  => __('Recompile SCSS files', 'scsslib'),
					'href'   => $url['path'] . '?' . http_build_query($query1),
				]);
			}

			// Si no está activo el develop
			if (isset($opciones['develop']) && $opciones['develop']) {
				$admin_bar->add_menu([
					'id'     => 'deactivate-scss-devmode',
					'parent' => 'scss-library',
					'title'  => __('Deactivate development mode', 'scsslib'),
					'href'   => $url['path'] . '?' . http_build_query($query2),
					'meta'   => [
						'class' => 'sl-active',
						'html'  => '<style>
							#wpadminbar .ab-submenu .sl-active > a.ab-item { color: white; background: #9c3e3d; }
							</style>',
					],
				]);
			} else {
				$admin_bar->add_menu([
					'id'     => 'activate-scss-devmode',
					'parent' => 'scss-library',
					'title'  => __('Activate development mode', 'scsslib'),
					'href'   => $url['path'] . '?' . http_build_query($query3),
				]);
			}
		}
	}

	/**
	 * Agergar elementos al pie de página.
	 */
	public function wp_footer(): void
	{
		// En caso de haber registro de errores, visualizarlos
		if (count($this->errors)) {
			$this->displayErrors();
		}
	}

	/**
	 * Publicación de errores en HTML
	 */
	protected function displayErrors(): void
	{
		?>
		<style>
		#scsslib {
			position: fixed;
			top: 0;
			z-index: 99999;
			width: 100%;
			padding: 20px;
			overflow: auto;
			background: #f5f5f5;
			font-family: 'Source Code Pro', Menlo, Monaco, Consolas, monospace;
			font-size: 18px;
			color: #666;
			text-align: left;
			border-left: 5px solid #DD3D36;
		}
		body.admin-bar #scsslib {
			top: 32px;
		}
		#scsslib .scsslib-title {
			margin-bottom: 20px;
			font-size: 120%;
		}
		#scsslib .scsslib-error {
			margin: 10px 0;
		}
		#scsslib .scsslib-file {
			font-weight: bold;
			white-space: pre;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
		#scsslib .scsslib-message {
			white-space: pre;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
		</style>
		<div id="scsslib">
			<div class="scsslib-title"><?php _e('Sass Compiling Error', 'scsslib'); ?></div>
			<?php foreach ($this->errors as $error): ?>
				<div class="scsslib-error">
					<div class="scsslib-file"><?php if ($error['handle']) {
			printf('%s : ', $error['handle']);
		} ?><?php print $error['file'] ?></div>
					<div class="scsslib-message"><?php print $error['message'] ?></div>
				</div>
			<?php endforeach ?>
		</div>
		<?php
	}
}
